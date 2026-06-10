<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Model;

class BasketItemGbp extends Model
{
    protected $connection = 'mysql_cms';
    protected $table = 'basket_item_gbp';

    public function basket()
    {
        return $this->belongsTo(BasketGbp::class, 'BasketID', 'BasketID');
    }

    public function product()
    {
        return $this->belongsTo(ProductGbp::class, 'ProductID', 'id');
    }
}