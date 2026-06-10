<?php

namespace App\Domains\Genba\Services;

use App\Domains\Genba\Models\GenbaProduct;
use App\Domains\Genba\Models\GenbaProductLanguage;
use App\Models\Localization;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductsSkipUpdate;
use Exception;
use Illuminate\Support\Facades\Log;


class GenbaSyncService
{


    /**
     * Sync every active product from the Genba DB.
     */
    public function syncProducts(): void
    {
        $cutoff = now()->subHours(48);

        $genbaProducts = GenbaProduct::with([
            'prices',
            'languages',
            'graphics',
            'rating',
            'videos'
        ])
            ->where('pre_live_state', '=', 1)
            ->where('updated_at', '>=', $cutoff)
            ->get();




        echo "Starting synchronization of {$genbaProducts->count()} products updated in the last 48 hours.\n";

        foreach ($genbaProducts as $index => $genbaProduct) {
            try {
                echo "Processing product " . ($index + 1) . "/{$genbaProducts->count()}"
                    . " –SKU: {$genbaProduct->id},"
                    . " Name: {$genbaProduct->name}\n";

                $this->syncSingleProduct($genbaProduct);

                echo "Successfully synced product SKU: {$genbaProduct->id}\n";
            } catch (Exception $e) {
                Log::error("Failed to sync Genba product ID {$genbaProduct->id}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "Failed to sync product ID: {$genbaProduct->id} – Error: {$e->getMessage()}\n";
            }
        }

        echo "Synchronization completed.\n";
    }

    public function syncPrices(): void
    {
        $cutoff = now()->subHours(48)->timestamp;

        $genbaProducts = GenbaProduct::with(['prices'])
            ->whereHas('prices', fn($q) => $q->where('price_update_timestamp', '>=', $cutoff))
            ->get();

        echo "Starting price sync for {$genbaProducts->count()} products with prices updated in the last 48 hours.\n";

        $source = config('services.sources.genba', 4);

        foreach ($genbaProducts as $index => $genbaProduct) {
            try {
                echo "Processing product " . ($index + 1) . "/{$genbaProducts->count()}"
                    . " – SKU: {$genbaProduct->id},"
                    . " Name: {$genbaProduct->name}\n";

                $product = Product::where('sku', $genbaProduct->id)
                    ->where('source', $source)
                    ->first();

                if (!$product) {
                    echo "  – Product SKU: {$genbaProduct->id} not found in Unify DB, skipping.\n";
                    continue;
                }

                $this->syncPrice($product, $genbaProduct);

                echo "Successfully synced prices for SKU: {$genbaProduct->id}\n";
            } catch (Exception $e) {
                Log::error("Failed to sync prices for Genba product ID {$genbaProduct->id}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "Failed to sync prices for product ID: {$genbaProduct->id} – Error: {$e->getMessage()}\n";
            }
        }

        echo "Price sync completed.\n";
    }

    /**
     * Sync a single GenbaProduct into the main DB and return the platform Product.
     */
    public function syncSingleProduct(GenbaProduct $genbaProduct): Product
    {
        echo "  – Preparing product data…\n";

        try {
            $productData = $this->prepareProductData($genbaProduct);


            echo "  – Updating or creating product…\n";

            $source = config('services.sources.genba', 4);

            $product = Product::firstOrNew([
                'sku'    => $productData['sku'],
                'source' => $source,
            ]);

            // Respect field-level skip-update rules when the product already exists.
            $skipRules = [];
            if ($product->exists) {
                $skipRules = ProductsSkipUpdate::where('product_id', $product->sku)
                    ->where('skip_update', 1)
                    ->pluck('field_name')
                    ->toArray();
            }

            $updateData = [
                'name'             => $productData['name']             ?? null,
                'default_language' => 'en',
                'platform'         => $productData['platform']         ?? null,
                'publisher_name'   => $productData['publisher_name']   ?? null,
                'product_type'     => $productData['product_type']     ?? 'Game',
                'genres'           => $productData['genres']           ?? null,
                'release_date'     => $productData['release_date']     ?? null,
                'update_timestamp' => $productData['update_timestamp'] ?? null,
                'category'         => $productData['category']         ?? null,
                'region_code'      => $productData['region_code']      ?? null,
                'status'           => $productData['status'],
                'pegi_ratings'     => $productData['pegi_ratings']     ?? null,
                'allowed_countries' => $productData['allowed_countries']    ?? null,
            ];

            foreach ($skipRules as $field) {
                unset($updateData[$field]);
            }

            $product->fill($updateData);
            $product->save();

            // echo "  – Syncing price information…\n";
            // $this->syncPrice($product, $genbaProduct);

            echo "  – Syncing localization…\n";
            $this->syncLocalization($product, $genbaProduct, $skipRules);

            echo "  – Syncing media…\n";
            $this->syncMedia($product, $genbaProduct);

            return $product;
        } catch (Exception $e) {
            Log::error('Genba: Failed to sync product', [
                'product_id' => $genbaProduct->id ?? 'unknown',
                'sku'        => $genbaProduct->sku ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    private function prepareProductData(GenbaProduct $genbaProduct): array
    {
        return [
            'sku'              => $genbaProduct->id,
            'name'             => $genbaProduct->name,
            'platform'         => $genbaProduct->platform,
            'publisher_name'   => $genbaProduct->publisher,
            'product_type'     => $genbaProduct->is_bundle ? 'Bundle' : 'Game',
            'genres'           => $this->normalizeGenres($genbaProduct->genres),
            'release_date'     => $genbaProduct->release_date
                ? $genbaProduct->release_date->timestamp
                : null,
            'update_timestamp' => $genbaProduct->updated_at
                ? $genbaProduct->updated_at->timestamp
                : time(),
            'source'           => config('services.sources.genba', 4),
            'region_code'      => $genbaProduct->region_code,
            'category'         => $genbaProduct->is_bundle ? 'Bundle' : 'Game',
            // 'status'           => $genbaProduct->is_active ? 1 : 0,
            'status'           => 0,
            'pegi_ratings'     => $this->buildPegiRatings($genbaProduct),
            'allowed_countries' => $this->buildAllowedCountries($genbaProduct),
        ];
    }

    private function buildAllowedCountries(GenbaProduct $genbaProduct): ?string
    {
        $restriction = $genbaProduct->countryRestrictions;

        if (!$restriction) {
            return null;
        }

        $whitelist = $this->parseCountryCodes($restriction->whitelist_country_codes);
        $blacklist = $this->parseCountryCodes($restriction->blacklist_country_codes);

        if (empty($whitelist) && empty($blacklist)) {
            return null;
        }

        return serialize([
            'whitelist' => $whitelist,
            'blacklist' => $blacklist,
        ]);
    }

    private function parseCountryCodes(?string $raw): array
    {
        if (empty(trim($raw ?? ''))) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', explode(',', $raw))
            )
        );
    }


    private function buildPegiRatings(GenbaProduct $genbaProduct): ?string
    {
        // Find the PEGI rating row (there may also be ESRB rows — ignore those).
        $pegiRow = $genbaProduct->rating
            ->first(fn($r) => strtoupper($r->rating_system_name) === 'PEGI');

        if (!$pegiRow) {
            return null;
        }

        $age     = $this->normalizePegiAge($pegiRow->ratings);
        $text    = "PEGI {$age}";
        $logoUrl = $this->pegiLogoUrl($age);

        $ratingEntry = [
            'Type' => 'pegi',
            'Logo' => ['URL' => $logoUrl],
            'Text' => $text,
        ];

        return serialize(['Rating' => [$ratingEntry]]);
    }

    private function normalizePegiAge(?string $raw): int
    {
        $validAges = [3, 7, 12, 16, 18];

        $numeric = (int) preg_replace('/\D/', '', $raw ?? '');


        if (in_array($numeric, $validAges, true)) {
            return $numeric;
        }

        foreach (array_reverse($validAges) as $valid) {
            if ($numeric >= $valid) {
                return $valid;
            }
        }

        return 3;
    }

    private function pegiLogoUrl(int $age): string
    {
        $base = config('services.ratings.pegi_logo_base', 'https://static.exertisztorm.net/logos/ratings');

        return "{$base}/pegi_{$age}.gif";
    }


    private function normalizeGenres(?string $genres): ?string
    {
        if (empty($genres)) {
            return null;
        }

        return trim($genres);
    }


    // private function deriveCategory(?string $genres): ?string
    // {
    //     return $this->normalizeGenres($genres);
    // }


    private function syncPrice(Product $product, GenbaProduct $genbaProduct): void
    {
        try {
            if ($genbaProduct->prices->isEmpty()) {
                return;
            }

            $now = now();


            foreach ($genbaProduct->prices as $genbaPrice) {
                // Skip zero / negative prices.
                if ((float) $genbaPrice->price <= 0) {
                    continue;
                }

                // Skip rows explicitly marked to not be updated.
                if ($genbaPrice->skip_update) {
                    continue;
                }

                Price::updateOrCreate(
                    [
                        'product_id'   => $product->sku,
                        'currency'     => $genbaPrice->currency_code,
                        'price'        => $genbaPrice->price,
                        'country_code' => $genbaPrice->country_code ?? null,
                    ],
                    [
                        'source'                 => $product->source,
                        'title'                  => $genbaPrice->product_price_title,
                        'cost_estimate'          => $genbaPrice->wsp ?? null,
                        'discount_percent'       => $genbaPrice->discount_percent ?? null,
                        'discount_valid_from'    => $genbaPrice->discount_valid_from ?? null,
                        'discount_valid_to'      => $genbaPrice->discount_valid_to   ?? null,
                        'price_update_timestamp' => $this->normalizeTimestamp($genbaPrice->price_update_timestamp) ?? now()->timestamp,
                        'is_active'              => $genbaPrice->is_active ?? 1,
                        'updated_at'             => $now,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::info("Genba: price sync failed for {$product->sku}: {$e->getMessage()}");
        }
    }

    private function normalizeTimestamp(?int $timestamp): ?int
    {
        if ($timestamp === null) {
            return null;
        }

        return $timestamp > 9_999_999_999
            ? (int) ($timestamp / 1000)
            : $timestamp;
    }


    private function syncLocalization(
        Product      $product,
        GenbaProduct $genbaProduct,
        array        $skipRules
    ): void {
        $skipShort = in_array('short_description', $skipRules, true);
        $skipLong  = in_array('long_description', $skipRules, true);
        $skipLegal = in_array('legal_texts', $skipRules, true);

        $hasEnglish = false;

        foreach ($genbaProduct->languages as $langRow) {
            $locale = $this->mapLanguageToLocale($langRow->language_name);

            if ($locale === 'en') {
                $hasEnglish = true;
            }

            $payload = [
                'title' => $this->nullIfEmpty($langRow->localized_name) ?? $product->name,
            ];

            if (!$skipShort) {
                $payload['short_description'] = $this->nullIfEmpty($langRow->localized_key_features);
            }

            if (!$skipLong) {
                $payload['long_description'] = $this->nullIfEmpty($langRow->localized_description);
            }

            if (!$skipLegal) {
                $payload['legal_texts'] = $this->nullIfEmpty($langRow->legal_text);
            }

            Localization::updateOrCreate(
                ['product_id' => $product->sku, 'locale' => $locale],
                $payload
            );
        }


        if (!$hasEnglish) {
            Localization::updateOrCreate(
                ['product_id' => $product->sku, 'locale' => 'en'],
                ['title'      => $product->name]
            );
        }
    }


    /**
     * Return null when a string is empty or whitespace-only.
     * Prevents storing empty strings '' in the unified DB text columns.
     */
    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Map Genba's full language names to BCP-47 locale codes.
     */
    private function mapLanguageToLocale(string $languageName): string
    {
        $map = [
            'English'            => 'en',
            'French'             => 'fr',
            'German'             => 'de',
            'Italian'            => 'it',
            'Spanish'            => 'es',
            'Russian'            => 'ru',
            'Japanese'           => 'ja',
            'Turkish'            => 'tr',
            'Portuguese-Brazil'  => 'pt-BR',
            'Portuguese'         => 'pt',
            'Dutch'              => 'nl',
            'Polish'             => 'pl',
            'Czech'              => 'cs',
            'Hungarian'          => 'hu',
            'Romanian'           => 'ro',
            'Greek'              => 'el',
            'Arabic'             => 'ar',
            'Azerbaijani'        => 'az',
            'Chinese'            => 'zh',
            'Korean'             => 'ko',
        ];

        return $map[$languageName] ?? strtolower(substr($languageName, 0, 2));
    }


    // ---------------------------------------------------------------------- //
    // Private helpers — media sync
    // ---------------------------------------------------------------------- //

    private function syncMedia(Product $product, GenbaProduct $genbaProduct): void
    {
        $mediaSource = config('services.media_sources.genba', 6);
        $now         = now();

        // ── Graphics ──────────────────────────────────────────────────────────
        foreach ($genbaProduct->graphics as $graphic) {
            if (empty($graphic->url)) {
                continue;
            }

            [$mediaType, $isMain, $orientation] = $this->resolveGraphicMeta($graphic);

            ProductMedia::updateOrCreate(
                [
                    'product_id' => $product->sku,
                    'url'        => $graphic->url,
                ],
                [
                    'is_main'           => $isMain,
                    'media_type'        => $mediaType,
                    'media_source'      => $mediaSource,
                    'image_orientation' => $orientation,
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ]
            );
        }

        // ── Videos ────────────────────────────────────────────────────────────
        foreach ($genbaProduct->videos as $video) {
            if (empty($video->url)) {
                continue;
            }

            ProductMedia::updateOrCreate(
                [
                    'product_id' => $product->sku,
                    'url'        => $video->url,
                ],
                [
                    'is_main'           => 0,
                    'media_type'        => ProductMedia::TYPE_VIDEOS,
                    'media_source'      => $mediaSource,
                    'image_orientation' => null,
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ]
            );
        }
    }

    private function resolveGraphicMeta(object $graphic): array
    {
        $orientation = $this->resolveImageOrientation($graphic);

        return match ($graphic->type) {
            // Box-art cover — main product image, always portrait
            'Packshot'    => [ProductMedia::TYPE_BOXSHOT,    1, ProductMedia::ORIENTATION_PORTRAIT],

            // Wide marketing banner — always landscape
            'Marketing'   => [ProductMedia::TYPE_IMAGES,     0, ProductMedia::ORIENTATION_LANDSCAPE],

            // In-game screenshots — derive orientation from actual pixel dimensions
            'Screenshot'  => [ProductMedia::TYPE_SCREENSHOT, 0, $orientation],

            // Logo / title lockup — treated as a generic image, derive orientation
            'Logo title'  => [ProductMedia::TYPE_IMAGES,     0, $orientation],

            // Unknown future types — safe default
            default       => [ProductMedia::TYPE_IMAGES,     0, $orientation],
        };
    }


    private function resolveImageOrientation(object $graphic): int
    {
        if (!empty($graphic->width) && !empty($graphic->height)) {
            return $graphic->height > $graphic->width
                ? ProductMedia::ORIENTATION_PORTRAIT
                : ProductMedia::ORIENTATION_LANDSCAPE;
        }

        return ProductMedia::ORIENTATION_LANDSCAPE;
    }
}
