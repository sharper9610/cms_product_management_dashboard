<?php

namespace App\Services\OrderKeyRedeemProcessor;

use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class OrderKeyRedeemProcessor
{
    protected string $incommEndpoint;
    protected string $incommPassword;

    protected string $ztormEndpoint;
    protected string $ztormPassword;

    protected string $pointNexusEndpoint;
    protected string $pointNexusPassword;

    protected string $genbaEndpoint;
    protected string $genbaPassword;

    public function __construct()
    {
        $this->incommEndpoint = config('services.incomm.endpoint') . '/order/shopify/key/redeem';
        $this->incommPassword = config('services.incomm.password');

        $this->ztormEndpoint = config('services.ztorm.endpoint') . '/api/1.0/shopify/get_install_key';
        $this->ztormPassword = config('services.ztorm.password');

        $this->pointNexusEndpoint = config('services.point_nexus.endpoint') . '/api/point-nexus/shopify/orders/confirm';
        $this->pointNexusPassword = config('services.point_nexus.password');

        $this->genbaEndpoint = config('services.genba.endpoint') . '/api/1.0/order/shopify/key/redeem';
        $this->genbaPassword = config('services.genba.password');
    }
    /**
     * Redeem a key for a given order item
     *
     * @param OrderItem $orderItem
     * @return array
     */
    public function processKeyRedeem(OrderItem $orderItem)
    {
        try {
            if (!$orderItem) {
                return $this->errorResponse('Order item not found', 404);
            }

            if ($orderItem->is_cancelled) {
                return $this->errorResponse('Can not redeem a cancelled product', 400);
            }

            return match ($orderItem->source) {
                1 => $this->redeemZtormKey($orderItem),
                2 => $this->redeemIncommKey($orderItem),
                3 => $this->redeemPointNexusKey($orderItem),
                4 => $this->redeemGenbaKey($orderItem),
                default => $this->errorResponse('No processor found for this source', 404),
            };
        } catch (Exception $e) {
            Log::channel('order')->error("Failed to redeem order key", [
                'order_item_id' => $orderItem->id ?? null,
                'key_id' => $orderItem->key_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to redeem key: ' . $e->getMessage(), 500);
        }
    }

    protected function redeemPointNexusKey(OrderItem $orderItem): JsonResponse
    {
        // booking_id      → stored in key_id
        // client_request_id → stored in retailer_order_id
        $bookingId       = $orderItem->key_id;
        $clientRequestId = $orderItem->retailer_order_id;

        $payload = [
            'booking_id'        => (int) $bookingId,
            'client_request_id' => $clientRequestId,
        ];

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->pointNexusEndpoint . '?password=' . $this->pointNexusPassword, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Response']['ErrorCode'] ?? null) === '0') {
                    $value = $data['Response']['Value'] ?? [];
                    $keys  = $value['key'] ?? [];

                    // Format all returned keys into a single readable string
                    $licenseKey = collect($keys)
                        ->pluck('value')
                        ->implode(', ');

                    if (!$orderItem->redeemed_at) {
                        $orderItem->update(['redeemed_at' => now()]);
                    }

                    Log::channel('order')->info("PointNexus key redeemed successfully", [
                        'order_item_id'    => $orderItem->id,
                        'key_id'           => $bookingId,
                        'client_request_id' => $clientRequestId,
                        'operation_id'     => $value['operation_id'] ?? null,
                        'endpoint'         => $this->pointNexusEndpoint,
                        'payload'          => $payload,
                    ]);

                    return response()->json([
                        'Response' => [
                            'Version'   => '1.0',
                            'ErrorCode' => '0',
                            'ErrorMsg'  => 'key redeemed successfully',
                            'Value'     => [
                                'Licensekey' => $licenseKey,
                                'key_id'     => $bookingId,
                            ],
                        ]
                    ], 200);
                }

                // API-level error
                $errorMsg = $data['Response']['ErrorMsg'] ?? 'Unknown error';

                Log::channel('order')->error("PointNexus key redemption API error", [
                    'order_item_id' => $orderItem->id,
                    'key_id'        => $bookingId,
                    'response'      => $data,
                    'endpoint'      => $this->pointNexusEndpoint,
                    'payload'       => $payload,
                ]);

                return $this->errorResponse('PointNexus redemption failed: ' . $errorMsg, 500);
            }

            // HTTP-level failure
            Log::channel('order')->error("PointNexus key redemption HTTP error", [
                'order_item_id' => $orderItem->id,
                'key_id'        => $bookingId,
                'status'        => $response->status(),
                'body'          => $response->body(),
                'endpoint'      => $this->pointNexusEndpoint,
                'payload'       => $payload,
            ]);

            return $this->errorResponse(
                'PointNexus redemption failed: ' . $response->body(),
                $response->status()
            );
        } catch (Exception $e) {
            Log::channel('order')->error("PointNexus key redemption exception", [
                'order_item_id' => $orderItem->id,
                'key_id'        => $bookingId,
                'error'         => $e->getMessage(),
                'endpoint'      => $this->pointNexusEndpoint,
                'payload'       => $payload,
            ]);

            return $this->errorResponse(
                'Failed to redeem PointNexus key: ' . $e->getMessage(),
                500
            );
        }
    }

    protected function redeemGenbaKey(OrderItem $orderItem): JsonResponse
    {
        $keyId           = $orderItem->key_id;
        $retailerOrderId = $orderItem->retailer_order_id;

        $payload = [
            'keyId'           => $keyId,
            'retailerOrderId' => $retailerOrderId,
        ];

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->genbaEndpoint . '?password=' . $this->pointNexusPassword, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Response']['ErrorCode'] ?? null) === '0') {
                    $value = $data['Response']['Value'] ?? [];
                    $keys  = $value['keys'] ?? [];

                    // Build license key string — single: "key1val", bundle: "key1val, key2val"
                    $licenseKey = collect($keys)
                        ->map(fn($entry) => $entry['key'])
                        ->implode(', ');

                    if (!$orderItem->redeemed_at) {
                        $orderItem->update(['redeemed_at' => now()]);
                    }

                    Log::channel('order')->info('Genba key redeemed successfully', [
                        'order_item_id'    => $orderItem->id,
                        'key_id'           => $keyId,
                        'retailer_order_id' => $retailerOrderId,
                        'genba_order_id'   => $value['genbaOrderId'] ?? null,
                        'key_count'        => count($keys),
                        'endpoint'         => $this->genbaEndpoint,
                        'payload'          => $payload,
                    ]);

                    return response()->json([
                        'Response' => [
                            'Version'   => '1.0',
                            'ErrorCode' => '0',
                            'ErrorMsg'  => 'Key redeemed successfully',
                            'Value'     => [
                                'Licensekey' => $licenseKey,
                                'key_id'     => $keyId,
                            ],
                        ],
                    ], 200);
                }

                $errorMsg = $data['Response']['ErrorMsg'] ?? 'Unknown error';

                Log::channel('order')->error('Genba key redemption API error', [
                    'order_item_id'    => $orderItem->id,
                    'key_id'           => $keyId,
                    'retailer_order_id' => $retailerOrderId,
                    'response'         => $data,
                    'endpoint'         => $this->genbaEndpoint,
                    'payload'          => $payload,
                ]);

                return $this->errorResponse('Genba redemption failed: ' . $errorMsg, 500);
            }

            Log::channel('order')->error('Genba key redemption HTTP error', [
                'order_item_id'    => $orderItem->id,
                'key_id'           => $keyId,
                'retailer_order_id' => $retailerOrderId,
                'status'           => $response->status(),
                'body'             => $response->body(),
                'endpoint'         => $this->genbaEndpoint,
                'payload'          => $payload,
            ]);

            return $this->errorResponse(
                'Genba redemption failed: ' . $response->body(),
                $response->status(),
            );
        } catch (Exception $e) {
            Log::channel('order')->error('Genba key redemption exception', [
                'order_item_id'    => $orderItem->id,
                'key_id'           => $keyId,
                'retailer_order_id' => $retailerOrderId,
                'error'            => $e->getMessage(),
                'endpoint'         => $this->genbaEndpoint,
                'payload'          => $payload,
            ]);

            return $this->errorResponse(
                'Failed to redeem Genba key: ' . $e->getMessage(),
                500,
            );
        }
    }

    protected function redeemIncommKey(OrderItem $orderItem)
    {
        $response = Http::get("{$this->incommEndpoint}/{$orderItem->key_id}/{$orderItem->retailer_order_id}", [
            'password' => $this->incommPassword
        ]);

        if ($response->successful()) {
            $data = $response->json('keyObj');

            // check if redeemed_at is null , then update it
            if (!$orderItem->redeemed_at) {
                $orderItem->update(['redeemed_at' => now()]);
            }

            Log::channel('order')->info("Incomm key redeemed successfully", [
                'order_item_id' => $orderItem->id,
                'key_id' => $orderItem->key_id,
                'data' => $response->body(),
                'endpoint'      => $this->incommEndpoint,
                'params'        => [
                    'key_id' => $orderItem->key_id,
                    'retailer_order_id' => $orderItem->retailer_order_id,
                ],
            ]);

            $combined = "Magic Link: {$data['magic_link']}, Expire Date: {$data['expire_date']}, Card Number: {$data['card_number']}, Card Password: {$data['card_password']}, Redemption Code: {$data['redemption_code']}";


            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '0',
                    'ErrorMsg'  => 'key redeemed successfully',
                    'Value'     => [
                        'Licensekey'    => $combined,
                        'key_id'        => $orderItem->key_id,
                    ],
                ]
            ], 200);
        }

        // add error log
        Log::channel('order')->error("Incomm key redemption failed", [
            'order_item_id' => $orderItem->id,
            'key_id' => $orderItem->key_id,
            'status' => $response->status(),
            'body' => $response->body(),
            'endpoint'      => $this->incommEndpoint,
            'params'        => [
                'key_id' => $orderItem->key_id,
                'retailer_order_id' => $orderItem->retailer_order_id,

            ],
        ]);

        return $this->errorResponse('Key redemption failed: ' . $response->body(), 500);
    }

    protected function redeemZtormKey(OrderItem $orderItem)
    {
        try {

            $response = Http::get($this->ztormEndpoint, [
                'password' => $this->ztormPassword,
                'customer_product_id' => $orderItem->key_id,
                'retailer_order_id' => $orderItem->retailer_order_id,
            ]);

            if ($response->successful()) {
                $data = $response->json('Response.Value.InstallKey');

                // check if redeemed_at is null , then update it
                if (!$orderItem->redeemed_at) {
                    $orderItem->update(['redeemed_at' => now()]);
                }

                Log::channel('order')->info("Ztorm key redeemed successfully", [
                    'order_item_id' => $orderItem->id,
                    'key_id' => $orderItem->key_id,
                    'data' => $response->body(),
                    'endpoint'      => $this->ztormEndpoint,
                    'params'        => [
                        'customer_product_id' => $orderItem->key_id,
                        'retailer_order_id' => $orderItem->retailer_order_id,
                    ],
                ]);

                $combinedKey = $data['Value'] ?? '';

                return response()->json([
                    'Response' => [
                        'Version'   => '1.0',
                        'ErrorCode' => '0',
                        'ErrorMsg'  => 'Key redeemed successfully',
                        'Value'     => [
                            'Licensekey' => $combinedKey,
                            'key_id'     => $orderItem->key_id,
                        ],
                    ]
                ], 200);
            }

            // add error log
            Log::channel('order')->error("Incomm key redemption failed", [
                'order_item_id' => $orderItem->id,
                'key_id' => $orderItem->key_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint'      => $this->ztormEndpoint,
                'params'        => [
                    'key_id' => $orderItem->key_id,
                    'retailer_order_id' => $orderItem->retailer_order_id,
                ],
            ]);


            // If API call fails
            return $this->errorResponse(
                'Ztorm redemption failed: ' . $response->body(),
                $response->status(),
            );
        } catch (Exception $e) {
            Log::channel('order')->error("Failed to redeem Ztorm key", [
                'order_item_id' => $orderItem->id,
                'key_id' => $orderItem->key_id,
                'error' => $e->getMessage(),
                'endpoint'      => $this->ztormEndpoint,
                'params'        => [
                    'customer_product_id' => $orderItem->key_id,
                    'retailer_order_id' => $orderItem->retailer_order_id,
                ],
            ]);

            return $this->errorResponse(
                'Failed to redeem Ztorm key: ' . $e->getMessage(),
                500
            );
        }
    }


    protected function errorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => (string) $statusCode,
                'ErrorMsg'  => $message,
                'Value'     => null,
            ]
        ], $statusCode);
    }
}
