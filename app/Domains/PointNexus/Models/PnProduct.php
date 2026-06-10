<?php

namespace App\Domains\PointNexus\Models;

use App\Domains\PointNexus\Models\PnPrice;
use Illuminate\Database\Eloquent\Model;

class PnProduct extends Model
{
    protected $connection = 'point_nexus';
    protected $table = 'products';
    public $timestamps = false;
    protected $guarded = [];


    public function prices(){
        return $this->hasMany(PnPrice::class, 'product_id', 'id');
    }

    public function description(){
        return $this->hasOne(PnProductDescription::class, 'product_id', 'id');
    }

    public function stocks(){
        return $this->hasMany(PnStock::class, 'product_id', 'id');
    }

    public function graphics(){
        return $this->hasMany(PnSteamGraphics::class, 'product_id', 'id');
    }
}
