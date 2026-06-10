<?php

namespace App\Services\OrderProcessing;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenbaProcessor implements OrderItemProcessorInterface
{
    protected string $endpoint;
    protected string $password;

    public function __construct()
    {
        $this->endpoint = config('services.genba.endpoint') . '/api/1.0/order/shopify';
        $this->password = config('services.genba.password');
    }

    // ─── OrderItemProcessorInterface ─────────────────────────────────────────

    public function process(OrderItem $item): bool
    {
        $item->update(['status' => OrderStatus::PROCESSING->value]);
        $item->fresh();

        $params = $this->buildParams($item);


        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . '?password=' . $this->password, $params);

            if ($response->successful()) {
                return $this->handleSuccess($item, $response->json(), $params);
            }

            return $this->handleHttpError($item, $response, $params);
        } catch (Exception $e) {
            return $this->handleException($item, $e, $params);
        }
    }

    // ─── Private — request builder ────────────────────────────────────────────

    private function buildParams(OrderItem $item): array
    {
        return [
            // Product & order identity
            'product_id'               =>  $item->product_id,
            'order_id_2game'           => (string) $item->order->order_id_2game,

            // Buying price — cost_price column; fall back to selling gross if not yet known
            'BuyingPriceAmount'        => (float) ($item->cost_price ?? $item->sales_price_including_vat),
            'BuyingPriceCurrencyCode'  => $item->currency_code,

            // Selling price — maps directly to the two price columns
            'SellingPriceNetAmount'    => (float) $item->sales_price_excluding_vat,
            'SellingPriceGrossAmount'  => (float) $item->sales_price_including_vat,
            'SellingPriceCurrencyCode' => $item->currency_code,

            // Location
            'CountryCode'              => $item->order->country_code,

            // Fee fields — stored in order_items columns
            'vat_amount'               => (float) $item->vat_amount,
            'discount_amount'          => (float) $item->discount_amount,
            'giftcard_amount'          => (float) $item->giftcard_amount,
            'row_total'                => (float) $item->row_total,

            // Order-level fee fields
            'payment_method'           => $item->order->payment_method ?? null,
            'payment_fee'              => isset($item->order->payment_fee)
                ? (float) $item->order->payment_fee
                : null,
        ];
    }

    // ─── Private — response handlers ──────────────────────────────────────────

    private function handleSuccess(OrderItem $item, array $data, array $params): bool
    {
        $rawValue = $data['data'] ?? null;

        $values = is_array($rawValue) && isset($rawValue[0]) ? $rawValue : ($rawValue ? [$rawValue] : []);

        if (empty($values) || !isset($values[0]['keyId'], $values[0]['retailerOrderId'])) {
            $errorMsg = $data['message'] ?? 'Missing keyId or retailerOrderId in Genba response';

            Log::channel('order')->error('Genba order booking failed (unexpected response shape)', [
                'order_item_id' => $item->id,
                'response'      => $data,
                'params'        => $params,
                'endpoint'      => $this->endpoint,
            ]);

            $item->update([
                'status'        => OrderStatus::FAILED->value,
                'failed_reason' => $errorMsg,
            ]);

            return false;
        }

        $value = $values[0];

        Log::channel('order')->info('Genba order booked successfully', [
            'order_item_id'     => $item->id,
            'key_ids'           => array_column($values, 'keyId'),
            'retailer_order_id' => $value['retailerOrderId'],
            'genba_order_id'    => $value['genbaOrderId'] ?? null,
            'cost_price'        => $value['costPrice'] ?? null,
            'cost_price_euro'   => $value['costPriceEuro'] ?? null,
            'bundle_key_count'  => count($values),
            'response'          => json_encode($data),
            'params'            => json_encode($params),
            'endpoint'          => $this->endpoint,
        ]);

        $item->update([
            'status'            => OrderStatus::COMPLETED->value,
            'key_id'            => implode('-', array_column($values, 'keyId')),
            'retailer_order_id' => $value['retailerOrderId'],
            'cost_price'        => isset($value['costPrice'])
                ? (float) $value['costPrice']
                : null,
            'cost_price_euro'   => isset($value['costPriceEuro'])
                ? (float) $value['costPriceEuro']
                : 0.000000,
        ]);

        return true;
    }

    private function handleHttpError(OrderItem $item, $response, array $params): bool
    {
        $body = $response->json() ?? $response->body();

        Log::channel('order')->error('Genba order HTTP error', [
            'order_item_id' => $item->id,
            'status'        => $response->status(),
            'body'          => $body,
            'params'        => $params,
            'endpoint'      => $this->endpoint,
        ]);

        $item->update([
            'status'        => OrderStatus::FAILED->value,
            'failed_reason' => is_array($body)
                ? ($body['message'] ?? json_encode($body))
                : $response->body(),
        ]);

        return false;
    }

    private function handleException(OrderItem $item, Exception $e, array $params): bool
    {
        Log::channel('order')->error('Genba order exception', [
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
