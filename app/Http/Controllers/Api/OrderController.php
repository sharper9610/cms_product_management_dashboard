<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\ProcessOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Services\OrderKeyRedeemProcessor\OrderKeyRedeemProcessor;
use App\Services\OrderProcessing\OrderProcessor;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     operationId="processOrder",
     *     tags={"Orders"},
     *     summary="Create and process a new order",
     *     description="Processes an order and returns the order ID and purchased key IDs. Supports full and partial processing.",
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description=" password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={
     *             "order_id_2game","total_price","consumer_ip","payment_method",
     *             "payment_fee","subtotal","grand_total","total_qty_ordered",
     *             "total_amount_paid","total_amount_ordered","country_code","email","items"
     *         },
     *         @OA\Property(property="order_id_2game", type="string" ,format="string", example="ORD8835ffggf88hh888"),
     *         @OA\Property(property="total_price", type="number", format="float", minimum=0, example=50.0),
     *         @OA\Property(property="consumer_ip", type="string", format="ipv4", example="192.168.1.50"),
     *         @OA\Property(property="payment_method", type="string", format="string", example="paypal"),
     *         @OA\Property(property="payment_fee", type="number", format="float", minimum=0, example=2.99),
     *         @OA\Property(property="subtotal", type="number", format="float", minimum=0, example=47.50),
     *         @OA\Property(property="grand_total", type="number", format="float", minimum=0, example=50.49),
     *         @OA\Property(property="total_qty_ordered", type="integer", minimum=0, example=1),
     *         @OA\Property(property="total_amount_paid", type="number", format="float", minimum=0, example=50.49),
     *         @OA\Property(property="total_amount_ordered", type="number", format="float", minimum=0, example=50.49),
     *         @OA\Property(property="total_discount_amount", type="number", format="float", nullable=true, minimum=0, example=0),
     *
     *         @OA\Property(
     *             property="country_code",
     *             type="string",
     *             minLength=2,
     *             maxLength=2,
     *             example="BR",
     *             description="ISO 3166-1 alpha-2 country code. Supported countries and their allowed currencies:
     *                 BR → BRL
     *                 MX → MXN
     *                 CL → CLP
     *                 CO → COP
     *                 PE → PEN
     *                 UY → UYU
     *                 CR → CRC"
     *         ),
     *
     *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *
     *         @OA\Property(property="items", type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"product_id","sales_price_including_vat","sales_price_excluding_vat","vat_amount","currency_code"},
     *                 @OA\Property(property="product_id", type="integer", minimum=1, example=4000000),
     *                 @OA\Property(property="sales_price_including_vat", type="number", format="float", minimum=0, example=50.0),
     *                 @OA\Property(property="sales_price_excluding_vat", type="number", format="float", minimum=0, example=47.5),
     *                 @OA\Property(property="discount_amount", type="number", format="float", nullable=true, minimum=0, example=0),
     *                 @OA\Property(property="vat_amount", type="number", format="float", minimum=0, example=2.5),
     *                 @OA\Property(property="giftcard_amount", type="number", format="float", minimum=0, example=0),
     *                 @OA\Property(property="row_total", type="number", format="float", minimum=0, example=50.0),
     *                 @OA\Property(
     *                     property="currency_code",
     *                     type="string",
     *                     minLength=3,
     *                     maxLength=3,
     *                     example="BRL",
     *                     enum={"BRL","MXN","CLP","COP","PEN","UYU","CRC"},
     *                     description="ISO 4217 currency code. Must match the allowed currency for the selected country_code."
     *                 )
     *             )
     *         )
     *     )
     * ),
     *

     *     @OA\Response(
     *         response=200,
     *         description="Order Successfully Processed or Partially Processed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Order Successfully Processed or Partially Processed"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="orderId", type="string", example="ORD-SHOPIFY-007"),
     *                     @OA\Property(
     *                         property="KeyIds",
     *                         type="object",
     *                         description="Mapping of product_id to key_id or failure message",
     *                         @OA\AdditionalProperties(
     *                             oneOf={
     *                                 @OA\Schema(type="integer", example=45698),
     *                                 @OA\Schema(type="string", example="Order processing failed")
     *                             }
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *
     *    @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="401"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unauthorized"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Order Payload Validation Failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="422"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Order Payload Validation Failed"),
     *                 @OA\Property(property="Value", type="array",
     *                     @OA\Items(type="string", example="Product ID 4000000 does not exist.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error / Failed to process order",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Failed to process order: Exception message here"),
     *                 @OA\Property(property="Value", type="null", nullable=true)
     *             )
     *         )
     *     )
     * )
     */

    public function processOrder(ProcessOrderRequest $request)
    {
        $validated = $request->validated();

        Log::channel('order')->info("Processing new order request", [
            'order_id_2game' => $validated['order_id_2game'],
            'email'          => $validated['email'],
            'items_count'    => count($validated['items']),
            'payload'        => $validated
        ]);


        try {
            $order = Order::where('order_id_2game', $validated['order_id_2game'])->first();

            if ($order) {
                return $this->retryFailedOrder($order);
            }

            $order = $this->createOrderWithItems($validated);

            if (!$order) {
                Log::channel('order')->error("Order creation failed", ['payload' => $validated]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '500',
                        'ErrorMsg'  => 'Failed to create order',
                        'Value'     => null,
                    ]
                ], 500);
            }

            Log::channel('order')->info("Order created successfully", ['order_id' => $order->id]);

            $results = (new OrderProcessor())->processOrder($order);



            $order->fresh();

            if ($order->status === OrderStatus::COMPLETED) {
                Log::channel('order')->info("Order processed to COMPLETED", ['order_id' => $order->id]);
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
                Log::channel('order')->warning("Order processed PARTIALLY", ['order_id' => $order->id]);
                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Order Successfully Processed Partially',
                        'Value'     => $results,
                    ]
                ], 200);
            }

            Log::channel('order')->error("Order processing failed", ['order_id' => $order->id, 'status' => $order->status]);
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => 'Order Processing failed',
                    'Value'     => $results,
                ]
            ], 422);
        } catch (Exception $e) {
            Log::channel('order')->critical("Order processing exception", [
                'order_id' => $validated['order_id_2game'] ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString()
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


    private function retryFailedOrder(Order $order)
    {
        //Add log of this retry attempt
        Log::channel('order')->info("Retrying existing failed order", [
            'order_id'    => $order->id,
            'retry_count' => $order->retry_count,
            'status'      => $order->status,
        ]);

        // Prevent race conditions
        return DB::transaction(function () use ($order) {

            $order->refresh();

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

            // if ($order->retry_count >= 3) {
            //     return response()->json([
            //         'Response' => [
            //             'Version'   => '1.0',
            //             'ErrorCode' => '429',
            //             'ErrorMsg'  => 'Retry limit exceeded',
            //             'Value'     => null,
            //         ]
            //     ], 429);
            // }

            // Mark retry attempt
            $order->update([
                'retry_count'  => $order->retry_count + 1,
                'last_retry_at' => now(),
                'status'       => OrderStatus::PROCESSING->value,
            ]);

            Log::channel('order')->warning('Retrying failed order', [
                'order_id'    => $order->id,
                'retry_count' => $order->retry_count,
            ]);

            $results = (new OrderProcessor())->retryFailedItems($order);

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


    /**
     * @OA\Get(
     *     path="/api/orders/redeem-key/{key_id}/{order_id}",
     *     operationId="redeemOrderKey",
     *     tags={"Orders"},
     *     summary="Redeem a key for an order item",
     *     description="Redeem a license key for a specific order item. Supports Ztorm and Incomm sources. Requires password authentication.",
     *     @OA\Parameter(
     *         name="key_id",
     *         in="path",
     *         description="The key ID to redeem",
     *         required=true,
     *         @OA\Schema(type="integer", example=12345)
     *     ),
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="The order ID (order_id_2game) associated with the key in order response",
     *         required=true,
     *         @OA\Schema(type="string", example="ORDER-SHOPIFY-007")
     *     ),
     *    @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password for API authentication",
     *         required=true,
     *         @OA\Schema(type="string", example="A9f")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful key redemption",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="Response", type="object",
     *                         @OA\Property(property="Version", type="string", example="1.0"),
     *                         @OA\Property(property="ErrorCode", type="string", example="0"),
     *                         @OA\Property(property="ErrorMsg", type="string", example="key redeemed successfully"),
     *                         @OA\Property(property="Value", type="object",
     *                             @OA\Property(property="Licensekey", type="string", example=""),
     *                             @OA\Property(property="key_id", type="integer", example=12345)
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="Response", type="object",
     *                         @OA\Property(property="Version", type="string", example="1.0"),
     *                         @OA\Property(property="ErrorCode", type="string", example="0"),
     *                         @OA\Property(property="ErrorMsg", type="string", example="key redeemed successfully"),
     *                         @OA\Property(property="Value", type="object",
     *                             @OA\Property(property="Licensekey", type="string", example="Magic Link: ..., Expire Date: ..., Card Number: ..., Card Password: ..., Redemption Code: ..."),
     *                             @OA\Property(property="key_id", type="integer", example=12345)
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Key or order item not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="No order item found for key_id: 12345"),
     *                 @OA\Property(property="Value", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="401"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Unauthorized"),
     *                 @OA\Property(property="Value", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Failed to redeem key: <error message>"),
     *                 @OA\Property(property="Value", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function redeemKey($key_id, $order_id)
    {
        Log::channel('order')->info("Redeem key request received", [
            'key_id'   => $key_id,
            'order_id' => $order_id,
        ]);

        try {
            $orderItem = OrderItem::select('order_items.*', 'orders.order_id_2game')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where([
                    'order_items.key_id' => $key_id,
                    'orders.order_id_2game' => $order_id
                ])
                ->first();

            if (!$orderItem) {
                Log::channel('order')->warning("Redeem key failed: no matching order item", [
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

            Log::channel('order')->info("Redeem key order item found", [
                'order_item_id' => $orderItem->id,
                'order_id'      => $orderItem->order_id,
                'order_id_2game' => $orderItem->order_id_2game,
                'key_id'        => $key_id,
            ]);
            $result = (new OrderKeyRedeemProcessor())->processKeyRedeem($orderItem);

            Log::channel('order')->info("Redeem key processed successfully", [
                'order_item_id' => $orderItem->id,
                'key_id'        => $key_id,
                'order_id'      => $order_id,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::channel('order')->error("Redeem key exception", [
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

    // public function redeemMagentoKey($key_id, $order_id){
    //     Log::channel('order')->info("Magento Redeem key request received", [
    //         'key_id'   => $key_id,
    //         'order_id' => $order_id,
    //     ]);
    // }


    /**
     * Handle order and order items creation in a transaction
     */
    private function createOrderWithItems(array $validated): Order
    {
        return DB::transaction(function () use ($validated) {

            // Store the order
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

            // Create order items
            $orderItems = collect($validated['items'])->map(function ($item) {
                $product = Product::where('sku', $item['product_id'])->select(['source'])->first();
                return [
                    'product_id'                 => $item['product_id'],
                    'sales_price_including_vat'  => $item['sales_price_including_vat'],
                    'sales_price_excluding_vat'  => $item['sales_price_excluding_vat'],
                    'discount_amount'            => $item['discount_amount'] ?? 0,
                    'vat_amount'                 => $item['vat_amount'],
                    'source'                     => $product->source,
                    'currency_code'              => $item['currency_code'],
                    'giftcard_amount'            => $item['giftcard_amount'],
                    'row_total'                  => $item['row_total'],
                ];
            })->toArray();

            $order->items()->createMany($orderItems);

            // Store transactions if present
            if (!empty($validated['transactions'])) {


                foreach ($validated['transactions'] as $transaction) {
                    $transactionCreatedAt = isset($transaction['createdAt'])
                        ? Carbon::parse($transaction['createdAt'])->format('Y-m-d H:i:s')
                        : null;
                    $order->transactions()->updateOrCreate(
                        [
                            'transaction_id' => $transaction['id'],
                        ],
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

    /**
     * @OA\Get(
     *     path="/api/orders/cancel",
     *     summary="Cancel an order",
     *     description="Cancels an order and its cancellable items. Returns a key-value map of product IDs and their cancellation response.",
     *     operationId="cancelOrder",
     *     tags={"Orders"},
     *
     *     @OA\Parameter(
     *         name="order_id",
     *         in="query",
     *         required=true,
     *         description="The 2Game order ID to cancel (URL encoded if it contains reserved characters like #)",
     *         @OA\Schema(type="string", example="%23ee444eeeedd4rrss4wweeed333")
     *     ),
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         required=true,
     *         description="Password used for authentication",
     *         @OA\Schema(type="string", example="mySecretPassword123")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order successfully cancelled (full or partial)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="Response",
     *                 type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="0"),
     *                 @OA\Property(property="ErrorMsg", type="string", nullable=true, example="Order with order_id: ORD123456 successfully cancelled"),
     *                 @OA\Property(
     *                     property="Value",
     *                     type="object",
     *                     description="Mapping of product IDs to cancellation response",
     *                     example={
     *                         "4000000": "Order Cancelled Successfully",
     *                         "55065": "Order Cancelled Successfully"
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="No cancellable items found / Cannot cancel redeemed items",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="Response",
     *                 type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="400"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="No cancellable items found for order_id: ORD123456"),
     *                 @OA\Property(property="Value", type="string", example=null)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="Response",
     *                 type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="404"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="No order found for order_id: ORD123456"),
     *                 @OA\Property(property="Value", type="string", example=null)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (missing or invalid order_id)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="Response",
     *                 type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="422"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="The order_id field is required."),
     *                 @OA\Property(property="Value", type="string", example=null)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="Response",
     *                 type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="string", example="500"),
     *                 @OA\Property(property="ErrorMsg", type="string", example="SQLSTATE[HY000]: General error"),
     *                 @OA\Property(property="Value", type="string", example=null)
     *             )
     *         )
     *     )
     * )
     */
    public function cancelOrder(Request $request)
    {
        Log::channel('order')->info("Cancel order request received", [
            'payload' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::channel('order')->warning("Cancel order validation failed", [
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
                Log::channel('order')->warning("Cancel order failed: order not found", [
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

            Log::channel('order')->info("Order found for cancellation", [
                'order_id' => $order->id,
                'order_id_2game' => $order->order_id_2game,
            ]);


            $cancellableItems = $order->items()
                ->where('is_cancelled', 0)
                ->get();
            if ($cancellableItems->isEmpty()) {
                Log::channel('order')->warning("No cancellable items found", [
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

            // check if any cancellable Items has redeem_at not null then give error response
            if ($cancellableItems->contains(fn($item) => !is_null($item->redeemed_at))) {
                Log::channel('order')->warning("Cancel order denied: items already redeemed", [
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
                $response = $this->cancelItemBySource($item);
                $responseArr[$item->product_id] = $response;
                Log::channel('order')->info("Cancel attempt for item", [
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
            Log::channel('order')->error("Cancel order exception", [
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


    /**
     * @OA\Post(
     *     path="/api/orders/v2/cancel",
     *     summary="Cancel an order",
     *     description="Cancel an order and optionally store transaction data.",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         required=true,
     *         description="Password used for authentication",
     *         @OA\Schema(type="string", example="mySecretPassword123")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(
     *                 property="order_id",
     *                 type="string",
     *                 example="1542",
     *                 description="Order ID (internal 2game order ID)"
     *             ),
     *             @OA\Property(
     *                 property="transactions",
     *                 type="array",
     *                 description="Optional transaction data associated with this cancellation",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="rDlGgxr0OgpZ42NAOG5ZKcczm"),
     *                     @OA\Property(property="gateway", type="string", example="manual"),
     *                     @OA\Property(property="kind", type="string", example="SALE"),
     *                     @OA\Property(property="status", type="string", example="SUCCESS"),
     *                     @OA\Property(property="createdAt", type="string", format="date-time", example="2025-09-15T19:30:13Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order successfully cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="integer", example=0),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Order successfully processed"),
     *                 @OA\Property(property="Value", type="object",
     *                     @OA\Property(property="items", type="object", description="Cancelled items by product_id"),
     *                     @OA\Property(property="transactions", type="array", description="Transactions submitted with this request",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="string", example="rDlGgxr0OgpZ42NAOG5ZKcczm"),
     *                             @OA\Property(property="gateway", type="string", example="manual"),
     *                             @OA\Property(property="kind", type="string", example="SALE"),
     *                             @OA\Property(property="status", type="string", example="SUCCESS"),
     *                             @OA\Property(property="createdAt", type="string", format="date-time", example="2025-09-15T19:30:13Z")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No cancellable items or items already redeemed",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="integer", example=400),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Cannot cancel order with already redeemed items"),
     *                 @OA\Property(property="Value", type="null")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="integer", example=404),
     *                 @OA\Property(property="ErrorMsg", type="string", example="No order found for order_id"),
     *                 @OA\Property(property="Value", type="null")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="Response", type="object",
     *                 @OA\Property(property="Version", type="string", example="1.0"),
     *                 @OA\Property(property="ErrorCode", type="integer", example=500),
     *                 @OA\Property(property="ErrorMsg", type="string", example="Internal server error"),
     *                 @OA\Property(property="Value", type="null")
     *             )
     *         )
     *     )
     * )
     */
    public function cancelOrderV2(CancelOrderRequest $request)
    {
        Log::channel('order')->info('Cancel order request received', [
            'payload' => $request->validated(),
        ]);

        $orderId2Game = $request->input('order_id');
        $transactions = $request->input('transactions', []);

        try {
            return DB::transaction(function () use ($orderId2Game, $transactions) {

                $order = Order::where('order_id_2game', $orderId2Game)
                    ->with(['items' => function ($q) {
                        $q->where('is_cancelled', 0);
                    }])
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
                            'items' => $responseArr,
                            'transactions' => $transactions,
                        ],
                    ]
                ], 200);
            });
        } catch (\Throwable $e) {

            Log::channel('order')->error('Cancel order exception', [
                'order_id' => $orderId2Game,
                'error' => $e->getMessage(),
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


    /**
     * Store transactions for an order in an idempotent way.
     *
     * @param  \App\Models\Order  $order
     * @param  array  $transactions
     * @param  string  $type  // OrderTransaction::TYPE_ORDER or TYPE_CANCEL
     * @return void
     */
    private function storeOrderTransactions($order, array $transactions, string $type = OrderTransaction::TYPE_ORDER): void
    {
        foreach ($transactions as $transaction) {
            OrderTransaction::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction['id'],
                    'type' => $type,
                ],
                [
                    'gateway' => $transaction['gateway'] ?? null,
                    'kind' => $transaction['kind'] ?? null,
                    'status' => $transaction['status'] ?? null,
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
            4 => $this->cancelGenbaOrder($item),
            default => "Unknown source, cannot cancel",
        };
    }

    /**
     * Call Ztorm cancel API
     */
    private function cancelZtormOrder($item)
    {
        try {
            $retailerOrderId = $item->retailer_order_id;
            if (!isset($retailerOrderId)) {
                return "Failed to cancel order, Failed to cancel order, retailer order id not found";
            }
            $password = config('services.ztorm.password');
            $endpoint = config('services.ztorm.endpoint') . '/api/1.0/shopify/cancel';

            $response = Http::get($endpoint, [
                'password' => $password,
                'retailer_order_id' => $retailerOrderId,
            ]);

            if ($response->failed()) {
                Log::channel('order')->error("Ztorm cancel API failed for item {$item->id}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'endpoint' => $endpoint,
                ]);
                throw new \Exception("Failed to cancel Ztorm order for item {$item->id}");
                return "Failed to cancel order";
            }

            $json = $response->json();
            if (
                isset($json['Response']) &&
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
                $item->status = OrderStatus::CANCELLED;
                if (!$item->cancelled_at) {
                    $item->cancelled_at = now();
                }
                $item->save();

                return "Order Cancelled Successfully";
            } else {
                Log::channel('order')->error("Ztorm cancel API returned unexpected response for item {$item->id}", [
                    'response' => $json,
                    'endpoint' => $endpoint,
                ]);
                return "Failed to cancel order";
            }
        } catch (\Exception $e) {
            Log::channel('order')->error("Ztorm cancel API exception for item {$item->id}: " . $e->getMessage());
            return "Failed to cancel order";
        }
    }

    /**
     * Call Incomm cancel API
     */
    private function cancelIncommOrder($item)
    {
        try {
            $externalPartnerLoadId = $item->retailer_order_id;
            $orderId               = $item->order->order_id_2game;
            $password              = config('services.incomm.password');


            if (!isset($externalPartnerLoadId)) {
                return "Failed to cancel order, retailer order id not found";
            }



            $url = config('services.incomm.endpoint') . "/order/shopify/cancel/" . rawurlencode($externalPartnerLoadId) . "/" . rawurlencode($orderId);

            $response = Http::get($url, [
                'password' => $password
            ]);



            if ($response->failed()) {
                Log::channel('order')->error("Incomm cancel API failed for item {$item->id}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'endpoint' => $url,
                ]);
                return "Failed to cancel order";
            }

            Log::channel('order')->info("Incomm cancel API success for item {$item->id}", [
                'response' => $response->json(),
                'endpoint' => $url,
            ]);

            $item->is_cancelled = 1;
            $item->status = OrderStatus::CANCELLED;
            if (!$item->cancelled_at) {
                $item->cancelled_at = now();
            }
            $item->save();

            return "Order Cancelled Successfully";
        } catch (Exception $e) {
            Log::channel('order')->error("Incomm cancel API exception for item {$item->id}: " . $e->getMessage());
            return "Failed to cancel order";
        }
    }

    /**
     * Call Point nexus cancel API
     */
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
                return "Failed to cancel order: booking_id or client_request_id not found";
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
                return "Failed to cancel order";
            }

            $json = $response->json();

            if (
                isset($json['Response']['ErrorCode']) &&
                $json['Response']['ErrorCode'] === '0'
            ) {
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

                return "Order Cancelled Successfully";
            }

            Log::channel('order')->error("PointNexus cancel API returned unexpected response for item {$item->id}", [
                'response' => $json,
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]);

            return "Failed to cancel order";
        } catch (\Exception $e) {
            Log::channel('order')->error("PointNexus cancel API exception for item {$item->id}: " . $e->getMessage());
            return "Failed to cancel order";
        }
    }

    private function cancelGenbaOrder($item)
    {
        try {
            $keyId           = $item->key_id;
            $retailerOrderId = $item->retailer_order_id;

            if (!isset($keyId) || !isset($retailerOrderId)) {
                Log::channel('order')->warning('Genba cancel failed: missing key_id or retailer_order_id', [
                    'item_id'           => $item->id,
                    'key_id'            => $keyId,
                    'retailer_order_id' => $retailerOrderId,
                ]);
                return 'Failed to cancel order: key_id or retailer_order_id not found';
            }

            $password = config('services.genba.password');
            $endpoint =config('services.genba.endpoint') . '/api/1.0/order/shopify/cancel';

            $payload = [
                'retailerOrderId' => $retailerOrderId,
                'Action'          => 'Cancel',
                'Reason'          => 'Cancelled by shopify',
                'ReturnReasonCode' => null,
            ];



            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($endpoint . '?password=' . $password, $payload);



            if ($response->failed()) {
                Log::channel('order')->error("Genba cancel API failed for item {$item->id}", [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                ]);
                return 'Failed to cancel order';
            }

            $json = $response->json();

            if (
                isset($json['Response']['ErrorCode']) &&
                $json['Response']['ErrorCode'] === '0'
            ) {
                Log::channel('order')->info("Genba cancel API success for item {$item->id}", [
                    'genba_order_id'    => $json['Response']['Value']['genbaOrderId'] ?? null,
                    'retailer_order_id' => $retailerOrderId,
                    'response'          => $json,
                    'endpoint'          => $endpoint,
                    'payload'           => $payload,
                ]);

                $item->is_cancelled = 1;
                $item->status       = OrderStatus::CANCELLED;
                if (!$item->cancelled_at) {
                    $item->cancelled_at = now();
                }
                $item->save();

                return 'Order Cancelled Successfully';
            }

            Log::channel('order')->error("Genba cancel API returned unexpected response for item {$item->id}", [
                'response' => $json,
                'endpoint' => $endpoint,
                'payload'  => $payload,
            ]);

            return 'Failed to cancel order';
        } catch (\Exception $e) {
            Log::channel('order')->error("Genba cancel API exception for item {$item->id}: " . $e->getMessage());
            return 'Failed to cancel order';
        }
    }
}
