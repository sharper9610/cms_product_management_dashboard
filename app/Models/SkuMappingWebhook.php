<?php 


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkuMappingWebhook extends Model
{
    protected $fillable = [
        'event',
        'label',
        'kind',
        'scope',
        'count',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(SkuMapping::class, 'webhook_id');
    }
}