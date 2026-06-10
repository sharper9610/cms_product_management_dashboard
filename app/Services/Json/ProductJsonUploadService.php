<?php

namespace App\Services\Json;

use App\Http\Resources\ProductResourceV3;
use App\Models\Product;
use App\Models\Price;
use App\Services\Utils\SlugGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductJsonUploadService
{
    public function __construct(
        protected SlugGenerationService $slugService
    ) {}
    /*
    |--------------------------------------------------------------------------
    | Product JSON
    |--------------------------------------------------------------------------
    */

    public function uploadProduct(Product $product, string $folder): string
    {
        $this->slugService->generateForSku($product->sku);

        $product->refresh();

        $json = json_encode(
            new ProductResourceV3($product->load('media')),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $path = "{$folder}/products/{$product->sku}.json";

        Storage::disk('r2')->put($path, $json, [
            'ContentType' => 'application/json',
        ]);

        return $path;
    }

    public function uploadAllProducts(
        string $folder = 'product-json/v3',
        bool $ignoreTimestamp = false,
        ?int $priceSource = null
    ): int {
        $count = 0;

        $query = Product::with('media');

        if ($priceSource) {
            $query->where('source', $priceSource);
        } else {
            $query->whereIn('source', [1, 2, 3, 4]);
        }

        if (!$ignoreTimestamp) {
            $from = now('Europe/Stockholm')->subHours(30)->timestamp;
            $query->where('update_timestamp', '>=', $from);
        }

        $query->chunk(100, function ($products) use (&$count, $folder) {
            foreach ($products as $product) {
                $this->uploadProduct($product, $folder);
                $count++;
            }
        });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | Price JSON
    |--------------------------------------------------------------------------
    */

    public function uploadPrice(
        Product $product,
        string $folder,
        ?int $priceSource = null
    ): ?string {
        $priceData = $this->buildPriceJson($product, $priceSource);

        if (!$priceData) {
            return null;
        }

        $path = "{$folder}/prices/{$product->sku}.json";

        Storage::disk('r2')->put(
            $path,
            json_encode(
                $priceData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            ['ContentType' => 'application/json']
        );

        return $path;
    }

    public function uploadAllPrices(
        string $folder = 'product-json/v3',
        bool $ignoreTimestamp = false,
        ?int $priceSource = null
    ): int {
        $count = 0;

        $query = Product::whereHas('prices', function ($q) use (
            $ignoreTimestamp,
            $priceSource
        ) {
            $q->where('is_active', 1)
                ->whereNotNull('price')
                ->where('price', '>', 0);

            if ($priceSource) {
                $q->where('source', $priceSource);
            } else {
                $q->whereIn('source', [1, 2, 3, 4]);
            }

            if (!$ignoreTimestamp) {
                $from = now('Europe/Stockholm')->subHours(30)->timestamp;
                $q->where('price_update_timestamp', '>=', $from);
            }
        });

        $query->chunk(100, function ($products) use (
            &$count,
            $folder,
            $priceSource
        ) {
            foreach ($products as $product) {
                $uploaded = $this->uploadPrice(
                    $product,
                    $folder,
                    $priceSource
                );

                if ($uploaded) {
                    $count++;
                }
            }
        });

        return $count;
    }

    private function buildPriceJson(
        Product $product,
        ?int $priceSource = null
    ): ?array {
        $query = Price::where('product_id', $product->sku)
            ->select([
                'currency',
                'is_converted',
                'steam_price',
                DB::raw('CASE
                    WHEN is_converted = 1
                    AND steam_price IS NOT NULL
                    AND steam_price > 0
                    THEN steam_price
                    ELSE price
                END as price'),
                DB::raw('CASE
                    WHEN is_converted = 1
                    AND steam_price IS NOT NULL
                    AND steam_price > 0
                    THEN 0
                    ELSE 1
                END as is_native_price'),
                'discount_valid_from',
                'discount_valid_to',
                'price_update_timestamp',
                'discount_percent',
            ])
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('is_active', 1);

        if ($priceSource) {
            $query->where('source', $priceSource);
        } else {
            $query->whereIn('source', [1, 2, 3, 4]);
        }

        $prices = $query->get()->keyBy('currency');

        if ($prices->isEmpty()) {
            return null;
        }

        $stores = config('shopify.store_matrix.stores') ?? [];
        $storePrices = [];

        foreach ($stores as $storeKey => $store) {
            $currency = $store['currency'];

            if ($prices->has($currency)) {
                $p = $prices[$currency];

                $discountAmount = $p->isDiscountActive()
                    ? $p->discountAmount()
                    : null;

                $storePrices[$storeKey] = [
                    'currency'               => $currency,
                    'price'                  => $p->price,
                    'is_native_price'        => (bool) $p->is_native_price,
                    'price_after_discount'   => $discountAmount ?? null,
                    'discount_percent'       => $p->discount_percent,
                    'discount_valid_from'    => (int) ($p->discount_valid_from ?? 0),
                    'discount_valid_to'      => (int) ($p->discount_valid_to ?? 0),
                    'price_update_timestamp' => (int) ($p->price_update_timestamp ?? 0),
                ];
            }
        }

        if (empty($storePrices)) {
            return null;
        }

        return [
            'product_id' => (int) $product->sku,
            'prices'     => $storePrices,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Combined Upload
    |--------------------------------------------------------------------------
    */

    public function upload(
        Product $product,
        string $folder = 'product-json/v3',
        ?int $priceSource = null
    ): array {
        $folder = config('app.env') === 'production'
            ? 'product-json/v3'
            : 'staging/product-json/v3';

        return [
            'product' => $this->uploadProduct($product, $folder),
            'price'   => $this->uploadPrice($product, $folder, $priceSource),
        ];
    }

    public function uploadAll(
        string $folder = 'product-json/v3',
        bool $ignoreTimestamp = false,
        ?int $priceSource = null
    ): array {
        $folder = config('app.env') === 'production'
            ? 'product-json/v3'
            : 'staging/product-json/v3';

        return [
            'products' => $this->uploadAllProducts($folder, $ignoreTimestamp, $priceSource),
            'prices'   => $this->uploadAllPrices($folder, $ignoreTimestamp, $priceSource),
        ];
    }
}
