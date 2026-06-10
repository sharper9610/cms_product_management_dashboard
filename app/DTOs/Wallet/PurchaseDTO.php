<?php

namespace App\DTOs\Wallet;

use App\Models\Customer;
use InvalidArgumentException;

class PurchaseDTO
{
    public function __construct(
        public readonly Customer $customer,
        public readonly float $bonusToUse,
        public readonly float $cashToUse,
        public readonly string $orderId,
        public readonly array $metadata = [],
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
            bonusToUse: $data['bonus_to_use'],
            cashToUse: $data['cash_to_use'],
            orderId: $data['order_id'],
            metadata: $data['metadata'] ?? [],
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
        return $this->bonusToUse + $this->cashToUse;
    }

    /**
     * Validate amounts
     */
    public function validate(): void
    {
        if ($this->bonusToUse < 0 || $this->cashToUse < 0) {
            throw new InvalidArgumentException('Purchase amounts cannot be negative');
        }

        if ($this->bonusToUse <= 0 && $this->cashToUse <= 0) {
            throw new InvalidArgumentException('Purchase amount must be greater than zero');
        }
    }
}