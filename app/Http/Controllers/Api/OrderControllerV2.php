<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\ProcessOrderRequest;
use App\Http\Requests\ProcessParentSkuOrderRequest;
use App\Http\Requests\ProcessStorefrontOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\SkuMapping;
use App\Services\OrderKeyRedeemProcessor\OrderKeyRedeemProcessor;
use App\Services\OrderProcessing\OrderProcessor;
use App\Services\SkuMapping\ParentSkuResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class OrderControllerV2 extends Controller
{
    public function processOrder(ProcessOrderRequest $request)
    {
        $validated = $request->validated();

        Log::channel('order')->info('Processing new order request', [
            'order_id_2game' => $validated['order_id_2game'],
            'email'          => $validated['email'],
            'items_count'    => count($validated['items']),
            'payload'        => $validated,
        ]);

        try {
            $order = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($order) {
                return $this->retryFailedOrder($order);
            }

            $order = $this->createOrderWithItems($validated);

            if (!$order) {
                Log::channel('order')->error('Order creation failed', ['payload' => $validated]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '500',
                        'ErrorMsg'  => 'Failed to create order',
                        'Value'     => null,
                    ]
                ], 500);
            }

            Log::channel('order')->info('Order created successfully', ['order_id' => $order->id]);

            $results = (new OrderProcessor())->processOrder($order);

            $order->fresh();

            if ($order->status === OrderStatus::COMPLETED) {
                Log::channel('order')->info('Order processed to COMPLETED', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            if ($order->status === OrderStatus::PARTIALLY_COMPLETED) {
                Log::channel('order')->warning('Order processed PARTIALLY', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed Partially',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            Log::channel('order')->error('Order processing failed', [
                'order_id' => $order->id,
                'status'   => $order->status,
            ]);

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => 'Order Processing failed',
                    'Value'     => $results,
                ]
            ], 422);
        } catch (Exception $e) {
            Log::channel('order')->critical('Order processing exception', [
                'order_id' => $validated['order_id_2game'] ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to process order: ' . $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }

    public function processMappedOrder(ProcessParentSkuOrderRequest $request)
    {
        $validated = $request->validated();

        // ── Parent SKU Resolution ─────────────────────────────────────────────
        try {
            $validated = (new ParentSkuResolver())->transformPayload($validated);


            Log::channel('order')->info('[SkuResolver] Payload transformation complete', [
                'order_id_2game'  => $validated['order_id_2game'],
                'resolved_items'  => collect($validated['items'])->map(fn($i) => [
                    'product_id'           => $i['product_id'],
                    'sku_mapping_snapshot' => $i['_sku_mapping_snapshot'] ?? null,
                ])->all(),
                'full_payload' => $validated,
            ]);
        } catch (RuntimeException $e) {
            Log::channel('order')->error('[SkuResolver] Payload transformation failed', [
                'order_id_2game' => $validated['order_id_2game'] ?? null,
                'error'          => $e->getMessage(),
            ]);


            // ── Persist the failed order so it can be retried later ──────────
            $existingOrder = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($existingOrder) {
                $existingOrder->update([
                    'last_failure_reason' => $e->getMessage(),
                    'status'         => OrderStatus::VALIDATION_FAILED,
                ]);
            } else {
                $this->createFailedOrder($validated, $e->getMessage());
            }

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => $e->getMessage(),
                    'Value'     => null,
                ]
            ], 422);
        }
        // ─────────────────────────────────────────────────────────────────────

        Log::channel('order')->info('[MappedOrder] Processing mapped order request', [
            'order_id_2game' => $validated['order_id_2game'],
            'email'          => $validated['email'],
            'items_count'    => count($validated['items']),
            'payload'        => $validated,
        ]);

        try {
            $order = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($order) {
                return $this->retryFailedOrder($order);
            }

            $order = $this->createOrderWithItems($validated);

            if (!$order) {
                Log::channel('order')->error('[MappedOrder] Order creation failed', ['payload' => $validated]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '500',
                        'ErrorMsg'  => 'Failed to create order',
                        'Value'     => null,
                    ]
                ], 500);
            }

            Log::channel('order')->info('[MappedOrder] Order created successfully', ['order_id' => $order->id]);

            $results = (new OrderProcessor())->processOrder($order);
            $results = $this->remapResultsToParentSku($results);

            $order->fresh();

            if ($order->status === OrderStatus::COMPLETED) {
                Log::channel('order')->info('[MappedOrder] Order processed to COMPLETED', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            if ($order->status === OrderStatus::PARTIALLY_COMPLETED) {
                Log::channel('order')->warning('[MappedOrder] Order processed PARTIALLY', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed Partially',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            Log::channel('order')->error('[MappedOrder] Order processing failed', [
                'order_id' => $order->id,
                'status'   => $order->status,
            ]);

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => 'Order Processing failed',
                    'Value'     => $results,
                ]
            ], 422);
        } catch (Exception $e) {
            Log::channel('order')->critical('[MappedOrder] Order processing exception', [
                'order_id' => $validated['order_id_2game'] ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to process order: ' . $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }

    public function processStorefrontOrder(ProcessStorefrontOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $validated = (new ParentSkuResolver())->transformStorefrontPayload($validated);


            Log::channel('order')->info('[StorefrontOrder] Payload transformation complete', [
                'order_id_2game' => $validated['order_id_2game'],
                'resolved_items' => collect($validated['items'])->map(fn($i) => [
                    'product_id'           => $i['product_id'],
                    'sku_mapping_snapshot' => $i['_sku_mapping_snapshot'] ?? null,
                ])->all(),
            ]);
        } catch (RuntimeException $e) {
            Log::channel('order')->error('[StorefrontOrder] Payload transformation failed', [
                'order_id_2game' => $validated['order_id_2game'] ?? null,
                'error'          => $e->getMessage(),
            ]);

            $existingOrder = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($existingOrder) {
                $existingOrder->update([
                    'last_failure_reason' => $e->getMessage(),
                    'status'              => OrderStatus::VALIDATION_FAILED,
                ]);
            } else {
                $this->createFailedOrder($validated, $e->getMessage());
            }

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => $e->getMessage(),
                    'Value'     => null,
                ]
            ], 422);
        }

        Log::channel('order')->info('[StorefrontOrder] Processing request', [
            'order_id_2game' => $validated['order_id_2game'],
            'email'          => $validated['email'],
            'items_count'    => count($validated['items']),
        ]);

        try {
            $order = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($order) {
                return $this->retryFailedOrder($order);
            }

            $order = $this->createOrderWithItems($validated);

            if (!$order) {
                Log::channel('order')->error('[StorefrontOrder] Order creation failed', ['payload' => $validated]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '500',
                        'ErrorMsg'  => 'Failed to create order',
                        'Value'     => null,
                    ]
                ], 500);
            }

            $results = (new OrderProcessor())->processOrder($order);
            $results = $this->remapResultsToParentSku($results);

            $order->fresh();

            if ($order->status === OrderStatus::COMPLETED) {
                Log::channel('order')->info('[StorefrontOrder] Order COMPLETED', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            if ($order->status === OrderStatus::PARTIALLY_COMPLETED) {
                Log::channel('order')->warning('[StorefrontOrder] Order PARTIALLY completed', ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed Partially',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            Log::channel('order')->error('[StorefrontOrder] Order processing failed', [
                'order_id' => $order->id,
                'status'   => $order->status,
            ]);

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => 'Order Processing failed',
                    'Value'     => $results,
                ]
            ], 422);
        } catch (Exception $e) {
            Log::channel('order')->critical('[StorefrontOrder] Exception', [
                'order_id' => $validated['order_id_2game'] ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to process order: ' . $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }

    private function createFailedOrder(array $validated, string $reason): ?Order
    {
        try {
            return DB::transaction(function () use ($validated, $reason) {
                $order = Order::create([
                    'order_id_2game'        => $validated['order_id_2game'],
                    'consumer_ip'           => $validated['consumer_ip'] ?? null,
                    'payment_method'        => $validated['payment_method'],
                    'payment_fee'           => $validated['payment_fee'],
                    'gateway_fee'           => $validated['gatewayFee'] ?? 0,
                    'shopify_plus_fee'      => $validated['shopifyPlusFee'] ?? 0,
                    'sale_transaction_id'   => $validated['sale_transaction_id'] ?? null,
                    'subtotal'              => $validated['subtotal'],
                    'grand_total'           => $validated['grand_total'],
                    'total_qty_ordered'     => $validated['total_qty_ordered'],
                    'total_amount_paid'     => $validated['total_amount_paid'],
                    'total_amount_ordered'  => $validated['total_amount_ordered'],
                    'total_discount_amount' => $validated['total_discount_amount'] ?? 0,
                    'total_price'           => $validated['total_price'],
                    'country_code'          => $validated['country_code'],
                    'email'                 => $validated['email'],
                    'status'               => OrderStatus::VALIDATION_FAILED,
                    'last_failure_reason'       => $reason,
                ]);

                // No order items created — child SKU cannot be resolved at this point.
                // Full payload logged for manual inspection and retry support.
                Log::channel('order')->info('[MappedOrder] Failed order persisted — full payload stored for retry', [
                    'order_id'       => $order->id,
                    'order_id_2game' => $order->order_id_2game,
                    'failure_reason' => $reason,
                    'payload'        => $validated,
                ]);

                return $order;
            });
        } catch (Throwable $e) {
            dd($e->getMessage());
            Log::channel('order')->critical('[MappedOrder] Could not persist failed order', [
                'order_id_2game' => $validated['order_id_2game'] ?? null,
                'error'          => $e->getMessage(),
                'payload'        => $validated,
            ]);
            return null;
        }
    }

    private function remapResultsToParentSku(array $results): array
    {
        if (!isset($results['KeyIds'])) {
            return $results;
        }

        $mappedKeyIds = [];

        foreach ($results['KeyIds'] as $childSku => $keyId) {
            $mapping = SkuMapping::whereJsonContains('child_skus', (int)$childSku)->first();

            $parentSku = $mapping?->parent_sku ?? $childSku;

            $mappedKeyIds[$parentSku] = $keyId;
        }

        $results['KeyIds'] = $mappedKeyIds;

        return $results;
    }


    private function retryFailedOrder(Order $order)
    {
        Log::channel('order')->info('Retrying existing failed order', [
            'order_id'    => $order->id,
            'retry_count' => $order->retry_count,
            'status'      => $order->status,
        ]);

        return DB::transaction(function () use ($order) {

            $order->refresh();

            if ($order->status === OrderStatus::VALIDATION_FAILED) {
                Log::channel('order')->warning('Retry blocked: order has validation failure', [
                    'order_id'       => $order->id,
                    'order_id_2game' => $order->order_id_2game,
                    'failure_reason' => $order->failure_reason,
                ]);

                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '422',
                        'ErrorMsg'  => 'Order cannot be retried: ' . $order->failure_reason,
                        'Value'     => null,
                    ]
                ], 422);
            }

            if (!in_array($order->status, [
                OrderStatus::FAILED,
                OrderStatus::PARTIALLY_COMPLETED,
            ])) {
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '409',
                        'ErrorMsg'  => 'Order is not eligible for retry',
                        'Value'     => null,
                    ]
                ], 409);
            }

            $order->update([
                'retry_count'   => $order->retry_count + 1,
                'last_retry_at' => now(),
                'status'        => OrderStatus::PROCESSING->value,
            ]);

            Log::channel('order')->warning('Retrying failed order', [
                'order_id'    => $order->id,
                'retry_count' => $order->retry_count,
            ]);

            $results = (new OrderProcessor())->retryFailedItems($order);

            $results = $this->remapResultsToParentSku($results);

            $order->refresh();

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'Order retry processed',
                    'Value'     => $results,
                ]
            ], 200);
        });
    }



    public function redeemKey($key_id, $order_id)
    {
        Log::channel('order')->info('Redeem key request received', [
            'key_id'   => $key_id,
            'order_id' => $order_id,
        ]);

        try {
            $orderItem = OrderItem::select('order_items.*', 'orders.order_id_2game')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where([
                    'order_items.key_id'    => $key_id,
                    'orders.order_id_2game' => $order_id,
                ])
                ->first();

            if (!$orderItem) {
                Log::channel('order')->warning('Redeem key failed: no matching order item', [
                    'key_id'   => $key_id,
                    'order_id' => $order_id,
                ]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '404',
                        'ErrorMsg'  => "No order item found for key_id: {$key_id}, order_id: {$order_id}",
                        'Value'     => null,
                    ]
                ], 404);
            }

            Log::channel('order')->info('Redeem key order item found', [
                'order_item_id'  => $orderItem->id,
                'order_id'       => $orderItem->order_id,
                'order_id_2game' => $orderItem->order_id_2game,
                'key_id'         => $key_id,
            ]);

            $result = (new OrderKeyRedeemProcessor())->processKeyRedeem($orderItem);

            Log::channel('order')->info('Redeem key processed successfully', [
                'order_item_id' => $orderItem->id,
                'key_id'        => $key_id,
                'order_id'      => $order_id,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::channel('order')->error('Redeem key exception', [
                'key_id'   => $key_id,
                'order_id' => $order_id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to redeem key: ' . $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }


    /**
     * Handle order and order items creation in a transaction.
     * sku_mapping_snapshot is persisted here when a parent SKU was resolved.
     */
    private function createOrderWithItems(array $validated): Order
    {
        return DB::transaction(function () use ($validated) {

            $order = Order::create([
                'order_id_2game'        => $validated['order_id_2game'],
                'consumer_ip'           => $validated['consumer_ip'] ?? null,
                'payment_method'        => $validated['payment_method'],
                'payment_fee'           => $validated['payment_fee'],
                'gateway_fee'           => $validated['gatewayFee'] ?? 0,
                'shopify_plus_fee'      => $validated['shopifyPlusFee'] ?? 0,
                'sale_transaction_id'   => $validated['sale_transaction_id'] ?? null,
                'subtotal'              => $validated['subtotal'],
                'grand_total'           => $validated['grand_total'],
                'total_qty_ordered'     => $validated['total_qty_ordered'],
                'total_amount_paid'     => $validated['total_amount_paid'],
                'total_amount_ordered'  => $validated['total_amount_ordered'],
                'total_discount_amount' => $validated['total_discount_amount'] ?? 0,
                'total_price'           => $validated['total_price'],
                'country_code'          => $validated['country_code'],
                'email'                 => $validated['email'],
            ]);

            if (!$order) {
                return null;
            }

            $orderItems = collect($validated['items'])->map(function ($item) {
                $product = Product::where('sku', $item['product_id'])->select(['source'])->first();

                return [
                    'product_id'                => $item['product_id'],
                    'sku_mapping_snapshot'      => $item['_sku_mapping_snapshot'] ?? null,
                    'sales_price_including_vat' => $item['sales_price_including_vat'],
                    'sales_price_excluding_vat' => $item['sales_price_excluding_vat'],
                    'discount_amount'           => $item['discount_amount'] ?? 0,
                    'vat_amount'                => $item['vat_amount'],
                    'source'                    => $product->source,
                    'currency_code'             => $item['currency_code'],
                    'giftcard_amount'           => $item['giftcard_amount'],
                    'row_total'                 => $item['row_total'],
                ];
            })->toArray();

            $order->items()->createMany($orderItems);

            if (!empty($validated['transactions'])) {
                foreach ($validated['transactions'] as $transaction) {
                    $transactionCreatedAt = isset($transaction['createdAt'])
                        ? Carbon::parse($transaction['createdAt'])->format('Y-m-d H:i:s')
                        : null;

                    $order->transactions()->updateOrCreate(
                        ['transaction_id' => $transaction['id']],
                        [
                            'gateway'                => $transaction['gateway'] ?? null,
                            'kind'                   => $transaction['kind'] ?? null,
                            'status'                 => $transaction['status'] ?? null,
                            'transaction_created_at' => $transactionCreatedAt,
                        ]
                    );
                }
            }

            return $order->load('items', 'transactions');
        });
    }



    public function cancelOrder(Request $request)
    {
        Log::channel('order')->info('Cancel order request received', [
            'payload' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::channel('order')->warning('Cancel order validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => $validator->errors()->first('order_id'),
                    'Value'     => null,
                ]
            ], 422);
        }

        $order_id = $request->query('order_id');

        try {
            $order = Order::where('order_id_2game', $order_id)->first();

            if (!$order) {
                Log::channel('order')->warning('Cancel order failed: order not found', [
                    'order_id' => $order_id,
                ]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '404',
                        'ErrorMsg'  => "No order found for order_id: {$order_id}",
                        'Value'     => null,
                    ]
                ], 404);
            }

            Log::channel('order')->info('Order found for cancellation', [
                'order_id'       => $order->id,
                'order_id_2game' => $order->order_id_2game,
            ]);

            $cancellableItems = $order->items()->where('is_cancelled', 0)->get();

            if ($cancellableItems->isEmpty()) {
                Log::channel('order')->warning('No cancellable items found', [
                    'order_id' => $order_id,
                ]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '400',
                        'ErrorMsg'  => "No cancellable items found for order_id: {$order_id}",
                        'Value'     => null,
                    ]
                ], 400);
            }

            if ($cancellableItems->contains(fn($item) => !is_null($item->redeemed_at))) {
                Log::channel('order')->warning('Cancel order denied: items already redeemed', [
                    'order_id' => $order_id,
                ]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '400',
                        'ErrorMsg'  => "Cannot cancel order that have already been redeemed some item for order_id: {$order_id}",
                        'Value'     => null,
                    ]
                ], 400);
            }

            $responseArr = [];
            foreach ($cancellableItems as $item) {
                $response                       = $this->cancelItemBySource($item);
                $responseArr[$item->product_id] = $response;

                Log::channel('order')->info('Cancel attempt for item', [
                    'order_id'   => $order_id,
                    'item_id'    => $item->id,
                    'product_id' => $item->product_id,
                    'response'   => $response,
                ]);
            }

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => "Order with order_id: {$order_id} successfully processed for cancellation",
                    'Value'     => $responseArr,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::channel('order')->error('Cancel order exception', [
                'order_id' => $order_id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }



    public function cancelOrderV2(CancelOrderRequest $request)
    {
        Log::channel('order')->info('Cancel order v2 request received', [
            'payload' => $request->validated(),
        ]);

        $orderId2Game = $request->input('order_id');
        $transactions = $request->input('transactions', []);

        try {
            return DB::transaction(function () use ($orderId2Game, $transactions) {

                $order = Order::where('order_id_2game', $orderId2Game)
                    ->with(['items' => fn($q) => $q->where('is_cancelled', 0)])
                    ->first();

                if (!$order) {
                    return response()->json([
                        'Response' => [
                            'Version'   => '1.0',
                            'ErrorCode' => '404',
                            'ErrorMsg'  => "No order found for order_id: {$orderId2Game}",
                            'Value'     => null,
                        ]
                    ], 404);
                }

                $items = $order->items;

                if ($items->isEmpty()) {
                    return response()->json([
                        'Response' => [
                            'Version'   => '1.0',
                            'ErrorCode' => '400',
                            'ErrorMsg'  => "No cancellable items found for order_id: {$orderId2Game}",
                            'Value'     => null,
                        ]
                    ], 400);
                }

                if ($items->contains(fn($item) => $item->redeemed_at !== null)) {
                    return response()->json([
                        'Response' => [
                            'Version'   => '1.0',
                            'ErrorCode' => '400',
                            'ErrorMsg'  => "Cannot cancel order with already redeemed items for order_id: {$orderId2Game}",
                            'Value'     => null,
                        ]
                    ], 400);
                }

                if (!empty($transactions)) {
                    $this->storeOrderTransactions(
                        $order,
                        $transactions,
                        OrderTransaction::TYPE_CANCEL
                    );
                }

                $responseArr = [];
                foreach ($items as $item) {
                    $responseArr[$item->product_id] = $this->cancelItemBySource($item);
                }

                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => "Order with order_id: {$orderId2Game} successfully processed for cancellation",
                        'Value'     => [
                            'items'        => $responseArr,
                            'transactions' => $transactions,
                        ],
                    ]
                ], 200);
            });
        } catch (\Throwable $e) {
            Log::channel('order')->error('Cancel order v2 exception', [
                'order_id' => $orderId2Game,
                'error'    => $e->getMessage(),
            ]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Internal server error',
                    'Value'     => null,
                ]
            ], 500);
        }
    }


    private function storeOrderTransactions(Order $order, array $transactions, string $type = OrderTransaction::TYPE_ORDER): void
    {
        foreach ($transactions as $transaction) {
            OrderTransaction::updateOrCreate(
                [
                    'order_id'       => $order->id,
                    'transaction_id' => $transaction['id'],
                    'type'           => $type,
                ],
                [
                    'gateway'                => $transaction['gateway'] ?? null,
                    'kind'                   => $transaction['kind'] ?? null,
                    'status'                 => $transaction['status'] ?? null,
                    'transaction_created_at' => isset($transaction['createdAt'])
                        ? Carbon::parse($transaction['createdAt'])
                        : null,
                ]
            );
        }
    }


    private function cancelItemBySource($item)
    {
        return match ((int) $item->source) {
            1 => $this->cancelZtormOrder($item),
            2 => $this->cancelIncommOrder($item),
            3 => $this->cancelPointNexusOrder($item),
            default => 'Unknown source, cannot cancel',
        };
    }


    private function cancelZtormOrder($item)
    {
        try {
            $retailerOrderId = $item->retailer_order_id;

            if (!isset($retailerOrderId)) {
                return 'Failed to cancel order, retailer order id not found';
            }

            $password = config('services.ztorm.password');
            $endpoint = config('services.ztorm.endpoint') . '/api/1.0/shopify/cancel';

            $response = Http::get($endpoint, [
                'password'          => $password,
                'retailer_order_id' => $retailerOrderId,
            ]);

            if ($response->failed()) {
                Log::channel('order')->error("Ztorm cancel API failed for item {$item->id}", [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'endpoint' => $endpoint,
                ]);
                throw new \Exception("Failed to cancel Ztorm order for item {$item->id}");
            }

            $json = $response->json();

            if (
                isset($json['Response']['ErrorCode']) &&
                $json['Response']['ErrorCode'] === '0' &&
                isset($json['Response']['ErrorMsg']) &&
                $json['Response']['ErrorMsg'] === 'OK'
            ) {
                Log::channel('order')->info("Ztorm cancel API success for item {$item->id}", [
                    'response' => $json,
                    'endpoint' => $endpoint,
                ]);

                $item->is_cancelled = 1;
                $item->status       = OrderStatus::CANCELLED;
                if (!$item->cancelled_at) {
                    $item->cancelled_at = now();
                }
                $item->save();

                return 'Order Cancelled Successfully';
            }

            Log::channel('order')->error("Ztorm cancel API unexpected response for item {$item->id}", [
                'response' => $json,
                'endpoint' => $endpoint,
            ]);

            return 'Failed to cancel order';
        } catch (\Exception $e) {
            Log::channel('order')->error("Ztorm cancel API exception for item {$item->id}: " . $e->getMessage());
            return 'Failed to cancel order';
        }
    }


    private function cancelIncommOrder($item)
    {
        try {
            $externalPartnerLoadId = $item->retailer_order_id;
            $orderId               = $item->order->order_id_2game;
            $password              = config('services.incomm.password');

            if (!isset($externalPartnerLoadId)) {
                return 'Failed to cancel order, retailer order id not found';
            }

            $url = config('services.incomm.endpoint')
                . '/order/shopify/cancel/'
                . rawurlencode($externalPartnerLoadId)
                . '/'
                . rawurlencode($orderId);

            $response = Http::get($url, ['password' => $password]);

            if ($response->failed()) {
                Log::channel('order')->error("Incomm cancel API failed for item {$item->id}", [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'endpoint' => $url,
                ]);
                return 'Failed to cancel order';
            }

            Log::channel('order')->info("Incomm cancel API success for item {$item->id}", [
                'response' => $response->json(),
                'endpoint' => $url,
            ]);

            $item->is_cancelled = 1;
            $item->status       = OrderStatus::CANCELLED;
            if (!$item->cancelled_at) {
                $item->cancelled_at = now();
            }
            $item->save();

            return 'Order Cancelled Successfully';
        } catch (\Exception $e) {
            Log::channel('order')->error("Incomm cancel API exception for item {$item->id}: " . $e->getMessage());
            return 'Failed to cancel order';
        }
    }


    private function cancelPointNexusOrder($item)
    {
        try {
            $bookingId       = $item->key_id;
            $clientRequestId = $item->retailer_order_id;

            if (!isset($bookingId) || !isset($clientRequestId)) {
                Log::channel('order')->warning("PointNexus cancel failed: missing booking_id or client_request_id", [
                    'item_id'           => $item->id,
                    'key_id'            => $bookingId,
                    'retailer_order_id' => $clientRequestId,
                ]);
                return 'Failed to cancel order: booking_id or client_request_id not found';
            }

            $password = config('services.point_nexus.password');
            $endpoint = config('services.point_nexus.endpoint') . '/api/point-nexus/shopify/orders/cancel';

            $payload = [
                'booking_id'        => (int) $bookingId,
                'client_request_id' => $clientRequestId,
            ];

            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($endpoint . '?password=' . $password, $payload);

            if ($response->failed()) {
                Log::channel('order')->error("PointNexus cancel API failed for item {$item->id}", [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                ]);
                return 'Failed to cancel order';
            }

            $json = $response->json();

            if (isset($json['Response']['ErrorCode']) && $json['Response']['ErrorCode'] === '0') {
                Log::channel('order')->info("PointNexus cancel API success for item {$item->id}", [
                    'response' => $json,
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                ]);

                $item->is_cancelled = 1;
                $item->status       = OrderStatus::CANCELLED;
                if (!$item->cancelled_at) {
                    $item->cancelled_at = now();
                }
                $item->save();

                return 'Order Cancelled Successfully';
            }

            Log::channel('order')->error("PointNexus cancel API unexpected response for item {$item->id}", [
                'response' => $json,
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]);

            return 'Failed to cancel order';
        } catch (Exception $e) {
            Log::channel('order')->error("PointNexus cancel API exception for item {$item->id}: " . $e->getMessage());
            return 'Failed to cancel order';
        }
    }

    public function redeemOrder(string $order_id)
    {
        Log::channel('order')->info('Order redeem request received', [
            'order_id' => $order_id,
        ]);

        try {
            $order = Order::where('order_id_2game', $order_id)
                ->with(['items.product.media'])
                ->first();

            if (!$order) {
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '404',
                        'ErrorMsg'  => "Order not found: {$order_id}",
                        'Value'     => null,
                    ]
                ], 404);
            }

            $itemsResponse = [];

            foreach ($order->items as $item) {

                $product = $item->product;

                $responseItem = [
                    'id'       => (string) $item->id,
                    'slug'     => $product?->seo_url_name ?? (string) $item->product_id,
                    'name'     => $product?->name ?? '',
                    'currency' => $item->currency_code,
                    'price'    => (float) $item->sales_price_including_vat,
                    'image'    => $product?->media()->where('is_main', 1)->first()?->url,
                    'type'     => $product?->product_type ?? 'DIGITAL',
                    'key'      => null,
                ];

                try {

                    // CASE 1: No key → return as-is (same behavior conceptually as redeemKey)
                    if (!$item->key_id) {
                        $responseItem['status'] = $this->mapStatus($item->status);
                        $itemsResponse[] = $responseItem;
                        continue;
                    }

                    // CASE 2: Key exists → FOLLOW EXACT redeemKey flow
                    $result = (new OrderKeyRedeemProcessor())->processKeyRedeem($item);

                    $data = json_decode(json_encode($result->getData(true)), true);


                    // extract key from processor result (same as redeemKey response source)
                    $key = $data['Response']['Value']['Licensekey'] ?? $data['Response']['ErrorMsg'];

                    $responseItem['key'] = $key;
                    $responseItem['status'] = 'COMPLETED';

                    // always update redeemed_at (idempotent tracking)
                    $item->redeemed_at = now();
                    $item->save();
                } catch (Throwable $e) {

                    Log::channel('order')->error('Item redeem failed in order flow', [
                        'order_item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);

                    $responseItem['status'] = $this->mapStatus($item->status);
                    $responseItem['key'] = null;
                }

                $itemsResponse[] = $responseItem;
            }

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'Order processed successfully',
                    'Value'     => [
                        'id'        => $order->order_id_2game,
                        'status'    => $order->status,
                        'createdAt' => $order->created_at?->toISOString(),
                        'items'     => $itemsResponse,
                    ],
                ]
            ]);
        } catch (Throwable $e) {

            Log::channel('order')->error('Order redeem exception', [
                'order_id' => $order_id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to process order: ' . $e->getMessage(),
                    'Value'     => null,
                ]
            ], 500);
        }
    }

    public function orderDetails(string $orderId)
    {
        Log::channel('order')->info('[OrderDetails] Fetching order details', [
            'order_id_2game' => $orderId,
        ]);

        try {
            $order = Order::with([
                'items.product',
            ])
                ->where('order_id_2game', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '404',
                        'ErrorMsg'  => 'Order not found',
                        'Value'     => null,
                    ]
                ], 404);
            }

            $items = $order->items->map(function ($item) {

                $product = $item->product;

                return [
                    'id'       => (string) $item->id,
                    'slug'     => $product?->seo_url_name,
                    'name'     => $product?->name,
                    'currency' => $item->currency ?? 'BRL',
                    'price'    => (float) $item->sales_price_including_vat,
                    'image'    => $product?->media()->where('is_main', 1)->first()?->url,
                    'type'     => $product?->product_type,
                    'key_id'      => $item->key_id ?? null,
                ];
            });

            return response()->json([
                'id'        => $order->order_id_2game,
                'status'    => $this->mapStatus($order->status),
                'createdAt' => $order->created_at?->toISOString(),
                'items'     => $items,
            ]);
        } catch (Exception $e) {

            Log::channel('order')->critical('[OrderDetails] Failed fetching order', [
                'order_id_2game' => $orderId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '500',
                    'ErrorMsg'  => 'Failed to fetch order details',
                    'Value'     => null,
                ]
            ], 500);
        }
    }

    private function mapStatus(OrderStatus $status): string
    {
        return $status->value;
    }
}
