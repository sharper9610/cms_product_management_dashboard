<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Model;

class BasketGbp extends Model
{
    protected $connection = 'mysql_cms';
    protected $table = 'basket_gbp';

    public function items()
    {
        return $this->hasMany(BasketItemGbp::class, 'BasketID', 'BasketID');
    }
}