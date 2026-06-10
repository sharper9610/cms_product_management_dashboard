<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'shopify_product_id',
        'shopify_variant_id',
        'price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
