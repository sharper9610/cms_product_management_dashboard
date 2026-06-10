<?php

namespace App\Http\Resources;

use App\Models\Localization;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResourceV3 extends JsonResource
{
    private function cleanValue($value)
    {
        if (is_null($value)) return '';
        if (is_array($value)) return $value ?: [];
        return $value;
    }

    private function safeArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }

        if (!$value) return [];

        $arr = is_string($value) ? @unserialize($value) : null;

        if (!is_array($arr)) {
            $arr = json_decode($value, true);
        }

        if (!is_array($arr)) {
            $arr = [];
        }

        return array_values(array_filter($arr, fn($v) => $v !== null && $v !== ''));
    }

    private function getPegiRatings(): array
    {
        $pegi = [];

        if (!empty($this->pegi_ratings)) {
            $ratingsArray = @unserialize($this->pegi_ratings);

            if (isset($ratingsArray['Rating']) && is_array($ratingsArray['Rating'])) {
                foreach ($ratingsArray['Rating'] as $r) {
                    if (isset($r['Type']) && str_starts_with($r['Type'], 'pegi')) {
                        $pegi[] = [
                            'type' => $r['Type'] ?? '',
                            'text' => $r['Text'] ?? '',
                            'logo_url' => $r['Logo']['URL'] ?? '',
                        ];
                    }
                }
            }
        }

        return $pegi;
    }

    private function getLocalizedTitles(): array
    {
        if (empty($this->sku)) {
            return [];
        }

        return Localization::query()
            ->where('product_id', $this->sku)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->pluck('title', 'locale')
            ->map(fn($title) => $this->cleanValue($title))
            ->toArray();
    }

    public function toArray(Request $request): array
    {
        $titles = $this->getLocalizedTitles();

        return [
            'product_id' => (int) $this->id,
            'sku' => $this->cleanValue($this->sku),
            'product_type' => $this->cleanValue($this->product_type),

            'title' => $this->name,

            'localized_titles' => !empty($titles) ? $titles : ['en' => $this->name],

            'seo_url_name' => $this->cleanValue($this->seo_url_name),

            'default_language' => $this->cleanValue($this->default_language),

            'platform' => $this->platform
                ? array_map('trim', explode(',', $this->platform))
                : [],

            'publisher' => $this->cleanValue($this->publisher_name),
            'developer' => is_array($this->developers)
                ? $this->developers
                : [$this->developers],

            'release_date' => $this->release_date
                ? date('Y-m-d', $this->release_date)
                : '',

            'status' => $this->status == 1 ? 'active' : 'inactive',

            'is_dlc' => (bool) $this->is_dlc,

            'dlc_product_ids' => $this->safeArray(
                $this->dlc_products
                    ? (unserialize($this->dlc_products)['ProductID'] ?? [])
                    : []
            ),

            'dlc_master_product_id' => (int) ($this->dlc_master_product_id ?? 0),

            'editions_product_ids' => array_map(
                'intval',
                $this->safeArray($this->editions)
            ),

            'availability' => [
                'allowed_countries' => $this->safeArray($this->allowed_countries) ?? [],
            ],

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
                    ->map(fn($v) => [
                        'url' => $this->cleanValue($v->url),
                    ])->values(),

                'videos_steam' => $this->media
                    ->where('media_type', ProductMedia::TYPE_VIDEOS)
                    ->where('media_source', ProductMedia::SOURCE_STEAM)
                    ->map(fn($v) => [
                        'url' => $this->cleanValue($v->url),
                    ])->values(),
            ],

            'ratings' => [
                'pegi' => $this->getPegiRatings(),
            ],

            'supplier' => (int) $this->publisher_id,

            'last_updated' => (int) $this->update_timestamp,
        ];
    }
}
