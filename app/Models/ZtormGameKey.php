<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZtormGameKey extends Model
{
    protected $connection = 'mysql_cms';

    protected $table = 'game_key';

    protected $guarded = [];
}
