<?php

namespace App\Domains\Incomm\Models;

use Illuminate\Database\Eloquent\Model;

class IncommCardImage extends Model
{
    protected $connection = 'incomm';
    protected $table = 'card_image';
    public $timestamps = false;
    protected $guarded = [];
}
