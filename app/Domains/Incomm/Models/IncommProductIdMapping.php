<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommProductIdMapping extends Model
{
    protected $connection = 'incomm';
    protected $table = 'product_id_mapping';
    public $timestamps = false;
    protected $guarded = [];
}
