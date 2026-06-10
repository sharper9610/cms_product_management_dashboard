<?php

namespace App\Services\OrderProcessing;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cms\CurrencyExchange;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZtormProcessor implements OrderItemProcessorInterface
{
    protected string $endpoint;
    protected string $password;

    public function __construct()
    {
        $this->endpoint = config('services.ztorm.endpoint') . '/api/1.0/shopify/order';
        $this->password = config('services.ztorm.password');
    }

    // public function process(OrderItem $item): bool
    // {
    //     $item->update(['status' => OrderStatus::PROCESSING->value]);
    //     $item->load('order');

    //     if (!$item->order) {
    //         Log::channel('order')->error('ZtormProcessor: order relation is null', [
    //             'order_item_id' => $item->id,
    //         ]);
    //         $item->update([
    //             'status'        => OrderStatus::FAILED->value,
    //             'failed_reason' => 'Order relation could not be loaded.',
    //         ]);
    //         return false;
    //     }

    //     $params = [
    //         'password'              => $this->password,
    //         'product_id'            => (int) $item->product_id,
    //         'currency'              => $item->currency_code,
    //         'country_code'          => $item->order->country_code,
    //         'product_price_ex_vat'  => (float) $item->sales_price_excluding_vat,
    //         'product_price_inc_vat' => (float) $item->sales_price_including_vat,
    //         'price'                 => (float) $item->sales_price_including_vat,
    //         'row_total'             => (float) $item->row_total,
    //         'vat_percent'           => (float) $item->vat_percent ?? 0,
    //         'discount_amount'       => (float) ($item->discount_amount ?? 0),
    //         'giftcard_amount'       => (float) ($item->giftcard_amount ?? 0),
    //         'vat_amount'            => (float) ($item->vat_amount ?? 0),
    //         'ip'                    => $item->order->consumer_ip,
    //         'email'                 => $item->order->email,
    //     ];


