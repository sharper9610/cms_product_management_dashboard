<?php

namespace App\DTOs\Wallet;

use App\Models\Customer;
use InvalidArgumentException;

class WithdrawalDTO
{
    public function __construct(
        public readonly Customer $customer,
        public readonly float $amount,
        public readonly string $referenceId,
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
            amount: $data['amount'],
            referenceId: $data['reference_id'],
            metadata: $data['metadata'] ?? [],
            storeId: $data['store_id'] ?? '2game_br',
            createdBy: $data['created_by'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
        );
    }

    /**
     * Validate amount
     */
    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw new InvalidArgumentException('Withdrawal amount must be greater than zero');
        }
    }
}