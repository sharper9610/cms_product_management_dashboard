<?php

namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GenbaProduct extends Model
{
    protected $connection = 'genba';
    protected $table      = 'products';

    protected $casts = [
        'release_date'          => 'datetime',
        'digital_release_date'  => 'datetime',
        'estimated_release_date' => 'datetime',
        'is_bundle'             => 'boolean',
        'is_active'             => 'boolean',
        'stock_status'          => 'boolean',
    ];


    public function prices(): HasMany
    {
        return $this->hasMany(GenbaPrice::class, 'product_id', 'id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(GenbaProductLanguage::class, 'product_id', 'id');
    }

    public function graphics(): HasMany
    {
        return $this->hasMany(GenbaProductGraphic::class, 'product_id', 'id');
    }

    public function rating(): HasMany
    {
        return $this->hasMany(GenbaProductRating::class, 'product_id', 'id');
    }

    public function countryRestrictions(): HasOne
    {
        return $this->hasOne(GenbaProductCountryRestriction::class, 'product_id', 'id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(GenbaProductVideo::class, 'product_id', 'id');
    }




    public function primaryLanguage(): ?GenbaProductLanguage
    {
        return $this->languages->firstWhere('language_name', 'English')
            ?? $this->languages->first();
    }


    public function spokenLanguages(): array
    {
        $data = json_decode($this->localisation_set ?? '{}', true);

        return $data['SpokenLanguageSet'] ?? [];
    }
}
