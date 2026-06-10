<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsSkipUpdate extends Model
{
    // Table name (optional if it follows Laravel naming convention)
    protected $table = 'products_skip_updates';

    // Fields that can be mass-assigned
    protected $fillable = [
        'product_id',
        'field_name',
        'skip_update',
    ];

    // Cast skip_update to boolean automatically
    protected $casts = [
        'skip_update' => 'boolean',
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
