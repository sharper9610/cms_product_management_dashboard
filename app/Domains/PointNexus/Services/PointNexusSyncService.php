<?php

namespace App\Domains\PointNexus\Services;


use App\Domains\PointNexus\Models\PnProduct;
use App\Models\Localization;
use App\Models\PnProductStock;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductsSkipUpdate;
use App\Services\Openai\OpenAIService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class PointNexusSyncService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    /**
     * Sync all products from Incomm DB into main DB
     */
    public function syncProducts(): void
    {
        $pnProducts = PnProduct::with([
            'prices',
            'description',
            'graphics'
        ])->get();


        echo "Starting synchronization of " . $pnProducts->count() . " products from Point Nexus DB.\n";

        foreach ($pnProducts as $index => $pnProduct) {
            try {
                echo "Processing product " . ($index + 1) . "/" . $pnProducts->count() . " - ID: {$pnProduct->id}, Name: {$pnProduct->generic_name}\n";
                $this->syncSingleProduct($pnProduct);
                echo "Successfully synced product ID: {$pnProduct->id}\n";
            } catch (Exception $e) {
                Log::error("Failed to sync product ID {$pnProduct->id}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "Failed to sync product ID: {$pnProduct->id} - Error: {$e->getMessage()}\n";
            }
        }
        echo "Synchronization completed.\n";
    }

    public function syncSingleProduct(PnProduct $pnProduct): Product
    {
        echo "  - Preparing product data...\n";

        try {
            $productData = $this->prepareProductData($pnProduct);


            echo "  - Updating or creating product...\n";

            $source = config('services.sources.point_nexus', 3);

            $product = Product::firstOrNew([
                'sku' => $productData['sku'],
                'source' => $source
            ]);

            // Load skip update rules (if the product exists)
            $skipRules = [];
            if ($product->exists) {
                $skipRules = ProductsSkipUpdate::where('product_id', $product->sku)
                    ->where('skip_update', 1)
                    ->pluck('field_name')
                    ->toArray();
            }


            // Fields to update
            $updateData = [
                'name' => $productData['name'] ?? null,
                'default_language' => 'en',

                'platform' => $productData['platform'] ?? null,
                'publisher_name' => $productData['publisher_name'] ?? null,
                'product_type' => $productData['product_type'] ?? 'Game',

                'genres' => $productData['genres'] ?? null,
                'editions' => $productData['editions'] ?? null,

                'allowed_countries' => $productData['allowed_countries'] ?? null,
                'allowed_currencies' => $productData['allowed_currencies'] ?? null,

                'release_date' => $productData['release_date'] ?? null,
                'update_timestamp' => $productData['update_timestamp'] ?? null,


                'category' => $productData['category'] ?? null,


                'status' => $productData['status'],
            ];

            // Remove skipped fields dynamically
            foreach ($skipRules as $field) {
                unset($updateData[$field]);
            }


            // Fill and save
            $product->fill($updateData);
            $product->save();

            echo "  - Syncing price information...\n";
            $this->syncPrice($product, $pnProduct);

            echo "  - Syncing localization...\n";
            $this->syncLocalization($product, $pnProduct, $skipRules);

            echo "  - Syncing Stock...\n";
            $this->syncStock($product, $pnProduct);

            echo "  - Syncing Media...\n";
            $this->syncMedia($product, $pnProduct);

            return $product;
        } catch (\Exception $e) {
            Log::error('Failed to sync product', [
                'product_id' => $pnProduct->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Prepare product data from IncommProduct.
     *
     * @param IncommProduct $incommProduct
     * @return array
     */
    private function prepareProductData(PnProduct $pnProduct): array
    {
        return [
            'sku' => $pnProduct->id,
            'name' => $pnProduct->generic_name,
            'platform' => $pnProduct->platform,
            'publisher_name' => $pnProduct->publisher,
            'product_type' => 'Game',
            'editions' => $pnProduct->edition,
            'genres' => $this->normalizeSerialized($pnProduct->genres),
            'release_date' => $pnProduct->release_date
                ? strtotime($pnProduct->release_date)
                : null,
            'update_timestamp' => $pnProduct->last_updated_at
                ? strtotime($pnProduct->last_updated_at)
                : time(),
            'source' => config('services.sources.point_nexus') ?? 3,
            'allowed_countries' => $this->normalizeSerialized($pnProduct->allowed_countries),
            'allowed_currencies' => $this->normalizeSerialized($pnProduct->allowed_currencies),
            'category' => $pnProduct->type,
            'status'   => $pnProduct->is_active,
        ];
    }

    private function normalizeSerialized($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value, '"');

        return $value;
    }



    private function syncPrice(Product $product, PnProduct $pnProduct): void
    {
        try {
            if (empty($pnProduct->prices)) {
                return;
            }

            $now = now();

            [$rows, $activeKeys] = $this->preparePriceRows($product, $pnProduct, $now);

            if (empty($rows)) {
                return;
            }

            $this->bulkUpsertPrices($rows);
        } catch (Exception $e) {
            Log::info("price sync failed for {$product->sku}: {$e->getMessage()}");
        }
    }

    private function preparePriceRows(Product $product, PnProduct $pnProduct, $now): array
    {
        $rows = [];
        $activeKeys = [];

        foreach ($pnProduct->prices as $pnPrice) {

            $priceValue = $pnPrice->calculated_price;
            if ($priceValue <= 0) {
                continue;
            }

            [$discountFrom, $discountTo] = $this->extractDiscountDates($pnPrice);

            $countries = $this->extractCountriesArray($pnPrice);
            $allowed_countries = $this->normalizeSerialized($pnPrice->allowed_countries);

            // fallback if no countries provided
            if (empty($countries)) {
                $countries = [null];
            }

            foreach ($countries as $country) {

                $key = $pnPrice->currency . '_' . $priceValue . '_' . $country;
                $activeKeys[] = $key;

                $rows[] = [
                    'product_id'             => $product->sku,
                    'currency'               => $pnPrice->currency,
                    'price'                  => $priceValue,
                    'country_code'           => $country,
                    'allowed_countries'      => $allowed_countries,
                    'source'                 => $product->source,
                    'cost_estimate'          => $pnPrice->cost_estimate,
                    'discount_percent'       => $pnPrice->pn_promo_discount_percent,
                    'discount_valid_from'    => $discountFrom,
                    'discount_valid_to'      => $discountTo,
                    'price_update_timestamp' => $now->timestamp,
                    'is_active'              => $pnPrice->is_active ?? 1,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
            }
        }

        return [$rows, $activeKeys];
    }

    private function extractCountriesArray($pnPrice): array
    {
        if (empty($pnPrice->allowed_countries)) {
            return [];
        }

        $countries = @unserialize($pnPrice->allowed_countries);

        return is_array($countries) ? $countries : [];
    }



    private function extractDiscountDates($pnPrice): array
    {
        return [
            $pnPrice->pn_promo_start ? strtotime($pnPrice->pn_promo_start) : null,
            $pnPrice->pn_promo_end ? strtotime($pnPrice->pn_promo_end) : null,
        ];
    }

    private function bulkUpsertPrices(array $rows): void
    {
        try {
            if (empty($rows)) {
                return;
            }

            foreach ($rows as $row) {

                Price::updateOrCreate(
                    [
                        'product_id'   => $row['product_id'],
                        'currency'     => $row['currency'],
                        'price'        => $row['price'],
                        'country_code' => $row['country_code'] ?? null,
                    ],
                    [
                        'source'                 => $row['source'] ?? null,
                        'cost_estimate'          => $row['cost_estimate'] ?? null,
                        'discount_percent'       => $row['discount_percent'] ?? null,
                        'discount_valid_from'    => $row['discount_valid_from'] ?? null,
                        'discount_valid_to'      => $row['discount_valid_to'] ?? null,
                        'price_update_timestamp' => $row['price_update_timestamp'] ?? now()->timestamp,
                        'is_active'              => $row['is_active'] ?? 1,
                        'allowed_countries'      => $row['allowed_countries'],
                        'updated_at'             => now(),
                    ]
                );
            }
        } catch (Exception $e) {

            Log::info('Bulk price sync failed', [
                'message' => $e->getMessage(),
                'rows_sample' => array_slice($rows, 0, 5),
            ]);
        }
    }



    /**
     * Sync localization information.
     *
     * @param Product $product
     * @param array $productData
     * @return void
     */
    private function syncLocalization(Product $product, PnProduct $pnProduct, array $skipRules): void
    {

        $locale = $pnProduct->description->locale;

        $skipShort = in_array('short_description', $skipRules, true);
        $skipLong  = in_array('long_description', $skipRules, true);


        if ($skipShort && $skipLong) {
            Localization::updateOrCreate(
                ['product_id' => $product->sku, 'locale' => $locale],
                ['title' => $product->name]
            );
            return;
        }



        $updatePayload = [
            'title' => $product->name,
        ];

        if (!$skipShort) {
            $updatePayload['short_description'] = $pnProduct->description->short_description;
        }

        if (!$skipLong) {
            $updatePayload['long_description'] = $pnProduct->description->long_description;
        }


        Localization::updateOrCreate(
            ['product_id' => $product->sku, 'locale' => $locale],
            $updatePayload
        );
    }


    // private function syncStock(Product $product, PnProduct $pnProduct): void
    // {
    //     try {
    //         if (empty($pnProduct->stocks)) {
    //             return;
    //         }

    //         foreach ($pnProduct->stocks as $stock) {

    //             $geolock = strtolower(trim($stock->geolock));

    //             PnProductStock::updateOrCreate(
    //                 [
    //                     'product_id' => $product->sku,
    //                     'geolock'    => $geolock,
    //                 ],
    //                 [
    //                     'countries'  => $stock->countries,
    //                     'qty'        => (int) $stock->qty,
    //                     'stock_update_timestamp' => now()->timestamp,
    //                     'is_active'  => 1,
    //                     'updated_at' => now(),
    //                 ]
    //             );
    //         }
    //     } catch (Exception $e) {

    //         Log::info('Stock sync failed', [
    //             'message' => $e->getMessage(),
    //             'product_id' => $product->sku,
    //         ]);
    //     }
    // }

    private function syncStock(Product $product, PnProduct $pnProduct): void
    {
        try {
            if (empty($pnProduct->stocks) || $pnProduct->stocks->isEmpty()) {
                $product->update(['status' => 0]);
                Log::info('Product deactivated due to no stock', [
                    'product_id' => $product->sku,
                ]);
                return;
            }

            $totalQty = 0;

            foreach ($pnProduct->stocks as $stock) {
                $geolock = strtolower(trim($stock->geolock));
                $qty     = (int) $stock->qty;

                PnProductStock::updateOrCreate(
                    [
                        'product_id' => $product->sku,
                        'geolock'    => $geolock,
                    ],
                    [
                        'countries'              => $stock->countries,
                        'qty'                    => $qty,
                        'stock_update_timestamp' => now()->timestamp,
                        'is_active'              => $qty > 0 ? 1 : 0,
                        'updated_at'             => now(),
                    ]
                );

                $totalQty += $qty;
            }

            if ($totalQty === 0) {
                $product->update(['status' => 0]);
                Log::info('Product deactivated due to zero stock', [
                    'product_id' => $product->sku,
                    'total_qty'  => $totalQty,
                ]);
            } else {
                $product->update(['status' => 1]);
            }
        } catch (Exception $e) {
            Log::info('Stock sync failed', [
                'message'    => $e->getMessage(),
                'product_id' => $product->sku,
            ]);
        }
    }

    private function syncMedia(Product $product, PnProduct $pnProduct): void
    {

        try {
            if (empty($pnProduct->graphics)) {
                return;
            }

            $mediaSource = config('services.media_sources.point_nexus', 4);
            $now = now();

            foreach ($pnProduct->graphics as $media) {
                if (empty($media->url)) {
                    continue;
                }

                if ($media->type == 'header') {
                    ProductMedia::updateOrCreate(
                        [
                            'product_id' => $product->sku,
                            'url'        => $media->url,
                        ],
                        [
                            'is_main'           => 1,
                            'media_type'        => 3,
                            'media_source'      => $mediaSource,
                            'image_orientation' => 2,
                            'updated_at'        => $now,
                            'created_at'        => $now,
                        ]
                    );
                }

                ProductMedia::updateOrCreate(
                    [
                        'product_id' => $product->sku,
                        'url'        => $media->url,
                    ],
                    [
                        'is_main'           => 0,
                        'media_type'        => 3,
                        'media_source'      => $mediaSource,
                        'image_orientation' => 2,
                        'updated_at'        => $now,
                        'created_at'        => $now,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::info('Media sync failed', [
                'message'    => $e->getMessage(),
                'product_id' => $product->sku,
            ]);
        }
    }
}
