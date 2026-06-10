<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommPrice extends Model
{
    protected $connection = 'incomm';
    protected $table = 'price';
    public $timestamps = false;
    protected $guarded = [];
}
