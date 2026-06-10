<?php

namespace App\Domains\Incomm\Services;

use App\Domains\Incomm\Models\IncommPrice;
use App\Domains\Incomm\Models\IncommProduct;
use App\Models\Localization;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductsSkipUpdate;
use App\Services\Openai\OpenAIService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class IncommSyncService
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
        $incommProducts = IncommProduct::with([
            'idMapping',
            'cardImages',
            'commission',
            'productLine'
        ])
            ->whereNull('price')
            ->whereNull('parent_product_id')
            ->get();

        echo "Starting synchronization of " . $incommProducts->count() . " products from Incomm DB.\n";

        foreach ($incommProducts as $index => $incommProduct) {
            try {
                echo "Processing product " . ($index + 1) . "/" . $incommProducts->count() . " - ID: {$incommProduct->id}, Name: {$incommProduct->name}\n";
                $this->syncSingleProduct($incommProduct);
                echo "Successfully synced product ID: {$incommProduct->id}\n";
            } catch (Exception $e) {
                Log::error("Failed to sync product ID {$incommProduct->id}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "Failed to sync product ID: {$incommProduct->id} - Error: {$e->getMessage()}\n";
            }
        }
        echo "Synchronization completed.\n";
    }

    /**
     * Synchronize a single product from Incomm to local database.
     *
     * @param IncommProduct $incommProduct
     * @return Product
     * @throws \Exception
     */
    // public function syncSingleProduct(IncommProduct $incommProduct): Product
    // {
    //     echo "  - Preparing product data...\n";
    //     try {
    //         $productData = $this->prepareProductData($incommProduct);

    //         echo "  - Updating or creating product...\n";

    //         // $product = Product::updateOrCreate(
    //         //     ['sku' => $productData['sku'], 'source' => config('services.sources.incomm', 2)],
    //         //     [
    //         //         'name' => $productData['name'],
    //         //         'default_language' => 'pt-br',
    //         //         'auxiliary_field' => $productData['auxiliary_field'],
    //         //         'classification' => $productData['classification'],
    //         //         'validade' => $productData['validade'],
    //         //         'product_type' => 'Top up',
    //         //         'redemption' => $productData['redemption'],
    //         //         'redemption_field' => $productData['redemption_field'],
    //         //         'system_requirements' => $productData['requirements'],
    //         //         'terms_and_conditions' => $productData['terms_and_conditions'],
    //         //         'update_timestamp' => $productData['update_timestamp'],
    //         //         'min_value' => $productData['min_value'],
    //         //         'max_value' => $productData['max_value'],
    //         //         'status' => $productData['status'] == 'ACTIVE' ? 1 : 0,

    //         //     ]
    //         // );

    //         $source = config('services.sources.incomm', 2);

    //         // Find existing product or create a new instance (without saving yet)
    //         $product = Product::firstOrNew(
    //             ['sku' => $productData['sku'], 'source' => $source ]
    //         );

    //         // Only set name if product does not already exist or name is empty
    //         if (!$product->exists || empty($product->name)) {
    //             $product->name = $productData['name'];
    //         }

    //         // Always update the rest of the fields
    //         $product->default_language = 'pt-br';
    //         $product->auxiliary_field = $productData['auxiliary_field'];
    //         $product->classification = $productData['classification'];
    //         $product->validade = $productData['validade'];
    //         $product->product_type = 'Top up';
    //         $product->redemption = $productData['redemption'];
    //         $product->redemption_field = $productData['redemption_field'];
    //         $product->system_requirements = $productData['requirements'];
    //         $product->terms_and_conditions = $productData['terms_and_conditions'];
    //         $product->update_timestamp = $productData['update_timestamp'];
    //         $product->min_value = $productData['min_value'];
    //         $product->max_value = $productData['max_value'];
    //         $product->status = $productData['status'] == 'ACTIVE' ? 1 : 0;

    //         $product->save();

    //         echo "  - Syncing price information...\n";
    //         // Sync price information
    //         $this->syncPrice($product, $incommProduct);

    //         echo "  - Syncing product media...\n";
    //         // Sync product media
    //         $this->syncProductMedia($product, $incommProduct);

    //         echo "  - Syncing localization...\n";
    //         // Sync localization
    //         $this->syncLocalization($product, $productData);

    //         return $product;
    //     } catch (\Exception $e) {
    //         Log::error('Failed to sync product', [
    //             'product_id' => $incommProduct->idMapping->productIdInt ?? 'unknown',
    //             'error' => $e->getMessage(),
    //         ]);
    //         throw $e;
    //     }
    // }

    public function syncSingleProduct(IncommProduct $incommProduct): Product
    {
        echo "  - Preparing product data...\n";

        try {
            $productData = $this->prepareProductData($incommProduct);

            echo "  - Updating or creating product...\n";

            $source = config('services.sources.incomm', 2);

            // Find or create product
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

            // dd($skipRules);

            // Fields to update
            $updateData = [
                'name' => $productData['name'] ?? null,
                'product_upc' => $productData['card_identificator'] ?? null,
                'default_language' => 'pt-br',
                'auxiliary_field' => $productData['auxiliary_field'] ?? null,
                'classification' => $productData['classification'] ?? null,
                'validade' => $productData['validade'] ?? null,
                'product_type' => 'Game',
                'redemption' => $productData['redemption'] ?? null,
                'redemption_field' => $productData['redemption_field'] ?? null,
                'system_requirements' => $productData['requirements'] ?? null,
                'terms_and_conditions' => $productData['terms_and_conditions'] ?? null,
                'update_timestamp' => $productData['update_timestamp'] ?? null,
                'min_value' => $productData['min_value'] ?? null,
                'max_value' => $productData['max_value'] ?? null,
                'status' => isset($productData['status']) && $productData['status'] == 'ACTIVE' ? 1 : 0,
                'merchant_commission_percentage' => $productData['merchant_commission'] ?? null,
                'category' => $productData['category'] ?? null
            ];

            // Remove skipped fields dynamically
            foreach ($skipRules as $field) {
                unset($updateData[$field]);
            }


            // Fill and save
            $product->fill($updateData);
            $product->save();

            echo "  - Syncing price information...\n";
            $this->syncPrice($product, $incommProduct);

            // Sync product media unless skipped
            if (!in_array('product_media', $skipRules, true)) {
                echo "  - Syncing product media...\n";
                $this->syncProductMedia($product, $incommProduct);
            } else {
                echo "  - Skipping product media sync due to skip rules...\n";
            }

            echo "  - Syncing localization...\n";
            $this->syncLocalization($product, $productData, $skipRules);

            return $product;
        } catch (\Exception $e) {
            Log::error('Failed to sync product', [
                'product_id' => $incommProduct->idMapping->productIdInt ?? 'unknown',
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
    private function prepareProductData(IncommProduct $incommProduct): array
    {
        // dd($incommProduct);
        return [
            'sku' => $incommProduct->idMapping->productIdInt,
            'name' => $incommProduct->name,
            'min_value' => $incommProduct->min_value,
            'max_value' => $incommProduct->max_value,
            'requirements' => $incommProduct->Requirements ?? $incommProduct->productLine->steps_to_use,
            'description_long' => $incommProduct->description_long,
            'auxiliary_field' => $incommProduct->auxiliary_field,
            'classification' => $incommProduct->classification,
            'validade' => $incommProduct->validade,
            'redemption' => $incommProduct->redemption,
            'redemption_field' => $incommProduct->redemption_field,
            'terms_and_conditions' => $incommProduct->terms_and_conditions ?? $incommProduct->productLine->terms_and_conditions,
            'update_timestamp' => $incommProduct->updated_at ? strtotime($incommProduct->updated_at) : null,
            'status' => $incommProduct->Status,
            'card_identificator' => $incommProduct->card_identificator,
            'merchant_commission' => $incommProduct->commission ? $incommProduct->commission->merchant_commission : null,
            'category' => $incommProduct->category ?? null,
        ];
    }

    /**
     * Sync price information for the product.
     *
     * @param Product $product
     * @param IncommProduct $incommProduct
     * @return void
     */
    private function syncPrice(Product $product, IncommProduct $incommProduct): void
    {
        try {
            if (!isset($incommProduct->min_value) || !isset($incommProduct->max_value)) {
                return;
            }

            $min = (float) $incommProduct->min_value;
            $max = (float) $incommProduct->max_value;

            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }

            $precision = max(2, $this->getDecimalPlaces($min), $this->getDecimalPlaces($max));
            $factor = (int) pow(10, $precision);

            $minUnits = (int) round($min * $factor);
            $maxUnits = (int) round($max * $factor);

            Price::where('product_id', $product->sku)
                ->where(function ($q) use ($min, $max) {
                    $q->where('price', '<', $min)
                        ->orWhere('price', '>', $max);
                })
                ->update(['is_active' => 0]);


            if ($minUnits === $maxUnits) {
                $priceValue = number_format($minUnits / $factor, $precision, '.', '');
                $this->upsertPriceIfNoDiscount($product, $priceValue, $min, $max);
                return;
            }

            $step = $this->getIntervalStep($min, $max);
            $stepUnits = max(1, (int) round($step * $factor));

            for ($units = $minUnits; $units <= $maxUnits; $units += $stepUnits) {
                $priceValue = number_format($units / $factor, $precision, '.', '');

                if ((float) $priceValue <= 0.0) {
                    continue;
                }
                $this->upsertPriceIfNoDiscount($product, $priceValue, $min, $max);
            }

            $finalPrice = number_format($maxUnits / $factor, $precision, '.', '');
            $this->upsertPriceIfNoDiscount($product, $finalPrice, $min, $max);
        } catch (Exception $e) {
            logger()->error("Price sync failed for product {$product->sku}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function upsertPriceIfNoDiscount(
        Product $product,
        string $priceValue,
        float $min,
        float $max
    ): void {
        try {
            DB::transaction(function () use ($product, $priceValue, $min, $max) {

                $existingPrice = Price::where('product_id', $product->sku)
                    ->where('price', $priceValue)
                    ->where('currency', 'BRL')
                    ->where('country_code', 'BR')
                    ->lockForUpdate()
                    ->first();

                if ($existingPrice) {

                    // Active discount
                    if ($existingPrice->isDiscountActive()) {
                        logger()->info(
                            "Skipped updating price {$priceValue} for Product {$product->sku} due to active discount"
                        );
                        return;
                    }

                    // Scheduled discount
                    if (
                        !empty($existingPrice->discount_valid_from) &&
                        $existingPrice->discount_valid_from > time() &&
                        empty($existingPrice->discount_valid_to)
                    ) {
                        logger()->info(
                            "Skipped updating price {$priceValue} for Product {$product->sku} due to scheduled discount"
                        );
                        return;
                    }

                    $existingPrice->update([
                        'source'                 => $product->source,
                        'min_value'              => $min,
                        'max_value'              => $max,
                        'price_update_timestamp' => now()->timestamp,
                        'is_active'              => 1,
                    ]);

                    logger()->info("Updated existing price {$priceValue} for Product {$product->sku}");
                    return;
                }

                // Try insert
                Price::create([
                    'product_id'             => $product->sku,
                    'price'                  => $priceValue,
                    'currency'               => 'BRL',
                    'country_code'           => 'BR',
                    'source'                 => $product->source,
                    'min_value'              => $min,
                    'max_value'              => $max,
                    'price_update_timestamp' => now()->timestamp,
                    'is_active'              => 1,
                ]);

                logger()->info("Inserted new price {$priceValue} for Product {$product->sku}");
            });
        } catch (QueryException $e) {

            // MySQL duplicate key error
            if ((int) $e->errorInfo[1] === 1062) {

                logger()->warning(
                    "Duplicate price detected, retrying update: {$priceValue} for Product {$product->sku}"
                );

                // Fetch and update existing row
                $price = Price::where('product_id', $product->sku)
                    ->where('price', $priceValue)
                    ->where('currency', 'BRL')
                    ->where('country_code', 'BR')
                    ->first();

                if ($price && !$price->isDiscountActive()) {
                    $price->update([
                        'source'                 => $product->source,
                        'min_value'              => $min,
                        'max_value'              => $max,
                        'price_update_timestamp' => now()->timestamp,
                        'is_active'              => 1,
                    ]);
                }

                return;
            }

            // Other DB errors → rethrow
            throw $e;
        } catch (\Throwable $e) {
            logger()->error(
                "Failed upserting price {$priceValue} for Product {$product->sku}: {$e->getMessage()}",
                ['trace' => $e->getTraceAsString()]
            );
        }
    }



    /**
     * Return number of significant decimal places for a value.
     */
    private function getDecimalPlaces($value): int
    {
        $s = (string) $value;
        if (strpos($s, '.') === false) {
            return 0;
        }

        $dec = rtrim(substr($s, strpos($s, '.') + 1), '0');
        return $dec === '' ? 0 : strlen($dec);
    }

    private function getIntervalStep(float $min, float $max): int
    {
        $range = $max - $min;

        return match (true) {
            $range <= 50    => 5,
            $range <= 100   => 10,
            $range <= 200   => 15,
            $range <= 500   => 20,
            $range <= 1000  => 50,
            $range <= 5000  => 100,
            $range <= 10000 => 200,
            default         => 500,
        };
    }



    /**
     * Sync product media information.
     *
     * @param Product $product
     * @param IncommProduct $incommProduct
     * @return void
     */
    // private function syncProductMedia(Product $product, IncommProduct $incommProduct): void
    // {

    //     $mediaCollection = [];
    //     $existingMedia = ProductMedia::where([
    //         'product_id' => $product->sku,
    //         'media_type' => 1,
    //     ])->pluck('url')->toArray();

    //     // Add card images
    //     foreach ($incommProduct->cardImages as $cardImage) {
    //         $fullUrl = $this->extractFullUrl($cardImage->image_url);
    //         if ($fullUrl && !in_array($fullUrl, $existingMedia)) {
    //             $mediaCollection[] = [
    //                 'product_id' => $product->sku,
    //                 'media_type' => config('services.mediatype.images'),
    //                 'url' => $fullUrl,
    //             ];
    //             $existingMedia[] = $fullUrl;
    //         }
    //     }

    //     // Add logo
    //     $logoUrl = $this->extractFullUrl($incommProduct->productLine->logo_url);
    //     if ($logoUrl && !in_array($logoUrl, $existingMedia)) {
    //         $mediaCollection[] = [
    //             'product_id' => $product->sku,
    //             'media_type' => 1,
    //             'url' => $logoUrl,
    //         ];
    //     }

    //     if (!empty($mediaCollection)) {
    //         ProductMedia::insert($mediaCollection);
    //     }
    // }
    private function syncProductMedia(Product $product, IncommProduct $incommProduct): void
    {
        $productId = $product->sku;

        $incommUrls = collect($incommProduct->cardImages)
            ->pluck('image_url')
            ->map(fn($url) => $this->extractFullUrl($url))
            ->filter()
            ->values();

        $logoUrl = $this->extractFullUrl($incommProduct->productLine->logo_url);
        if ($logoUrl) {
            $incommUrls->push($logoUrl);
        }

        $incommUrls = $incommUrls->unique()->toArray();

        if (empty($incommUrls)) {
            return;
        }

        DB::transaction(function () use ($productId, $incommUrls) {
            $existingMedia = ProductMedia::where('product_id', $productId)
                ->where('media_source', '!=', ProductMedia::SOURCE_MANUAL)
                ->get(['id', 'url', 'media_type', 'image_orientation']);

            $existingUrls = array_unique($existingMedia->pluck('url')->toArray());
            $incommUrls   = array_unique($incommUrls);

            $urlsToInsert = array_values(array_diff($incommUrls, $existingUrls));
            $urlsToDelete = array_values(array_diff($existingUrls, $incommUrls));

            if (!empty($urlsToInsert)) {
                $now = now();
                $records = [];

                foreach ($urlsToInsert as $url) {
                    $orientation = ProductMedia::ORIENTATION_LANDSCAPE;
                    try {
                        [$width, $height] = getimagesize($url);
                        $orientation = ($height > $width)
                            ? ProductMedia::ORIENTATION_PORTRAIT
                            : ProductMedia::ORIENTATION_LANDSCAPE;
                    } catch (\Exception $e) {
                    }

                    $records[] = [
                        'product_id'       => $productId,
                        'media_type'       => ProductMedia::TYPE_BOXSHOT,
                        'media_source'     => ProductMedia::SOURCE_INCOMM,
                        'image_orientation' => $orientation,
                        'url'              => $url,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                ProductMedia::insert($records);
            }

            if (!empty($urlsToDelete)) {
                ProductMedia::where('product_id', $productId)
                    ->where('media_source', '!=', ProductMedia::SOURCE_MANUAL)
                    ->whereIn('url', $urlsToDelete)
                    ->delete();
            }

            foreach ($existingMedia as $media) {
                try {
                    [$width, $height] = getimagesize($media->url);
                    $orientation = ($height > $width)
                        ? ProductMedia::ORIENTATION_PORTRAIT
                        : ProductMedia::ORIENTATION_LANDSCAPE;
                } catch (\Exception $e) {
                    $orientation = $media->image_orientation ?? ProductMedia::ORIENTATION_LANDSCAPE;
                }

                $media->update([
                    'media_type'       => ProductMedia::TYPE_BOXSHOT,
                    'image_orientation' => $orientation,
                ]);
            }
        });
    }



    /**
     * Sync localization information.
     *
     * @param Product $product
     * @param array $productData
     * @return void
     */
    private function syncLocalization(Product $product, array $productData, array $skipRules): void
    {

        $locale = 'pt-br';

        $skipShort = in_array('short_description', $skipRules, true);
        $skipLong  = in_array('long_description', $skipRules, true);


        if ($skipShort && $skipLong) {
            Localization::updateOrCreate(
                ['product_id' => $product->sku, 'locale' => $locale],
                ['title' => isset($product) ? $product->name : $productData['name']]
            );
            return;
        }

        $existingLocalization = Localization::where('product_id', $product->sku)
            ->where('locale', $locale)
            ->first();

        $existingShort = $existingLocalization?->short_description;
        $existingLong  = $existingLocalization?->long_description;

        $longDescription = $skipLong
            ? $existingLong
            : ($productData['description_long'] ?? null);

        if ($skipShort) {
            $shortDescription = $existingShort;
        } else {
            $shortDescription = $existingShort ?: ($longDescription ? $this->generateShortDescription($longDescription) : null);
        }

        $updatePayload = [
            'title' => $product->name,
        ];

        if (!$skipShort) {
            $updatePayload['short_description'] = $shortDescription;
        }

        if (!$skipLong) {
            $updatePayload['long_description'] = $longDescription;
        }

        Localization::updateOrCreate(
            ['product_id' => $product->sku, 'locale' => $locale],
            $updatePayload
        );
    }



    private function generateShortDescription(string $longDescription): ?string
    {
        try {
            $prompt = "Summarize the following product description into a concise, engaging short description (max 100 characters):\n\n" . $longDescription;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini', // You can also use 'gpt-4-turbo' or 'gpt-3.5-turbo'
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a marketing assistant who writes short product summaries.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 120,
            ]);

            $this->openAIService->deactivateRateLimitNotice();

            return trim($response->choices[0]->message->content ?? '');
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            $this->openAIService->activateRateLimitNotice($e);
        } catch (Exception $e) {
            Log::error("Failed to generate short description: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract and validate full URL from provided path.
     *
     * @param string|null $url
     * @return string|null
     */
    private function extractFullUrl(?string $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return $data[0]['_full'] ?? null;
    }
}
