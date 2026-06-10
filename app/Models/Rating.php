<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
  protected $table = 'ratings';

  protected $fillable = [
    'product_id',
    'metacritic_score',
    'metacritic_label',
    'created_at',
    'updated_at'
  ];

  // Belongs to product
  public function product()
  {
    return $this->belongsTo(Product::class, 'product_id', 'sku');
  }
}
