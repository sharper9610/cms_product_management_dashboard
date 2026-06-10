<?php

namespace App\Services\OrderProcessing;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IncommProcessor implements OrderItemProcessorInterface
{
    protected string $endpoint;
    protected string $password;

    public function __construct()
    {
        $this->endpoint = config('services.incomm.endpoint')."/order/shopify";
        $this->password = config('services.incomm.password');
    }

    public function process(OrderItem $item): bool
    {
        $item->update(['status' => OrderStatus::PROCESSING->value]);
        $item->load('order');
        $body = [
            'product_id'         => (int) $item->product_id,
            'order_id_2game'     => $item->order->order_id_2game,
            'total_price'        => (float) $item->sales_price_including_vat,
            'ConsumerIP'         => $item->order->consumer_ip,
            'vat_amount'         => (float) $item->vat_amount,
            'payment_method'     => $item->order->payment_method,
            'payment_fee'        => (float) $item->order->payment_fee,
            'giftcard_amount'    => (float) ($item->giftcard_amount ?? 0),
            'discount_amount'    => (float) ($item->discount_amount ?? 0),
            'row_total'          => (float) ($item->row_total ?? $item->sales_price_including_vat),
            'subtotal'           => (float) ($item->order->subtotal ?? $item->sales_price_including_vat),
            'grand_total'        => (float) ($item->order->grand_total ?? $item->sales_price_including_vat),
            'total_qty_ordered'  => (int) $item->order->total_qty_ordered,
            'amount_paid'        => (float) ($item->order->row_total),
            'amount_ordered'     => (float) ($item->order->row_total),
            'is_shopify_order'   => 1,
        ];

        try {
            $response = Http::post("{$this->endpoint}?password={$this->password}", $body);

            if ($response->successful()) {
                Log::channel('order')->info("Incomm order processed successfully", [
                    'order_item_id' => $item->id,
                    'endpoint'      => $this->endpoint,
                    'params'        => $body,
                    'response'      => $response->json(),
                ]);

                $item->update([
                    'status' => OrderStatus::COMPLETED->value,
                    'key_id' => $response->json('data.keyId'),
                    'retailer_order_id' => $response->json('data.retailerOrderId'),
                    'cost_price' => $response->json('data.cost_price'),
                    'cost_price_euro' => $response->json('data.cost_price_euro'),
                ]);

                return true;
            }

            $item->update([
                'status' => OrderStatus::FAILED->value,
                'failed_reason' => $response->body(),
            ]);

            Log::channel('order')->error("Incomm order failed", [
                'order_item_id' => $item->id,
                'status' => $response->status(),
                'body' => $response->body(),
                'params'        => $body,
                'endpoint'      => $this->endpoint,
            ]);

            return false;
        } catch (Exception $e) {
            Log::channel('order')->error("Incomm order exception", [
                'order_item_id' => $item->id,
                'error' => $e->getMessage(),
                'params'        => $body,
                'endpoint'      => $this->endpoint,
            ]);

            $item->update(['failed_reason' => $e->getMessage()]);

            return false;
        }
    }
}
