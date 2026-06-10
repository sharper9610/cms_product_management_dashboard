<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PnProductStock extends Model
{
    protected $fillable = [
        'product_id',
        'countries',
        'qty',
        'geolock',
        'stock_update_timestamp',
    ];
}
