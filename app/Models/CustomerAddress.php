<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'shopify_address_id',
        'address1',
        'address2',
        'city',
        'province',
        'province_code',
        'country',
        'country_code',
        'zip',
        'phone',
        'company',
        'first_name',
        'last_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFormattedAddressAttribute(): string
    {
        return collect([
            $this->address1,
            $this->address2,
            $this->city,
            $this->province,
            $this->zip,
            $this->country,
        ])->filter()->implode(', ');
    }

    protected static function boot()
    {
        parent::boot();

        // Ensure only one default address per customer
        static::creating(function ($address) {
            if ($address->is_default) {
                static::where('customer_id', $address->customer_id)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            if ($address->is_default && $address->isDirty('is_default')) {
                static::where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}