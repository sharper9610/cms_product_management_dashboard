<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Ensure no null values
     */
    private function cleanValue($value)
    {
        if (is_null($value)) return '';
        if (is_array($value)) return $value ?: [];
        return $value;
    }

    /**
     * Safely unserialize and return filtered array
     */
    private function safeArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }

        if (!$value) {
            return [];
        }

        $arr = is_string($value) ? @unserialize($value) : null;

        if (!is_array($arr)) {
            $arr = json_decode($value, true);
        }

        if (!is_array($arr)) {
            $arr = [];
        }

        $arr = array_filter($arr, fn($v) => $v !== null && $v !== '');

        return array_values($arr);
    }

    private function formatUniqueTags(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $tag) {
            if (!is_string($tag) || trim($tag) === '') {
                continue;
            }

            $clean = ucfirst(mb_strtolower(trim($tag)));

            $key = mb_strtolower($clean);

            $normalized[$key] = $clean;
        }

        return array_values($normalized);
    }


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pegiRatings = [];

        if (!empty($this->pegi_ratings)) {
            $ratingsArray = @unserialize($this->pegi_ratings);

            if (isset($ratingsArray['Rating']) && is_array($ratingsArray['Rating'])) {
                foreach ($ratingsArray['Rating'] as $r) {
                    if (isset($r['Type']) && str_starts_with($r['Type'], 'pegi')) {
                        $pegiRatings[] = [
                            'type' => $r['Type'] ?? '',
                            'text' => $r['Text'] ?? '',
                            'logo_url' => $r['Logo']['URL'] ?? '',
                        ];
                    }
                }
            }
        }

        return [
            'sku' => $this->cleanValue($this->sku),
            'product_type' => $this->cleanValue($this->product_type),
            'default_language' => $this->cleanValue($this->default_language),
            'allowed_countries' => $this->cleanValue($this->allowed_countries),
            'seo_url_name' => $this->cleanValue($this->seo_url_name),
            'ignore_update' => (bool) $this->ignore_update,

            'localizations' => $this->localizations->map(function ($loc) {
                return [
                    'locale' => $this->cleanValue($loc->locale),
                    'title' => $this->cleanValue($loc->title),
                    'short_description' => $this->cleanValue($loc->short_description),
                    'long_description' => $this->cleanValue($loc->long_description),
                    'seo_tags' => $this->safeArray($loc->seo_tags),
                    'genre_tags' => $this->formatUniqueTags(
                        $this->safeArray($loc->genre_tags)
                    ),
                    'franchise_tags' => $this->safeArray($loc->franchise_tags),
                    'system_requirements' => $loc->system_requirements ?: '',
                ];
            })->values(),

            'variants' => ($this->source == config('services.sources.incomm')) ? $this->conceptPrices() : [],

            'media' => [
                'images' => [
                    'boxshot' => [
                        'portrait' => $this->media
                            ->where('media_type', ProductMedia::TYPE_BOXSHOT)
                            ->where('image_orientation', ProductMedia::ORIENTATION_PORTRAIT)
                            ->map(fn($img) => [
                                'url' => $this->cleanValue($img->url),
                                'is_main' => $img->is_main ?? false,
                            ])->values(),

                        'landscape' => $this->media
                            ->where('media_type', ProductMedia::TYPE_BOXSHOT)
                            ->where('image_orientation', ProductMedia::ORIENTATION_LANDSCAPE)
                            ->map(fn($img) => [
                                'url' => $this->cleanValue($img->url),
                                'is_main' => $img->is_main ?? false,
                            ])->values(),
                    ],

                    'screenshot' => $this->media
                        ->where('media_type', ProductMedia::TYPE_SCREENSHOT)
                        ->map(fn($img) => [
                            'url' => $this->cleanValue($img->url),
                            'is_main' => $img->is_main ?? false,
                        ])->values(),
                ],

                'videos' => $this->media
                    ->where('media_type', ProductMedia::TYPE_VIDEOS)
                    ->map(fn($v) => ['url' => $this->cleanValue($v->url)])
                    ->values(),

                'videos_steam' => $this->media
                    ->where('media_type', ProductMedia::TYPE_VIDEOS)
                    ->where('media_source', ProductMedia::SOURCE_STEAM)
                    ->map(fn($v) => ['url' => $this->cleanValue($v->url)])
                    ->values(),
            ],

            'tags' => $this->tags->pluck('tag')->filter()->values(),

            'ratings' => [
                'metacritic_score' =>
                optional($this->ratings->first())->metacritic_score
                    ?? $this->average_rating
                    ?? 0,

                'metacritic_label' => $this->cleanValue(optional($this->ratings->first())->metacritic_label),
                'pegi_ratings' => $pegiRatings,
            ],

            'community_discussion' => [
                'discord_server' => $this->cleanValue($this->community_discussion),
            ],

            'metadata' => [
                'genres' => $this->genres ? $this->formatUniqueTags($this->genres) : [],
                'platform' => $this->platform ? array_map('trim', explode(',', $this->platform)) : [],
                'release_date' => $this->release_date ? date('Y-m-d', $this->release_date) : '',
                'system_requirements' => $this->cleanValue($this->system_requirements),
                'drm_type' => $this->cleanValue($this->platform),
                'developer' => $this->developers ?: [],
                'publisher' => $this->cleanValue($this->publisher_name),
                'status' => $this->status == 1 ? 'Active' : 'Inactive',
                'dlc' => (bool) $this->dlc_products,
                'dlc_products_ids' => $this->safeArray(($this->dlc_products ? (unserialize($this->dlc_products)['ProductID'] ?? []) : [])),
                'editions_products_ids' => array_map('intval', $this->safeArray($this->editions)),
                'supported_languages' => $this->supported_languages ?: [],
            ],

            'localization_needed' =>
            collect($this->allowed_currencies)->mapWithKeys(
                fn($currency) =>
                [$currency => config("localization.currency_locales.$currency", [])]
            )->toArray(),

            'last_updated' => $this->cleanValue($this->update_timestamp),
        ];
    }
}
