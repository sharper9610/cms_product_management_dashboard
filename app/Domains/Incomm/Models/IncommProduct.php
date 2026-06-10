<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommProduct extends Model
{
    protected $connection = 'incomm';
    protected $table = 'product';
    public $timestamps = false;
    protected $guarded = [];

    public function idMapping()
    {
        return $this->hasOne(IncommProductIdMapping::class, 'productId', 'id');
    }

    public function cardImages()
    {
        return $this->hasMany(IncommCardImage::class, 'product_id', 'id');
    }

    public function commission()
    {
        return $this->hasOne(IncommProductCommission::class, 'product_id', 'id');
    }

    public function productLine()
    {
        return $this->belongsTo(IncommProductLine::class, 'product_line_id', 'id');
    }

    public function price(){
        return $this->hasOne(IncommPrice::class, 'productId', 'id');
    }
}
