<?php

namespace App\Services\SkuMapping;

use App\Models\Price;
use App\Models\SkuMapping;
use Illuminate\Support\Facades\Log;
use Psy\Readline\Hoa\Console;
use RuntimeException;

class ParentSkuResolver
{
    /**
     * Transform payload items from parent SKUs to real child SKUs.
     *
     */
    public function transformPayload(array $payload): array
    {
        $countryCode      = strtoupper($payload['country_code']);
        $transformedItems = [];


        foreach ($payload['items'] as $index => $item) {
            $parentSku    = (int)   $item['product_id'];
            $salesPrice   = (float) $item['sales_price_including_vat'];
            $currencyCode = strtoupper($item['currency_code']);

            Log::channel('order')->info('[SkuResolver] Resolving SKU', [
                'index'        => $index,
                'parent_sku'   => $parentSku,
                'country_code' => $countryCode,
                'currency'     => $currencyCode,
                'sales_price'  => $salesPrice,
            ]);

            // ── 1. Check for mapping ─────────────────────────────────────────
            $mapping = SkuMapping::where('parent_sku', $parentSku)->first();

            if (!$mapping) {
                Log::channel('order')->info('[SkuResolver] No mapping found — passing SKU through', [
                    'sku' => $parentSku,
                ]);
                // $transformedItems[] = $item;
                // continue;

                throw new RuntimeException(
                    "Cannot resolve parent SKU {$parentSku}: no SKU mapping exists."
                );
            }

            $childSkus = $mapping->child_skus;

            Log::channel('order')->info('[SkuResolver] Mapping found', [
                'parent_sku' => $parentSku,
                'child_skus' => $childSkus,
                'webhook_id' => $mapping->webhook_id,
            ]);

            // ── 2. Find matching child SKU via prices ────────────────────────
            $candidates = Price::whereIn('product_id', $childSkus)
                ->where('is_active', 1)
                ->where('currency', $currencyCode)
                ->where('country_code', $countryCode)
                ->get();


            $matchedPrice = null;

            // ── 1. Price match (within 2% tolerance) ────────────────────────────
            $matchedPrice ??= $candidates->first(
                fn(Price $p) => $this->isWithinTolerance((float) $p->price, $salesPrice)
            );

            // ── 2. Steam price match — converted, no discount (within 2% tolerance)
            $matchedPrice ??= $candidates->first(
                fn(Price $p) => $p->is_converted
                    && $this->isWithinTolerance((float) $p->steam_price, $salesPrice)
            );

            // ── 3. Discount match (within 2% tolerance) ───────────────────
            $matchedPrice ??= $candidates->first(function (Price $p) use ($salesPrice) {
                if (!$p->discount_percent || !$p->isDiscountActive()) {
                    return false;
                }

                return $this->isWithinTolerance((float) $p->discount_amount, $salesPrice);
            });


            if (!$matchedPrice) {
                Log::channel('order')->error('[SkuResolver] No matching child SKU found', [
                    'parent_sku'   => $parentSku,
                    'child_skus'   => $childSkus,
                    'price'        => $salesPrice,
                    'currency'     => $currencyCode,
                    'country_code' => $countryCode,
                ]);

                throw new RuntimeException(
                    "Cannot resolve parent SKU {$parentSku}: no active child SKU found "
                        . "for price {$salesPrice} {$currencyCode} in country {$countryCode}."
                );
            }

            $resolvedSku = (int) $matchedPrice->product_id;
            $snapshot    = $parentSku . ' -> [' . implode(', ', $childSkus) . ']';
            // e.g. "10150 -> [1015, 1016, 1049, 72358, 72361, 80972]"

            Log::channel('order')->info('[SkuResolver] Resolved successfully', [
                'parent_sku'   => $parentSku,
                'resolved_sku' => $resolvedSku,
                'snapshot'     => $snapshot,
                'price_id'     => $matchedPrice->id,
            ]);

            // ── 3. Replace product_id; carry snapshot for DB persistence ─────
            $transformedItems[] = array_merge($item, [
                'product_id'            => $resolvedSku,
                '_sku_mapping_snapshot' => $snapshot,
            ]);
        }

        return array_merge($payload, ['items' => $transformedItems]);
    }


