<?php

namespace App\Http\Resources;

use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResourceV2 extends JsonResource
{


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

    private function enTags(): array
    {
        $loc = $this->localizations->firstWhere('locale', 'en');

        return collect($this->safeArray($loc?->genre_tags))
            ->map(fn($t) => strtolower(trim($t)))
            ->values()
            ->toArray();
    }

    private function getPegiRating(): ?int
    {
        if (empty($this->pegi_ratings)) {
            return null;
        }

        $data = @unserialize($this->pegi_ratings);

        if (!is_array($data) || empty($data['Rating'])) {
            return null;
        }

        foreach ($data['Rating'] as $rating) {
            if (($rating['Type'] ?? '') === 'pegi') {
                // Extract number from "PEGI 12"
                if (!empty($rating['Text']) && preg_match('/\d+/', $rating['Text'], $m)) {
                    return (int) $m[0];
                }
            }
        }

        return null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $localization = $this->localizations->first();
        $cover = $this->media
                    ->where('media_type', ProductMedia::TYPE_BOXSHOT)
                    ->sortByDesc('is_main')
                    ->first();

        return [
            'id' => (int) $this->sku,

            'slug' => $this->seo_url_name,

            'type' => $this->product_type ?: 'game',

            'platform' => strtolower($this->platform ?? 'steam'),

            'activation' => 'steam_key',

            'release' => $this->release_date
                ? date('Y-m-d', $this->release_date)
                : null,

            'title' => [
                'en' => $localization?->title ?? '',
            ],

            'cover' => $cover?->url,

            'developer' => is_array($this->developers)
                ? implode(', ', $this->developers)
                : $this->developers,

            'publisher' => $this->publisher_name,

            'tags' => $this->enTags(),

            'age' => [
                'system' => 'pegi',
                'value' => $this->getPegiRating(),
            ],
        ];
    }
}
