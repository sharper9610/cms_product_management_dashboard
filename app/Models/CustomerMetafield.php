<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMetafield extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'shopify_metafield_id',
        'namespace',
        'key',
        'value',
        'type',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getParsedValueAttribute()
    {
        return match($this->type) {
            'json' => json_decode($this->value, true),
            'integer' => (int) $this->value,
            'number_decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            default => $this->value,
        };
    }
}