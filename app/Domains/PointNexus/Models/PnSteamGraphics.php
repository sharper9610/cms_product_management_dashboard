<?php

namespace App\Domains\PointNexus\Models;

use Illuminate\Database\Eloquent\Model;

class PnSteamGraphics extends Model
{
    protected $connection = 'point_nexus';
    protected $table = 'pn_steam_media';
    public $timestamps = false;
    protected $guarded = [];
}
