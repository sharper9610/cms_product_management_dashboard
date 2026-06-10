<?php

namespace App\DTOs\Wallet;

use App\Models\Customer;

class RefundDTO
{
    public function __construct(
        public readonly Customer $customer,
        public readonly string $orderId,
        public readonly float $ribCash = 0,
        public readonly float $topupCash = 0,
        public readonly float $bonus = 0,
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
            orderId: $data['order_id'],
            ribCash: $data['rib_cash'] ?? 0,
            topupCash: $data['topup_cash'] ?? 0,
            bonus: $data['bonus'] ?? 0,
            metadata: $data['metadata'] ?? [],
            storeId: $data['store_id'] ?? '2game_br',
            createdBy: $data['created_by'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
        );
    }

    /**
     * Get total refund amount
     */
    public function getTotalAmount(): float
    {
        return $this->ribCash + $this->topupCash + $this->bonus;
    }
}