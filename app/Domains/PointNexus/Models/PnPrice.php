<?php

namespace App\Domains\PointNexus\Models;

use Illuminate\Database\Eloquent\Model;

class PnPrice extends Model
{
    protected $connection = 'point_nexus';
    protected $table = 'prices';
    public $timestamps = false;
    protected $guarded = [];
}
