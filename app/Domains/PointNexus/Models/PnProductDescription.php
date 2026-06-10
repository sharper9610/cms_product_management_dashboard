<?php

namespace App\Domains\PointNexus\Models;

use Illuminate\Database\Eloquent\Model;

class PnProductDescription extends Model
{
    protected $connection = 'point_nexus';
    protected $table = 'product_descriptions';
    public $timestamps = false;
    protected $guarded = [];
}
