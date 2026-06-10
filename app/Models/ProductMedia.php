<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    const SOURCE_ZTORM = 1;
    const SOURCE_INCOMM = 2;
    const SOURCE_MANUAL = 3;
    const SOURCE_STEAM = 4;

    const TYPE_IMAGES = 1;
    const TYPE_VIDEOS = 2;
    const TYPE_BOXSHOT = 3;
    const TYPE_SCREENSHOT = 4;
    const TYPE_STEAM_VIDEOS = 5;

    const ORIENTATION_PORTRAIT = 1;
    const ORIENTATION_LANDSCAPE = 2;

    // const TYPE_STEAM_VIDEOS = 3;

    protected $table = 'product_media';

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'sku');
    }

    public function scopeZtorm($query)
    {
        return $query->where('media_source', self::SOURCE_ZTORM);
    }

    public function scopeIncomm($query)
    {
        return $query->where('media_source', self::SOURCE_INCOMM);
    }

    public function scopeSteam($query)
    {
        return $query->where('media_source', self::SOURCE_STEAM);
    }

    public function scopeManual($query)
    {
        return $query->where('media_source', self::SOURCE_MANUAL);
    }

    public function scopeImages($query)
    {
        return $query->where('media_type', self::TYPE_IMAGES);
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', self::TYPE_VIDEOS);
    }

    public function scopeBoxshots($query)
    {
        return $query->where('media_type', self::TYPE_BOXSHOT);
    }

    public function scopeScreenshots($query)
    {
        return $query->where('media_type', self::TYPE_SCREENSHOT);
    }
}
