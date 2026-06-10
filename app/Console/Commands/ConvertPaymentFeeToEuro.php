<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\Cms\CurrencyExchange;
use Illuminate\Support\Facades\Log;

class ConvertPaymentFeeToEuro extends Command
{
    protected $signature = 'orders:convert-payment-fee
                            {orderId?}
                            {--all}';

    protected $description = 'Convert order payment fees to EUR';

    public function handle()
    {
        $runAll  = $this->option('all');
        $orderId = $this->argument('orderId');

        if ($runAll) {
            return $this->convertAll();
        }

        if (!$orderId) {
            $this->error("Provide an orderId OR use --all");
            return null;
        }

        return $this->convertSingle($orderId);
    }

    protected function convertSingle(int $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            $this->warn("Order {$orderId} not found.");
            return;
        }

        $this->convertOrder($order);
        $this->info("Order {$orderId} payment fee converted.");
    }

    protected function convertAll()
    {
        $orders = Order::where(function ($q) {
            $q->whereNull('payment_fee_eur')->orWhere('payment_fee_eur', 0);
        })
        ->where('payment_fee', '>', 0)
        ->get();

        if ($orders->isEmpty()) {
            $this->warn("No orders need payment fee conversion.");
            return;
        }

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $this->convertOrder($order);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("All eligible orders converted.");
    }

    protected function convertOrder(Order $order)
    {
        $currency = $order->currency_code ?? 'EUR';
        $date     = $order->created_at->format('Y-m-d');

        $rate = CurrencyExchange::getRate($currency, 'EUR', $date);

        Log::info("PaymentFeeConversion | order: {$order->id}, from: {$currency}, rate: {$rate}, date: {$date}");

        if ($rate <= 0) {
            return;
        }

        $order->payment_fee_eur = $order->payment_fee * $rate;
        $order->save();
    }
}
