<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shopify_customer_id',
        'shopify_legacy_id',
        'email',
        'first_name',
        'last_name',
        'display_name',
        'phone',
        'locale',
        'state',
        'tax_exempt',
        'verified_email',
        'note',
        'tags',
        'amount_spent',
        'number_of_orders',
        'shopify_created_at',
        'shopify_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'tax_exempt' => 'boolean',
        'verified_email' => 'boolean',
        'amount_spent' => 'decimal:2',
        'number_of_orders' => 'integer',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    // Relationships
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function metafields(): HasMany
    {
        return $this->hasMany(CustomerMetafield::class);
    }

    // Scopes
    public function scopeByShopifyId($query, string $shopifyCustomerId)
    {
        return $query->where('shopify_customer_id', $shopifyCustomerId);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeActive($query)
    {
        return $query->where('state', 'ENABLED');
    }

    // Helpers
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->display_name ?: $this->email;
    }

    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function isActive(): bool
    {
        return $this->state === 'ENABLED' && !$this->trashed();
    }

    // Metafield helpers
    public function getMetafield(string $namespace, string $key): ?CustomerMetafield
    {
        return $this->metafields()
            ->where('namespace', $namespace)
            ->where('key', $key)
            ->first();
    }

    public function setMetafield(string $namespace, string $key, $value, string $type = 'string'): CustomerMetafield
    {
        return $this->metafields()->updateOrCreate(
            ['namespace' => $namespace, 'key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type
            ]
        );
    }


    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get or create wallet for this customer
     */
    public function getOrCreateWallet(string $storeId = '2game_br'): Wallet
    {
        return $this->wallet()->firstOrCreate(
            ['store_id' => $storeId],
            [
                'currency' => $this->getCurrencyByStore($storeId),
                'is_active' => true,
            ]
        );
    }

    /**
     * Get currency based on store
     */
    private function getCurrencyByStore(string $storeId): string
    {
        return match ($storeId) {
            '2game_br' => 'BRL',
            default => 'BRL',
        };
    }
}
