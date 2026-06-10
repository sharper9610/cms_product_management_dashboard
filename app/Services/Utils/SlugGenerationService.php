<?php

namespace App\Services\Utils;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class SlugGenerationService
{
    public function generateBaseSlug(string $name, ?string $platform): string
    {
        $parts = array_filter([$name, $platform]);

        return Str::slug(implode(' ', $parts));
    }

    public function generateUniqueSlug(string $baseSlug, int $attempt): string
    {
        if ($attempt === 0) {
            return $baseSlug;
        }

        return "{$baseSlug}-{$attempt}";
    }

    public function saveSlug(Product $product): bool
    {
        $baseSlug   = $this->generateBaseSlug($product->name, $product->platform);
        $maxRetries = 50;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $fresh = Product::findOrFail($product->id);
                $fresh->seo_url_name = $this->generateUniqueSlug($baseSlug, $attempt);
                $fresh->save();

                return true;
            } catch (UniqueConstraintViolationException $e) {
                continue;
            } catch (QueryException $e) {
                throw $e;
            }
        }

        return false;
    }

    public function generateForSku(int $sku): array
    {
        $products = Product::where('sku', $sku)->get();

        $result = ['found' => $products->count(), 'failed' => 0, 'skipped' => 0, 'slugs' => []];

        foreach ($products as $product) {
            if (empty($product->name)) {
                $result['skipped']++;
                continue;
            }

            $success = $this->saveSlug($product);

            if (!$success) {
                $result['failed']++;
            } else {
                $result['slugs'][$product->id] = $product->fresh()->seo_url_name;
            }
        }

        return $result;
    }

    public function generateForAll(array $sources, callable $onChunk): array
    {
        $stats = ['updated' => 0, 'skipped' => 0, 'failed' => 0];

        Product::whereIn('source', $sources)
            ->orderBy('id')
            ->chunkById(300, function ($products) use (&$stats, $onChunk) {
                foreach ($products as $product) {
                    if (empty($product->name)) {
                        $stats['skipped']++;
                        continue;
                    }

                    $success = $this->saveSlug($product);

                    if (!$success) {
                        $stats['failed']++;
                    } else {
                        $stats['updated']++;
                    }

                    $onChunk($product, $success);
                }
            });

        return $stats;
    }
}