    //     try {
    //         $response = Http::withHeaders([
    //             'Accept' => 'application/json',
    //             'Content-Type' => 'application/json',
    //         ])->get($this->endpoint, $params);

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             if (($data['Response']['ErrorCode']) === "0") {
    //                 $orderContent = $this->extractOrderContent($data);
    //                 $customerProductId = $orderContent['CustomerProducts']['CustomerProductID'] ?? null;
    //                 $RetailerOrderID = $data['Response']['Value']['Order']['RetailerOrderID'] ?? null;

    //                 Log::channel('order')->info('Ztorm order processed successfully', [
    //                     'order_item_id' => $item->id,
    //                     'response'      => json_encode($data),
    //                     'params'        => json_encode($params),
    //                     'endpoint'      => $this->endpoint,
    //                 ]);

    //                 $item->update([
    //                     'status'  => OrderStatus::COMPLETED->value,
    //                     'key_id'  => $customerProductId,
    //                     'retailer_order_id' => $RetailerOrderID,
    //                 ]);

    //                 try {
    //                     $this->updateCostPrice($item);
    //                 } catch (Throwable $e) {
    //                     Log::error('Failed to update cost price', [
    //                         'order_item_id' => $item->id ?? null,
    //                         'error' => $e->getMessage(),
    //                         'trace' => $e->getTraceAsString(),
    //                     ]);
    //                 }

    //                 return true;
    //             }

    //             // Failed because API returned error
    //             $errorMsg = $data['Response']['ErrorMsg'] ?? 'Unknown error';
    //             $item->update([
    //                 'status'        => OrderStatus::FAILED->value,
    //                 'failed_reason' => $errorMsg,
    //             ]);

    //             Log::channel('order')->error("Ztorm order failed", [
    //                 'order_item_id' => $item->id,
    //                 'response'      => $data,
    //                 'params'        => $params,
    //                 'endpoint'      => $this->endpoint,
    //             ]);

    //             return false;
    //         }

    //         // HTTP-level failure
    //         $item->update([
    //             'status'        => OrderStatus::FAILED->value,
    //             'failed_reason' => $response->body(),
    //         ]);

    //         Log::channel('order')->error("Ztorm order HTTP error", [
    //             'order_item_id' => $item->id,
    //             'status'        => $response->status(),
    //             'body'          => $response->body(),
    //             'params'        => $params,
    //             'endpoint'      => $this->endpoint,
    //         ]);

    //         return false;
    //     } catch (Exception $e) {
    //         Log::channel('order')->error("Ztorm order exception", [
    //             'order_item_id' => $item->id,
    //             'error'         => $e->getMessage(),
    //             'params'        => $params,
    //             'endpoint'      => $this->endpoint,
    //         ]);

    //         $item->update([
    //             'status'        => OrderStatus::FAILED->value,
    //             'failed_reason' => $e->getMessage(),
    //         ]);

    //         return false;
    //     }
    // }

    public function process(OrderItem $item): bool
    {
        $item->update(['status' => OrderStatus::PROCESSING->value]);

        $order = Order::where('id', $item->order_id)->first();

        if (!$order) {
            Log::channel('order')->error('ZtormProcessor: order not found by order id', [
                'order_item_id'  => $item->id,
                'order_id' => $item->order_id,
            ]);
            $item->update([
                'status'        => OrderStatus::FAILED->value,
                'failed_reason' => 'Order could not be found.',
            ]);
            return false;
        }

        $params = [
            'password'              => $this->password,
            'product_id'            => (int) $item->product_id,
            'currency'              => $item->currency_code,
            'country_code'          => $order->country_code,
            'product_price_ex_vat'  => (float) $item->sales_price_excluding_vat,
            'product_price_inc_vat' => (float) $item->sales_price_including_vat,
            'price'                 => (float) $item->sales_price_including_vat,
            'row_total'             => (float) $item->row_total,
            'vat_percent'           => (float) $item->vat_percent ?? 0,
            'discount_amount'       => (float) ($item->discount_amount ?? 0),
            'giftcard_amount'       => (float) ($item->giftcard_amount ?? 0),
            'vat_amount'            => (float) ($item->vat_amount ?? 0),
            'ip'                    => $order->consumer_ip,
            'email'                 => $order->email,
        ];

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->get($this->endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Response']['ErrorCode']) === "0") {
                    $orderContent      = $this->extractOrderContent($data);
                    $customerProductId = $orderContent['CustomerProducts']['CustomerProductID'] ?? null;
                    $RetailerOrderID   = $data['Response']['Value']['Order']['RetailerOrderID'] ?? null;

                    Log::channel('order')->info('Ztorm order processed successfully', [
                        'order_item_id' => $item->id,
                        'response'      => json_encode($data),
                        'params'        => json_encode($params),
                        'endpoint'      => $this->endpoint,
                    ]);

                    $item->update([
                        'status'            => OrderStatus::COMPLETED->value,
                        'key_id'            => $customerProductId,
                        'retailer_order_id' => $RetailerOrderID,
                    ]);

                    try {
                        $this->updateCostPrice($item);
                    } catch (Throwable $e) {
                        Log::error('Failed to update cost price', [
                            'order_item_id' => $item->id ?? null,
                            'error'         => $e->getMessage(),
                            'trace'         => $e->getTraceAsString(),
                        ]);
                    }

                    return true;
                }

                // Failed because API returned error
                $errorMsg = $data['Response']['ErrorMsg'] ?? 'Unknown error';
                $item->update([
                    'status'        => OrderStatus::FAILED->value,
                    'failed_reason' => $errorMsg,
                ]);

                Log::channel('order')->error("Ztorm order failed", [
                    'order_item_id' => $item->id,
                    'response'      => $data,
                    'params'        => $params,
                    'endpoint'      => $this->endpoint,
                ]);

                return false;
            }

            $item->update([
                'status'        => OrderStatus::FAILED->value,
                'failed_reason' => $response->body(),
            ]);

            Log::channel('order')->error("Ztorm order HTTP error", [
                'order_item_id' => $item->id,
                'status'        => $response->status(),
                'body'          => $response->body(),
                'params'        => $params,
                'endpoint'      => $this->endpoint,
            ]);

            return false;
        } catch (Exception $e) {
            Log::channel('order')->error("Ztorm order exception", [
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

    /**
     * Normalizes the OrderContent payload regardless of whether the provider
     * returns it as a sequential array (Instock) or a direct object (Ztorm).
     *
     * Instock:  Contents.OrderContent[0].CustomerProducts...
     * Ztorm:    Contents.OrderContent.CustomerProducts...
     */
    private function extractOrderContent(array $data): array
    {
        $orderContent = $data['Response']['Value']['Order']['Contents']['OrderContent'] ?? [];

        // If it's a sequential list (e.g. Instock), take the first element.
        // If it's already a direct object (e.g. Ztorm), use it as-is.
        return array_is_list($orderContent) ? ($orderContent[0] ?? []) : $orderContent;
    }

    private function updateCostPrice(OrderItem $item)
    {
        $item = $item->fresh();
        $RetailerOrderID = $item->retailer_order_id;

        $cmsOrder = DB::connection('mysql_cms')
            ->table('basket_gbp as b')
            ->join('basket_item_gbp as bi', 'b.BasketID', '=', 'bi.BasketID')
            ->select(
                'b.RetailerOrderID',
                'b.BasketID',
                'b.Store',
                'b.CountryCode',
                'b.CountryRedirectCurrency',
                'bi.CostEstimate',
                'bi.CostEstimateEUR',
                'bi.Currency'
            )
            ->where('b.RetailerOrderID', $RetailerOrderID)
            ->first();

        if (! $cmsOrder) {
            return;
        }

        try {
            $costEstimate = $this->getLocalCost($cmsOrder);
        } catch (\Exception $e) {
            $costEstimate = 0;
            Log::error('ZtormProcessor::updateCostPrice ' . $e->getMessage());
        }

        return $item->update([
            'cost_price' => $costEstimate,
            'cost_price_euro' => $cmsOrder->CostEstimateEUR ?? 0,
        ]);
    }

    private function getLocalCost($cmsOrder)
    {
        // dump($cmsOrder->Store, $cmsOrder->Currency, $cmsOrder->CountryRedirectCurrency);

        // not redirect
        if (is_null($cmsOrder->CountryRedirectCurrency)) {
            if ($cmsOrder->Store === $cmsOrder->Currency) {
                // echo "native \n";
                return $cmsOrder->CostEstimate;
            }

            if ($cmsOrder->Store !== $cmsOrder->Currency) {
                // echo "converted \n";
                $rate = CurrencyExchange::getRate($cmsOrder->Currency, $cmsOrder->Store);
                return round($cmsOrder->CostEstimate * $rate, 2);
            }
        }

        // redirect
        else {
            if ($cmsOrder->Store === $cmsOrder->Currency && $cmsOrder->Store === $cmsOrder->CountryRedirectCurrency) {
                // echo "native \n";
                return $cmsOrder->CostEstimate;
            }

            if ($cmsOrder->Store === $cmsOrder->Currency && $cmsOrder->Store !== $cmsOrder->CountryRedirectCurrency) {
                // echo "native redirct \n";
                $rate = CurrencyExchange::getRate($cmsOrder->Currency, $cmsOrder->CountryRedirectCurrency);
                return round($cmsOrder->CostEstimate * $rate, 2);
            }
        }

        return 0;
    }
}
