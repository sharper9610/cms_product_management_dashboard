<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommProductLine extends Model
{
    protected $connection = 'incomm';
    protected $table = 'product_line';
    public $timestamps = false;
    protected $guarded = [];
}
