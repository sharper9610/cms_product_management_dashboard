<?php

namespace App\DTOs\Wallet;

use App\Enums\WalletEventType;
use App\Models\Customer;
use InvalidArgumentException;

class WalletTransactionDTO
{
    public function __construct(
        public readonly Customer $customer,
        public readonly WalletEventType $type,
        public readonly float $ribCash = 0,
        public readonly float $topupCash = 0,
        public readonly float $bonus = 0,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?array $metadata = null,
        public readonly ?string $description = null,
        public readonly string $storeId = '2game_br',
        public readonly ?string $createdBy = null,
        public readonly ?string $ipAddress = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customer: $data['customer'],
            type: $data['type'] instanceof WalletEventType ? $data['type'] : WalletEventType::from($data['type']),
            ribCash: $data['rib_cash'] ?? 0,
            topupCash: $data['topup_cash'] ?? 0,
            bonus: $data['bonus'] ?? 0,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            description: $data['description'] ?? null,
            storeId: $data['store_id'] ?? '2game_br',
            createdBy: $data['created_by'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
        );
    }

    /**
     * Get total amount
     */
    public function getTotalAmount(): float
    {
        return $this->ribCash + $this->topupCash + $this->bonus;
    }

    /**
     * Validate amounts are positive
     */
    public function validatePositiveAmounts(): void
    {
        if ($this->ribCash < 0 || $this->topupCash < 0 || $this->bonus < 0) {
            throw new InvalidArgumentException('Balance amounts cannot be negative');
        }
    }

    /**
     * Validate at least one amount is positive
     */
    public function validateHasAmount(): void
    {
        if ($this->ribCash <= 0 && $this->topupCash <= 0 && $this->bonus <= 0) {
            throw new InvalidArgumentException('At least one balance amount must be greater than zero');
        }
    }
}