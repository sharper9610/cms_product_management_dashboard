<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZtormProductImage extends Model
{
    const TYPE_BOXSHOT = 1;
    const TYPE_SCREENSHOT = 2;
    const TYPE_VIDEO = 3;

    const SOURCE_ZTORM = 1;
    const SOURCE_STEAM = 2;
    const SOURCE_MANUAL_UPLOAD = 3;

    const ACTIVE = 1;
    const INACTIVE = 0;

    protected $connection = 'mysql_cms';

    protected $table = 'product_images';

    protected $guarded = [];
}
