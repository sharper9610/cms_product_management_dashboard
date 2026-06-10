<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'store_id',
        'rib_cash',
        'topup_cash',
        'bonus',
        'currency',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'rib_cash' => 'decimal:2',
        'topup_cash' => 'decimal:2',
        'bonus' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
    ];

    protected $appends = [
        'total_cash',
        'formatted_balances',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WalletEvent::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, string $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Accessors
    public function getTotalCashAttribute(): float
    {
        return (float) ($this->rib_cash + $this->topup_cash);
    }

    public function getFormattedBalancesAttribute(): array
    {
        return [
            'rib_cash' => [
                'amount' => (float) $this->rib_cash,
                'formatted' => $this->formatCurrency($this->rib_cash),
                'type' => 'withdrawable',
            ],
            'topup_cash' => [
                'amount' => (float) $this->topup_cash,
                'formatted' => $this->formatCurrency($this->topup_cash),
                'type' => 'non-withdrawable',
            ],
            'bonus' => [
                'amount' => (float) $this->bonus,
                'formatted' => $this->formatCurrency($this->bonus),
                'type' => 'restricted',
            ],
            'total' => [
                'amount' => (float) $this->total_balance,
                'formatted' => $this->formatCurrency($this->total_balance),
            ],
        ];
    }

    // Helpers
    public function hasBalanceFor(float $amount, bool $includingBonus = false): bool
    {
        $availableCash = $this->total_cash;
        
        if ($includingBonus) {
            return ($availableCash + $this->bonus) >= $amount;
        }
        
        return $availableCash >= $amount;
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->rib_cash >= $amount && $amount > 0;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    private function formatCurrency(float $amount): string
    {
        $symbol = match($this->currency) {
            'BRL' => 'R$',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency,
        };
        
        return $symbol . ' ' . number_format($amount, 2, ',', '.');
    }

    public function updateLastTransaction(): void
    {
        $this->update(['last_transaction_at' => now()]);
    }
}