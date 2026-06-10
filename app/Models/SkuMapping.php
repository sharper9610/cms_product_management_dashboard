<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuMapping extends Model
{
    protected $fillable = [
        'webhook_id',
        'parent_sku',
        'child_skus',
        'mapped_at',
    ];

    protected $casts = [
        'parent_sku' => 'integer',
        'child_skus' => 'array',
        'mapped_at'  => 'datetime',
    ];



    public function webhook(): BelongsTo
    {
        return $this->belongsTo(SkuMappingWebhook::class, 'webhook_id');
    }
}
