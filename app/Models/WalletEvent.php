<?php

namespace App\Models;

use App\Enums\WalletEventType;
use App\Enums\WalletReferenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'type',
        'rib_cash_delta',
        'topup_cash_delta',
        'bonus_delta',
        'rib_cash_balance',
        'topup_cash_balance',
        'bonus_balance',
        'reference_type',
        'reference_id',
        'metadata',
        'description',
        'created_by',
        'ip_address',
    ];

    protected $casts = [
        'type' => WalletEventType::class,
        'reference_type' => WalletReferenceType::class,
        'rib_cash_delta' => 'decimal:2',
        'topup_cash_delta' => 'decimal:2',
        'bonus_delta' => 'decimal:2',
        'rib_cash_balance' => 'decimal:2',
        'topup_cash_balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByType($query, WalletEventType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByReferenceType($query, WalletReferenceType $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getTotalDeltaAttribute(): float
    {
        return (float) ($this->rib_cash_delta + $this->topup_cash_delta + $this->bonus_delta);
    }

    public function getTotalBalanceAttribute(): float
    {
        return (float) ($this->rib_cash_balance + $this->topup_cash_balance + $this->bonus_balance);
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->type->isDebit();
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->type->isCredit();
    }
}