<?php

namespace App\Enums;

enum WalletEventType: string
{
    case TOPUP = 'TOPUP';
    case BONUS = 'BONUS';
    case PURCHASE = 'PURCHASE';
    case WITHDRAWAL = 'WITHDRAWAL';
    case TOURNAMENT_WIN = 'TOURNAMENT_WIN';
    case REFUND = 'REFUND';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function label(): string
    {
        return match($this) {
            self::TOPUP => 'Top-up',
            self::BONUS => 'Bonus',
            self::PURCHASE => 'Purchase',
            self::WITHDRAWAL => 'Withdrawal',
            self::TOURNAMENT_WIN => 'Tournament Win',
            self::REFUND => 'Refund',
            self::ADJUSTMENT => 'Adjustment',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::TOPUP,
            self::BONUS,
            self::TOURNAMENT_WIN,
            self::REFUND,
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::WITHDRAWAL,
        ]);
    }
}