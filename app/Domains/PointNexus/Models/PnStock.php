<?php

namespace App\Domains\PointNexus\Models;

use Illuminate\Database\Eloquent\Model;

class PnStock extends Model
{
    protected $connection = 'point_nexus';
    protected $table = 'stocks';
    public $timestamps = false;
    protected $guarded = [];
}
