<?php

namespace App\Services\OrderProcessing;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\OrderItemFailure;
use App\Notifications\OrderProcessingFailed;
use App\Services\Cms\CurrencyExchange;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderProcessor
{
    /**
     * Resolve the correct processor for an order item based on its source.
     *
     * source 1 → Ztorm
     * source 2 → Incomm
     * source 3 → PointNexus
     */
    private function resolveProcessor(OrderItem $item): ?OrderItemProcessorInterface
    {
        return match ((int) $item->source) {
            1 => new ZtormProcessor(),
            2 => new IncommProcessor(),
            3 => new PointNexusProcessor(),
            4 => new GenbaProcessor(),
            default => null,
        };
    }

    /**
     * Process order items and return per-item responses.
     */
    public function processOrder(Order $order): array
    {
        if (!$order) {
            throw new Exception("Order instance is null");
        }

        $order->update(['status' => OrderStatus::PROCESSING->value]);

        $results = [];

        foreach ($order->items as $item) {
            $processor = $this->resolveProcessor($item);

            if ($processor) {
                $success = $processor->process($item);
                $item->update([
                    'status' => $success ? OrderStatus::COMPLETED->value : OrderStatus::FAILED->value,
                ]);

                $failedReason = json_decode($item->failed_reason, true);

                if (isset($failedReason['message'])) {
                    unset($failedReason['message']);
                }

                $results[] = [
                    'item_id'       => $item->id,
                    'product_id'    => $item->product_id,
                    'source'        => $item->source,
                    'status'        => $item->status,
                    'message'       => $success
                        ? "Item processed successfully"
                        : "Item processing failed",
                    'failed_reason' => $failedReason ?? null,
                    'key_id'        => $item->key_id,
                ];
            } else {
                $item->update(['status' => OrderStatus::FAILED->value]);

                $results[] = [
                    'item_id'    => $item->id,
                    'product_id' => $item->product_id,
                    'source'     => $item->source,
                    'status'     => OrderStatus::FAILED->value,
                    'message'    => "Invalid source, no processor found",
                ];
            }
        }

        $order->fresh();

        $totalItems     = $order->items->count();
        $completedItems = $order->items
            ->where('status', OrderStatus::COMPLETED->value)
            ->count();

        if ($completedItems === $totalItems) {
            $order->update(['status' => OrderStatus::COMPLETED->value]);
        } elseif ($completedItems > 0) {
            $order->update(['status' => OrderStatus::PARTIALLY_COMPLETED->value]);
        } else {
            $order->update(['status' => OrderStatus::FAILED->value]);
        }

        $hasFailedItems = OrderItem::where([
            'order_id' => $order->id,
            'status'   => OrderStatus::FAILED,
        ])->count();

        if ($hasFailedItems) {
            try {
                (new OrderAlertReceiver())->notify(
                    new OrderProcessingFailed($order)
                );
            } catch (Throwable $e) {
                Log::error('Failed to send order failure report email', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // Dispatch currency conversion after processing
        try {
            self::convertToEuro($order->id);
        } catch (Throwable $e) {
            Log::error("Currency conversion failed for order {$order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $keyIds = [];
        foreach ($results as $row) {
            $keyIds[$row['product_id']] = $row['key_id'] ?? 'Order processing failed';
        }

        return [
            'orderId' => $order->order_id_2game,
            'KeyIds'  => $keyIds,
        ];
    }


    public function retryFailedItems(Order $order): array
    {
        $results = [];

        $failedItems = $order->items()
            ->where('status', OrderStatus::FAILED->value)
            ->get();

        foreach ($failedItems as $item) {
            $processor = $this->resolveProcessor($item);

            if (!$processor) {
                continue;
            }

            $previousState = [
                'status'             => $item->status,
                'failed_reason'      => $item->failed_reason,
                'key_id'             => $item->key_id,
                'retailer_order_id'  => $item->retailer_order_id,
            ];

            $success = $processor->process($item);

            if ($success) {
                OrderItemFailure::create([
                    'order_id'                  => $order->id,
                    'order_item_id'             => $item->id,
                    'retry_attempt'             => $order->retry_count,
                    'previous_status'           => $previousState['status'],
                    'key_id'                    => $previousState['key_id'],
                    'retailer_order_id'         => $previousState['retailer_order_id'],
                    'failed_reason'             => $previousState['failed_reason'],
                    'sales_price_including_vat' => $item->sales_price_including_vat,
                    'sales_price_excluding_vat' => $item->sales_price_excluding_vat,
                    'discount_amount'           => $item->discount_amount,
                    'vat_amount'                => $item->vat_amount,
                    'giftcard_amount'           => $item->giftcard_amount,
                    'row_total'                 => $item->row_total,
                    'currency_code'             => $item->currency_code,
                    'source'                    => $item->source,
                ]);
            }

            $item->update([
                'status' => $success
                    ? OrderStatus::COMPLETED->value
                    : OrderStatus::FAILED->value,
            ]);

            $hasFailedItems = OrderItem::where([
                'order_id' => $order->id,
                'status'   => OrderStatus::FAILED,
            ])->count();

            if ($hasFailedItems) {
                try {
                    (new OrderAlertReceiver())->notify(
                        new OrderProcessingFailed($order)
                    );
                } catch (Throwable $e) {
                    Log::error('Failed to send order failure report email', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            $results[] = [
                'item_id'    => $item->id,
                'product_id' => $item->product_id,
                'status'     => $item->status,
                'message'    => $success
                    ? 'Item retried successfully'
                    : 'Item retry failed',
                'key_id'     => $item->key_id,
            ];
        }

        // Recalculate order status
        $this->recalculateOrderStatus($order);

        $keyIds = [];
        foreach ($results as $row) {
            $keyIds[$row['product_id']] = $row['key_id'] ?? 'Order processing failed';
        }

        return [
            'orderId' => $order->order_id_2game,
            'KeyIds'  => $keyIds,
        ];
    }


    private function recalculateOrderStatus(Order $order): void
    {
        $order = Order::with('items')->findOrFail($order->id);

        $total     = $order->items->count();
        $completed = $order->items
            ->where('status', OrderStatus::COMPLETED->value)
            ->count();

        if ($completed === $total) {
            $order->update(['status' => OrderStatus::COMPLETED->value]);
        } elseif ($completed > 0) {
            $order->update(['status' => OrderStatus::PARTIALLY_COMPLETED->value]);
        } else {
            $order->update(['status' => OrderStatus::FAILED->value]);
        }
    }


    /**
     * Convert all order items for a given order to Euro.
     */
    public static function convertToEuro(int $orderId): void
    {
        $order = Order::where('id', $orderId)->first();
        if (!$order) {
            return;
        }

        $orderItems = OrderItem::where('order_id', $orderId)->get();

        if ($orderItems->isEmpty()) {
            return;
        }

        $rateForOrder = CurrencyExchange::getRate($orderItems->first()->currency_code, 'EUR');
        Log::info('currency_rate', ['order_id' => $orderId, 'rate' => $rateForOrder]);

        foreach ($orderItems as $item) {
            $rate = CurrencyExchange::getRate($item->currency_code, 'EUR');

            Log::info('currency_rate', ['order_item_id' => $item->id, 'rate' => $rate]);

            if ($rate <= 0) {
                continue;
            }

            $item->sales_price_including_vat_eur = $item->sales_price_including_vat * $rate;
            $item->sales_price_excluding_vat_eur = $item->sales_price_excluding_vat * $rate;
            $item->discount_amount_eur           = $item->discount_amount * $rate;
            $item->vat_amount_eur                = $item->vat_amount * $rate;
            $item->giftcard_amount_eur           = $item->giftcard_amount * $rate;
            $item->row_total_eur                 = $item->row_total * $rate;
            $item->save();
        }

        $order->payment_fee_eur      = $order->payment_fee * $rateForOrder;
        $order->gateway_fee_eur      = $order->gateway_fee * $rateForOrder;
        $order->shopify_plus_fee_eur = $order->shopify_plus_fee * $rateForOrder;
        $order->save();
    }
}