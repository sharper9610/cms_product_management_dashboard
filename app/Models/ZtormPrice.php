<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZtormPrice extends Model
{
    const STATUS_ACTIVE = 1;

    protected $connection = 'mysql_cms';

    protected $table = 'prices';

    protected $guarded = [];
}
