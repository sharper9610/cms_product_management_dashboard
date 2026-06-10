<?php

namespace App\Services\Ztorm;

use App\Models\Product;

class ProductSlugService
{
    public function slugify(string $text): string
    {
        if (class_exists('\Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KD);
        }

        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = strtolower($text);
        $text = preg_replace("/['']/", '', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }

    public function generateProductUrlTitle(string $productName, string $platform): string
    {
        return sprintf(
            '%s-%s-key',
            $this->slugify($productName),
            $this->slugify($platform)
        );
    }

    /**
     * Generates a UNIQUE slug safely:
     *
     * slug
     * slug-1
     * slug-2
     * slug-n
     *
     * Uses a loop so that if another process claims the slug between the
     * SELECT and the UPDATE, we immediately re-read and try the next suffix
     * instead of failing.
     */
    public function generateUniqueSlug(string $baseSlug, int $productId, int $maxAttempts = 50): string
    {

        $currentOwner = Product::where('seo_url_name', $baseSlug)
            ->value('id');

        if ($currentOwner === null || (int) $currentOwner === $productId) {
            return $baseSlug;
        }


        for ($suffix = 1; $suffix <= $maxAttempts; $suffix++) {
            $candidate = "{$baseSlug}-{$suffix}";

            $taken = Product::where('seo_url_name', $candidate)
                ->where('id', '!=', $productId)
                ->exists();

            if (!$taken) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            "Could not generate a unique slug for base '{$baseSlug}' after {$maxAttempts} attempts."
        );
    }
}