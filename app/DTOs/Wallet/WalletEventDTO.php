<?php

namespace App\DTOs\Wallet;

use App\Enums\WalletEventType;
use App\Models\Wallet;

class WalletEventDTO
{
    public function __construct(
        public readonly Wallet $wallet,
        public readonly WalletEventType $type,

        // deltas
        public readonly float $ribCashDelta,
        public readonly float $topupCashDelta,
        public readonly float $bonusDelta,

        // balances
        public readonly float $ribCashBalance,
        public readonly float $topupCashBalance,
        public readonly float $bonusBalance,

        // meta
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?array $metadata = null,
        public readonly ?string $description = null,
        public readonly ?string $createdBy = null,
        public readonly ?string $ipAddress = null,
    ) {}
}
