<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZtormProductDescription extends Model
{
    protected $connection = 'mysql_cms';

    protected $table = 'product_descriptions';

    protected $guarded = [];
}
