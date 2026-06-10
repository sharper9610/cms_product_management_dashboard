<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Model;

class ProductGbp extends Model
{
    protected $connection = 'mysql_cms';
    protected $table = 'product_gbp';

    public function basketItems()
    {
        return $this->hasMany(BasketItemGbp::class, 'ProductID', 'id');
    }
}