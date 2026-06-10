<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ZtormProduct extends Model
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_DISCONTINUED = 'Discontinued';

    protected $connection = 'mysql_cms';

    protected $table = 'product_gbp';

    protected $guarded = [];

    public function getGenresAttribute($value)
    {
        return @unserialize($value);
    }

    public function getScreenshotsS3Attribute($value)
    {
        return @unserialize($value);
    }

    public function getBoxshotsS3Attribute($value)
    {
        return @unserialize($value);
    }

    public function getBoxshotsSteamS3Attribute($value)
    {
        return @unserialize($value);
    }

    public function getBoxshotsCustomS3Attribute($value)
    {
        return @unserialize($value);
    }

    public function getIsDLCAttribute($value)
    {
        return $value == 'true' ? true : false;
    }

    public function getStatusAttribute($value)
    {
        return $value == self::STATUS_ACTIVE ? true : false;
    }

    public function getGenres($lang='en')
    {
        if (! isset($this->Genres['Genre'])) {
            return [];
        }

        $filter = array_filter($this->Genres['Genre'], function ($item) use ($lang) {
            return isset($item['attributes']['lang']) ?
                $item['attributes']['lang'] == $lang : false;
        });

        return Arr::pluck($filter, 'value');
    }

    public function getGenresArr($lang='en')
    {
        $arr = $this->getGenres();

        if (empty($arr)) {
            return null;
        }

        return serialize($arr);
    }

    public function getVideos()
    {
        $output = [];
        $videos = unserialize($this->getOriginal('Videos'));
        if (isset($videos['Video'])) {
            if(isset($videos['Video'][0])) {
                foreach ($videos['Video'] as $video) {
                    $output[] = $this->getYoutubeURL($video);
                }
            } else {
                $output[] = $this->getYoutubeURL($videos['Video']);
            }
        }

        return $output;
    }

    public function getYoutubeURL($video)
    {
        return isset($video['URL']) ? $video['URL'] : null;
    }

    public function editions()
    {
        return $this->hasOne(ZtormProductEdition::class, 'product_id');
    }

    public function descriptions()
    {
        return $this->hasMany(ZtormProductDescription::class, 'ProductID', 'id');
    }
  
    public function videos()
    {
        return $this->hasMany(ZtormProductImage::class, 'product_id', 'id')
            ->where('type', ZtormProductImage::TYPE_VIDEO);
    }
}