    public function transformStorefrontPayload(array $payload): array
    {
        $countryCode      = strtoupper($payload['country_code']);
        $transformedItems = [];

        foreach ($payload['items'] as $index => $item) {
            $parentSku    = (int)   $item['product_id'];
            $salesPrice   = (float) $item['sales_price_including_vat'];
            $currencyCode = strtoupper($item['currency_code']);

            // ── source_sku bypass ────────────────────────────────────────────────
            if (!empty($item['source_sku'])) {
                $resolvedSku = (int) $item['source_sku'];

                // ── Price validation ─────────────────────────────────────────────
                $candidates = Price::where('product_id', $resolvedSku)
                    ->where('is_active', 1)
                    ->where('currency', $currencyCode)
                    ->where('country_code', $countryCode)
                    ->get();

                $matchedPrice = null;

                $matchedPrice ??= $candidates->first(
                    fn(Price $p) => $this->isWithinTolerance((float) $p->price, $salesPrice)
                );

                $matchedPrice ??= $candidates->first(
                    fn(Price $p) => $p->is_converted
                        && $this->isWithinTolerance((float) $p->steam_price, $salesPrice)
                );

                $matchedPrice ??= $candidates->first(function (Price $p) use ($salesPrice) {
                    if (!$p->discount_percent || !$p->isDiscountActive()) {
                        return false;
                    }
                    return $this->isWithinTolerance((float) $p->discount_amount, $salesPrice);
                });

                if (!$matchedPrice) {
                    Log::channel('order')->error('[SkuResolver] source_sku price validation failed', [
                        'index'        => $index,
                        'parent_sku'   => $parentSku,
                        'source_sku'   => $resolvedSku,
                        'price'        => $salesPrice,
                        'currency'     => $currencyCode,
                        'country_code' => $countryCode,
                    ]);

                    throw new RuntimeException(
                        "Cannot use source SKU {$resolvedSku}: no active price found "
                            . "for price {$salesPrice} {$currencyCode} in country {$countryCode}."
                    );
                }
                // ─────────────────────────────────────────────────────────────────

                $snapshot = $parentSku . ' -> [direct:' . $resolvedSku . ']';

                Log::channel('order')->info('[SkuResolver] source_sku bypass — price validated', [
                    'index'        => $index,
                    'parent_sku'   => $parentSku,
                    'source_sku'   => $resolvedSku,
                    'price_id'     => $matchedPrice->id,
                ]);

                $transformedItems[] = array_merge($item, [
                    'product_id'            => $resolvedSku,
                    '_sku_mapping_snapshot' => $snapshot,
                ]);
                continue;
            }
            // ─────────────────────────────────────────────────────────────────────

            Log::channel('order')->info('[SkuResolver] Resolving SKU', [
                'index'        => $index,
                'parent_sku'   => $parentSku,
                'country_code' => $countryCode,
                'currency'     => $currencyCode,
                'sales_price'  => $salesPrice,
            ]);

            $mapping = SkuMapping::where('parent_sku', $parentSku)->first();

            if (!$mapping) {
                throw new RuntimeException(
                    "Cannot resolve parent SKU {$parentSku}: no SKU mapping exists."
                );
            }

            $childSkus = $mapping->child_skus;

            Log::channel('order')->info('[SkuResolver] Mapping found', [
                'parent_sku' => $parentSku,
                'child_skus' => $childSkus,
                'webhook_id' => $mapping->webhook_id,
            ]);

            $candidates = Price::whereIn('product_id', $childSkus)
                ->where('is_active', 1)
                ->where('currency', $currencyCode)
                ->where('country_code', $countryCode)
                ->get();

            $matchedPrice = null;

            $matchedPrice ??= $candidates->first(
                fn(Price $p) => $this->isWithinTolerance((float) $p->price, $salesPrice)
            );

            $matchedPrice ??= $candidates->first(
                fn(Price $p) => $p->is_converted
                    && $this->isWithinTolerance((float) $p->steam_price, $salesPrice)
            );

            $matchedPrice ??= $candidates->first(function (Price $p) use ($salesPrice) {
                if (!$p->discount_percent || !$p->isDiscountActive()) {
                    return false;
                }
                return $this->isWithinTolerance((float) $p->discount_amount, $salesPrice);
            });

            if (!$matchedPrice) {
                Log::channel('order')->error('[SkuResolver] No matching child SKU found', [
                    'parent_sku'   => $parentSku,
                    'child_skus'   => $childSkus,
                    'price'        => $salesPrice,
                    'currency'     => $currencyCode,
                    'country_code' => $countryCode,
                ]);

                throw new RuntimeException(
                    "Cannot resolve parent SKU {$parentSku}: no active child SKU found "
                        . "for price {$salesPrice} {$currencyCode} in country {$countryCode}."
                );
            }

            $resolvedSku = (int) $matchedPrice->product_id;
            $snapshot    = $parentSku . ' -> [' . implode(', ', $childSkus) . ']';

            Log::channel('order')->info('[SkuResolver] Resolved successfully', [
                'parent_sku'   => $parentSku,
                'resolved_sku' => $resolvedSku,
                'snapshot'     => $snapshot,
                'price_id'     => $matchedPrice->id,
            ]);

            $transformedItems[] = array_merge($item, [
                'product_id'            => $resolvedSku,
                '_sku_mapping_snapshot' => $snapshot,
            ]);
        }

        return array_merge($payload, ['items' => $transformedItems]);
    }

    private function isWithinTolerance(float $a, float $b, float $tolerancePercent = 2.0): bool
    {
        $divisor = max(abs($a), abs($b));

        if ($divisor == 0.0) {
            return $a === $b;
        }

        return (abs($a - $b) / $divisor) * 100 <= $tolerancePercent;
    }
}
