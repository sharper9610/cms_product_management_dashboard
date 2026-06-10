<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Resources\Json\JsonResource;

class ZtormPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        // country_codes comes as a GROUP_CONCAT string e.g. "US,CA,GB"
        $countries = !empty($this->country_codes)
            ? explode(',', $this->country_codes)
            : [];

        $variant = ProductVariant::where('price', $this->final_price)
            ->whereHas('product', function ($q) {
                $q->where('sku', $this->product_id);
            })
            ->first();


        $data = [
            'currency'               => $this->currency,
            'price'                  => $this->final_price,
            'countries'              => $countries,

            'shopify_product_id'     => $variant?->shopify_product_id,
            'shopify_variant_id'     => $variant?->shopify_variant_id,

            'discount_percent'       => $this->discount_percent,
            'discount_valid_from'    => (int) $this->discount_valid_from,
            'discount_valid_to'      => (int) $this->discount_valid_to,
            'price_update_timestamp' => (int) $this->price_update_timestamp,
        ];

        // Add region USD_LATAM if currency is USD
        if (strtoupper($this->currency) === 'USD') {
            $data['region'] = 'USD_LATAM';
        }

        return $data;
    }
}
