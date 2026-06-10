<?php

namespace App\Enums;

enum WalletReferenceType: string
{
    case SHOPIFY = 'shopify';
    case RIB = 'rib';
    case ADMIN = 'admin';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match($this) {
            self::SHOPIFY => 'Shopify',
            self::RIB => 'RIB',
            self::ADMIN => 'Admin',
            self::SYSTEM => 'System',
        };
    }
}