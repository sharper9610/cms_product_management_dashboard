<?php

namespace App\Http\Resources;

use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncommPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->product?->variants
            ->where('price', $this->price)
            ->first();


        return [
            'currency'               => $this->currency,
            'price'                  => $this->price,
            'title'                  => $this->title,
            'countries'              => Price::getCountryCodesByCurrency($this->product_id, $this->currency),

            'shopify_product_id'     => $variant?->shopify_product_id,
            'shopify_variant_id'     => $variant?->shopify_variant_id,
            'discount_percent'       => $this->discount_percent ?? '',
            'discount_valid_from'    => (int) ($this->discount_valid_from ?? 0),
            'discount_valid_to'      => (int) ($this->discount_valid_to ?? 0),
            'price_update_timestamp' => (int) ($this->price_update_timestamp ?? 0),
        ];
    }
}
