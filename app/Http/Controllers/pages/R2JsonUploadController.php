<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Product;
use App\Services\Json\ProductJsonUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class R2JsonUploadController extends Controller
{
    use HttpResponses;

    protected ProductJsonUploadService $productJsonUploadService;

    public function __construct(ProductJsonUploadService $productJsonUploadService)
    {
        $this->productJsonUploadService = $productJsonUploadService;
    }

    public function index()
    {
        $this->checkPageAccess('r2.json.upload');

        $data['sourceOptions'] = [
            config('services.sources.ztorm', 1) => 'Ztorm',
            config('services.sources.incomm', 2) => 'Incomm',
            config('services.sources.point_nexus', 3) => 'Point Nexus',
            config('services.sources.genba', 4) => 'Genba',
        ];

        return view('content.pages.pages-r2-json-upload', $data);
    }

    public function upload(Request $request)
    {
        $skuString = trim($request->input('sku', ''));
        $ignoreTimestamp = $request->boolean('ignore_timestamp', false);
        $priceSource = $request->filled('source') ? (int) $request->input('source') : null;

        // Determine folder path based on environment
        $folder = config('app.env') === 'production'
            ? 'product-json/v3'
            : 'staging/product-json/v3';

            try {
            if (!empty($skuString)) {
                $skus = array_filter(array_map('trim', explode(',', $skuString)));

                if (empty($skus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No SKU provided.',
                    ]);
                }

                $products = Product::with('media')
                    ->whereIn('sku', $skus)
                    ->get()
                    ->keyBy('sku');

                $uploaded = [];
                $missing = [];

                foreach ($skus as $sku) {
                    if (!isset($products[$sku])) {
                        $missing[] = $sku;
                        continue;
                    }

                    $result = $this->productJsonUploadService->upload(
                        $products[$sku],
                        $folder,
                        $priceSource
                    );

                    $uploaded[] = [
                        'sku' => $sku,
                        'product' => $result['product'] ?? null,
                        'price' => $result['price'] ?? null,
                    ];
                }

                $message = 'Uploaded ' . count($uploaded) . ' product(s) to R2.';

                if (!empty($missing)) {
                    $message .= ' Missing SKUs: ' . implode(', ', $missing);
                }

                activity('r2.json.upload')
                    ->event('sku_upload')
                    ->withProperties([
                        'skus'     => array_values($skus),
                        'uploaded' => array_column($uploaded, 'sku'),
                        'missing'  => $missing,
                        'source'   => $priceSource,
                        'count'    => count($uploaded),
                    ])
                    ->log($message);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'uploaded' => $uploaded,
                        'missing' => $missing,
                    ],
                ]);
            }

            $result = $this->productJsonUploadService->uploadAll(
                $folder,
                $ignoreTimestamp,
                $priceSource
            );

            activity('r2.json.upload')
                ->event('bulk_upload')
                ->withProperties([
                    'source'           => $priceSource,
                    'ignore_timestamp' => $ignoreTimestamp,
                    'products_count'   => $result['products'],
                    'prices_count'     => $result['prices'],
                ])
                ->log('Bulk R2 JSON upload completed. Products: ' . $result['products'] . ', Prices: ' . $result['prices'] . '.');

            return response()->json([
                'success' => true,
                'message' => 'Bulk JSON upload completed successfully.',
                'data' => [
                    'products' => $result['products'],
                    'prices' => $result['prices'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('R2 JSON upload failed', [
                'error' => $e->getMessage(),
                'sku' => $skuString,
                'source' => $priceSource,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error during R2 JSON upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function syncParentSku()
    {
        $webhookUrl = config('services.parent_sku_webhook.url');
        $token = config('services.parent_sku_webhook.token');

        if (empty($webhookUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Parent SKU webhook URL is not configured.',
            ], 503);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->post($webhookUrl, ['triggered_by' => 'webhook']);

            if ($response->successful()) {
                activity('r2.json.upload')
                    ->event('parent_sku_sync')
                    ->withProperties([
                        'triggered_by'   => 'manual',
                        'webhook_status' => $response->status(),
                        'webhook_body'   => $response->json(),
                    ])
                    ->log('Parent SKU sync triggered successfully.');

                return response()->json([
                    'success' => true,
                    'message' => 'Parent SKU sync triggered successfully.',
                    'data'    => $response->json(),
                ]);
            }

            Log::error('Parent SKU webhook returned non-success', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            activity('r2.json.upload')
                ->event('parent_sku_sync_failed')
                ->withProperties([
                    'triggered_by'   => 'manual',
                    'webhook_status' => $response->status(),
                    'webhook_body'   => $response->body(),
                ])
                ->log('Parent SKU sync failed. Webhook status: ' . $response->status() . '.');

            return response()->json([
                'success' => false,
                'message' => 'Webhook responded with status ' . $response->status() . ': ' . $response->body(),
            ], 502);
        } catch (\Throwable $e) {
            Log::error('Parent SKU webhook call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to call Parent SKU webhook: ' . $e->getMessage(),
            ], 500);
        }
    }
}
