<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommProductCommission extends Model
{
    protected $connection = 'incomm';
    protected $table = 'product_commission';
    public $timestamps = false;
    protected $guarded = [];
}
