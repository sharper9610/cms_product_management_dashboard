<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderItem;
use App\Models\Order;
use App\Services\Cms\CurrencyExchange;
use Illuminate\Support\Facades\Log;

class ConvertOrderPricesToEuro extends Command
{
    protected $signature = 'orders:convert-to-eur 
                            {orderId? : The ID of the order} 
                            {--all : Convert order prices for ALL orders}';

    protected $description = 'Convert prices of order items and order payment_fee to EUR';

    public function handle()
    {
        $runAll  = $this->option('all');
        $orderId = $this->argument('orderId');

        if ($runAll) {
            return $this->convertAllOrders();
        }

        if (!$orderId) {
            $this->error("You must provide an orderId OR use --all");
            return null;
        }

        $this->convertSingleOrder($orderId);
    }

    protected function convertSingleOrder(int $orderId)
    {
        $this->info("Processing Order ID: {$orderId}");

        $orderItems = OrderItem::where('order_id', $orderId)
            ->get();

        if ($orderItems->isNotEmpty()) {
            $this->processItems($orderItems);
            $this->info("Order items conversion completed.");
        }

        $order = Order::where('id',$orderId)->with('items')->first();

        if ($order && $order->payment_fee > 0 && (!$order->payment_fee_eur || $order->payment_fee_eur == 0)) {
            $order_date = $order->created_at->format('Y-m-d');
            $rate = CurrencyExchange::getRate($order->items->first()->currency_code,'EUR', $order_date);
            if ($rate > 0) {
                $order->payment_fee_eur = $order->payment_fee * $rate;
                $order->save();

                Log::info("Order payment fee converted | order: {$order->id}, rate: {$rate}");
            }
        }

        $this->info("Order {$orderId} conversion completed.");
    }

    protected function convertAllOrders()
    {
        $this->info("Converting ALL orders needing conversion…");

        $orderIds = Order::pluck('id');

        if ($orderIds->isEmpty()) {
            $this->warn("No orders found that need conversion.");
            return;
        }

        $bar = $this->output->createProgressBar(count($orderIds));
        $bar->start();

        foreach ($orderIds as $orderId) {
            $orderItems = OrderItem::where('order_id', $orderId)
                ->needsEuroConversion()
                ->get();

            if ($orderItems->isNotEmpty()) {
                $this->processItems($orderItems);
            }

            $order = Order::where('id',$orderId)->with('items')->first();

            if ($order && $order->payment_fee > 0 && (!$order->payment_fee_eur || $order->payment_fee_eur == 0)) {
                $order_date = $order->created_at->format('Y-m-d');
                $rate = CurrencyExchange::getRate($order->items->first()->currency_code,'EUR', $order_date);
                if ($rate > 0) {
                    $order->payment_fee_eur = $order->payment_fee * $rate;
                    $order->gateway_fee_eur = $order->gateway_fee * $rate;
                    $order->shopify_plus_fee_eur = $order->shopify_plus_fee * $rate;
                    $order->save();

                    Log::info("Order payment fee converted | order: {$order->id}, rate: {$rate}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("All eligible orders converted successfully.");
    }

    protected function processItems($items)
    {
        foreach ($items as $item) {
            $date = $item->created_at->format('Y-m-d');
            $rate = CurrencyExchange::getRate($item->currency_code, 'EUR', $date);

            Log::info("Currency conversion | order: {$item->order_id}, item: {$item->id}, from: {$item->currency_code}, rate: {$rate}, date: {$date}");

            if ($rate <= 0) { continue; }

            $item->sales_price_including_vat_eur = $item->sales_price_including_vat * $rate;
            $item->sales_price_excluding_vat_eur = $item->sales_price_excluding_vat * $rate;
            $item->discount_amount_eur           = $item->discount_amount * $rate;
            $item->vat_amount_eur                = $item->vat_amount * $rate;
            $item->giftcard_amount_eur           = $item->giftcard_amount * $rate;
            $item->row_total_eur                 = $item->row_total * $rate;
            $item->cost_price_euro               = $item->cost_price * $rate;

            $item->save();
        }
    }
}
