<?php

namespace App\Services\OrderProcessing;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cms\CurrencyExchange;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentFeeWebhookService
{
    public function handle(array $data): Order
    {
        $order = Order::where('order_id_2game', $data['order_id'])
            ->orWhere('sale_transaction_id', $data['transaction_id'])
            ->first();

        if (!$order) {
            Log::channel('order')->warning('Payment fee webhook: order not found', [
                'order_id'       => $data['order_id'],
                'transaction_id' => $data['transaction_id'],
            ]);
            throw new ModelNotFoundException('Order not found.');
        }

        $orderItemCurrency = OrderItem::where('order_id', $order->id)->value('currency_code');

        if (!$orderItemCurrency) {
            Log::channel('order')->warning('Payment fee webhook: order item currency not found', [
                'order_id_2game' => $order->order_id_2game,
            ]);
            throw new RuntimeException('Order item currency could not be determined.');
        }

        $currency = strtoupper($data['currency']);
        $rateDate = $order->created_at->toDateString();

        $feeInEur = $currency === 'EUR'
            ? $data['payment_fee']
            : round($data['payment_fee'] * $this->getRate($currency, 'EUR', $rateDate), 6);

        $feeInOrderCurrency = $orderItemCurrency !== 'EUR'
            ? round($feeInEur * $this->getRate('EUR', $orderItemCurrency, $rateDate), 6)
            : $feeInEur;

        DB::transaction(function () use ($order, $data, $feeInOrderCurrency, $feeInEur) {
            $order->update([
                'payment_fee'         => $feeInOrderCurrency,
                'payment_fee_eur'     => $feeInEur,
                'sale_transaction_id' => $data['transaction_id'],
            ]);
        });

        Log::channel('order')->info('Payment fee updated via webhook', [
            'order_id_2game'      => $order->order_id_2game,
            'transaction_id'      => $data['transaction_id'],
            'payment_fee'         => $feeInOrderCurrency,
            'payment_fee_eur'     => $feeInEur,
            'order_item_currency' => $orderItemCurrency,
            'rate_date'           => $rateDate,
        ]);

        return $order;
    }

    private function getRate(string $from, string $to, string $date): float
    {
        $rate = CurrencyExchange::getRate($from, $to, $date);

        if ($rate <= 0) {
            Log::channel('order')->warning('Payment fee webhook: invalid exchange rate', [
                'from' => $from,
                'to'   => $to,
                'date' => $date,
                'rate' => $rate,
            ]);
            throw new RuntimeException("Exchange rate not available for {$from} to {$to} on {$date}.");
        }

        return $rate;
    }
}
