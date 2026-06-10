<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
  protected $table = 'tags';

  protected $fillable = [
    'product_id', 'tag', 'created_at', 'updated_at'
  ];

  // Belongs to product
  public function product()
  {
    return $this->belongsTo(Product::class, 'product_id', 'sku');
  }
}
