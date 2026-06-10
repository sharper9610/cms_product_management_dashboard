<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Option extends Model
{
    const CACHE_PREFIX = '_option_';

    public $timestamps = false;

    protected $casts = [
        'value' => 'json',
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function exists($key)
    {
        return self::where('key', $key)->exists();
    }

    public static function get($key, $default = null)
    {
        if ($option = self::where('key', $key)->first()) {
            return $option->value;
        }

        return $default;
    }

    public static function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {

            Cache::forget(self::CACHE_PREFIX.$key);

            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public static function remove($key)
    {
        $result = (bool) self::where('key', $key)->delete();

        if ($result) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }

        return $result;
    }

    public static function getCached($key)
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
            return static::get($key);
        });
    }
}
