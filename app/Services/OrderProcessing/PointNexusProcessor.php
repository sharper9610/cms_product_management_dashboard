<?php

namespace App\Services\OrderProcessing;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PointNexusProcessor implements OrderItemProcessorInterface
{
    protected string $endpoint;
    protected string $password;

    public function __construct()
    {
        $this->endpoint = config('services.point_nexus.endpoint') . '/api/point-nexus/shopify/orders/book';
        $this->password = config('services.point_nexus.password');
    }

    public function process(OrderItem $item): bool
    {
        $item->update(['status' => OrderStatus::PROCESSING->value]);
        $item->fresh();
        $item->load('order');

        $params = [
            'product_id'  => (int) $item->product_id,
            'currency'    => $item->currency_code,
            'price'       => (float) $item->sales_price_including_vat,
            'vat'         => (float) ($item->vat_amount ?? 0),
            'countryCode' => $item->order->country_code,
            'IP'          => $item->order->consumer_ip,
            'order_id_2game' => $item->order->order_id_2game
        ];

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . '?password=' . $this->password, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Response']['ErrorCode'] ?? null) === '0') {
                    $value           = $data['Response']['Value'] ?? [];
                    $bookingId       = $value['booking_id'] ?? null;
                    $clientRequestId = $value['client_request_id'] ?? null;

                    Log::channel('order')->info('PointNexus order booked successfully', [
                        'order_item_id'    => $item->id,
                        'booking_id'       => $bookingId,
                        'client_request_id' => $clientRequestId,
                        'response'         => json_encode($data),
                        'params'           => json_encode($params),
                        'endpoint'         => $this->endpoint,
                    ]);

                    $item->update([
                        'status'             => OrderStatus::COMPLETED->value,
                        'key_id'             => $bookingId,
                        'retailer_order_id'  => $clientRequestId,
                    ]);

                    return true;
                }

                $errorMsg = $data['Response']['ErrorMsg'] ?? 'Unknown error';

                $item->update([
                    'status'        => OrderStatus::FAILED->value,
                    'failed_reason' => $errorMsg,
                ]);

                Log::channel('order')->error('PointNexus order booking failed (API error)', [
                    'order_item_id' => $item->id,
                    'response'      => $data,
                    'params'        => $params,
                    'endpoint'      => $this->endpoint,
                ]);

                return false;
            }

            // HTTP-level failure
            $item->update([
                'status'        => OrderStatus::FAILED->value,
                'failed_reason' => $response->body(),
            ]);

            Log::channel('order')->error('PointNexus order HTTP error', [
                'order_item_id' => $item->id,
                'status'        => $response->status(),
                'body'          => $response->body(),
                'params'        => $params,
                'endpoint'      => $this->endpoint,
            ]);

            return false;
        } catch (Exception $e) {
            Log::channel('order')->error('PointNexus order exception', [
                'order_item_id' => $item->id,
                'error'         => $e->getMessage(),
                'params'        => $params,
                'endpoint'      => $this->endpoint,
            ]);

            $item->update([
                'status'        => OrderStatus::FAILED->value,
                'failed_reason' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
