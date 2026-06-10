<?php

namespace App\Services\Customer;

use App\DTOs\Wallet\PurchaseDTO;
use App\DTOs\Wallet\RefundDTO;
use App\DTOs\Wallet\WalletEventDTO;
use App\DTOs\Wallet\WalletTransactionDTO;
use App\DTOs\Wallet\WithdrawalDTO;
use App\Enums\WalletEventType;
use App\Enums\WalletReferenceType;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletEvent;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WalletService
{

    /**
     * Get or create wallet for customer
     */
    public function getOrCreateWallet(Customer $customer, string $storeId = '2game_br'): Wallet
    {
        return $customer->wallet()->firstOrCreate(
            ['store_id' => $storeId],
            [
                'currency' => $this->getCurrencyByStore($storeId),
                'is_active' => true,
            ]
        );
    }

    /**
     * Get wallet balances
     */
    public function getBalances(Customer $customer, string $storeId = '2game_br'): array
    {
        $wallet = $this->getOrCreateWallet($customer, $storeId);

        return [
            'rib_cash' => (float) $wallet->rib_cash,
            'topup_cash' => (float) $wallet->topup_cash,
            'bonus' => (float) $wallet->bonus,
            'total_balance' => (float) $wallet->total_balance,
            'total_cash' => (float) $wallet->total_cash,
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
            'last_transaction_at' => $wallet->last_transaction_at?->toIso8601String(),
        ];
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

    /**
     * Add balance (TOPUP, BONUS, TOURNAMENT_WIN, REFUND)
     */
    public function addBalance(WalletTransactionDTO $dto): WalletEvent
    {
        return DB::transaction(function () use ($dto) {
            // Validate amounts
            $dto->validatePositiveAmounts();
            $dto->validateHasAmount();

            $wallet = $this->getOrCreateWallet($dto->customer, $dto->storeId);

            // Update wallet balances
            $wallet->increment('rib_cash', $dto->ribCash);
            $wallet->increment('topup_cash', $dto->topupCash);
            $wallet->increment('bonus', $dto->bonus);
            $wallet->updateLastTransaction();
            $wallet->refresh();

            // Create event
            $event = $this->createEvent(new WalletEventDTO(
                wallet: $wallet,
                type: $dto->type,

                ribCashDelta: $dto->ribCash,
                topupCashDelta: $dto->topupCash,
                bonusDelta: $dto->bonus,

                ribCashBalance: $wallet->rib_cash,
                topupCashBalance: $wallet->topup_cash,
                bonusBalance: $wallet->bonus,

                referenceType: $dto->referenceType,
                referenceId: $dto->referenceId,
                metadata: $dto->metadata,
                description: $dto->description,

                createdBy: $dto->createdBy,
                ipAddress: $dto->ipAddress,
            ));

            Log::info('Wallet balance added', [
                'customer_id' => $dto->customer->id,
                'wallet_id' => $wallet->id,
                'event_id' => $event->id,
                'type' => $dto->type->value,
                'total_amount' => $dto->getTotalAmount(),
            ]);

            return $event;
        });
    }


    /**
     * Deduct balance (PURCHASE, WITHDRAWAL, ADJUSTMENT)
     */
    public function deductBalance(WalletTransactionDTO $dto): WalletEvent
    {
        return DB::transaction(function () use ($dto) {
            // Validate amounts
            $dto->validatePositiveAmounts();
            $dto->validateHasAmount();

            $wallet = $this->getOrCreateWallet($dto->customer, $dto->storeId);

            // Validate sufficient balance
            $this->validateSufficientBalance($wallet, $dto->ribCash, $dto->topupCash, $dto->bonus);

            // Deduct wallet balances
            $wallet->decrement('rib_cash', $dto->ribCash);
            $wallet->decrement('topup_cash', $dto->topupCash);
            $wallet->decrement('bonus', $dto->bonus);
            $wallet->updateLastTransaction();
            $wallet->refresh();

            // Create event (negative deltas)
            $event = $this->createEvent(new WalletEventDTO(
                wallet: $wallet,
                type: $dto->type,

                ribCashDelta: -$dto->ribCash,
                topupCashDelta: -$dto->topupCash,
                bonusDelta: -$dto->bonus,

                ribCashBalance: $wallet->rib_cash,
                topupCashBalance: $wallet->topup_cash,
                bonusBalance: $wallet->bonus,

                referenceType: $dto->referenceType,
                referenceId: $dto->referenceId,
                metadata: $dto->metadata,
                description: $dto->description,

                createdBy: $dto->createdBy,
                ipAddress: $dto->ipAddress,
            ));

            Log::info('Wallet balance deducted', [
                'customer_id' => $dto->customer->id,
                'wallet_id' => $wallet->id,
                'event_id' => $event->id,
                'type' => $dto->type->value,
                'total_amount' => $dto->getTotalAmount(),
            ]);

            return $event;
        });
    }






    /**
     * Process purchase (deduct with allocation plan)
     */
    public function processPurchase(PurchaseDTO $dto): WalletEvent
    {
        // Validate
        $dto->validate();

        // Check for duplicate purchase
        $this->validateDuplicatePurchase($dto->customer, $dto->orderId);

        $wallet = $this->getOrCreateWallet($dto->customer, $dto->storeId);

        // Validate sufficient bonus
        if ($dto->bonusToUse > $wallet->bonus) {
            throw new \Exception(
                "Insufficient bonus balance. Available: {$wallet->bonus}, Required: {$dto->bonusToUse}"
            );
        }

        // Validate sufficient cash
        if ($dto->cashToUse > $wallet->total_cash) {
            throw new \Exception(
                "Insufficient cash balance. Available: {$wallet->total_cash}, Required: {$dto->cashToUse}"
            );
        }

        // Determine cash allocation (topup first, then rib)
        $topupToUse = min($dto->cashToUse, $wallet->topup_cash);
        $ribToUse = $dto->cashToUse - $topupToUse;

        // Create transaction DTO for deduction
        $transactionDto = new WalletTransactionDTO(
            customer: $dto->customer,
            type: WalletEventType::PURCHASE,
            ribCash: $ribToUse,
            topupCash: $topupToUse,
            bonus: $dto->bonusToUse,
            referenceType: WalletReferenceType::SHOPIFY->value,
            referenceId: $dto->orderId,
            metadata: array_merge($dto->metadata, [
                'allocation' => [
                    'bonus_used' => $dto->bonusToUse,
                    'topup_cash_used' => $topupToUse,
                    'rib_cash_used' => $ribToUse,
                    'total_amount' => $dto->getTotalAmount(),
                ],
                'order_id' => $dto->orderId,
            ]),
            description: "Purchase for order {$dto->orderId}",
            storeId: $dto->storeId,
            createdBy: $dto->createdBy,
            ipAddress: $dto->ipAddress
        );

        return $this->deductBalance($transactionDto);
    }

    /**
     * Process withdrawal (RIB cash only)
     */
    public function processWithdrawal(WithdrawalDTO $dto): WalletEvent
    {
        // Validate
        $dto->validate();

        $wallet = $this->getOrCreateWallet($dto->customer, $dto->storeId);

        // Validate sufficient RIB cash
        if (!$wallet->canWithdraw($dto->amount)) {
            throw new \Exception(
                "Insufficient RIB cash for withdrawal. Available: {$wallet->rib_cash}, Requested: {$dto->amount}"
            );
        }

        // Create transaction DTO for deduction
        $transactionDto = new WalletTransactionDTO(
            customer: $dto->customer,
            type: WalletEventType::WITHDRAWAL,
            ribCash: $dto->amount,
            topupCash: 0,
            bonus: 0,
            referenceType: WalletReferenceType::SYSTEM->value,
            referenceId: $dto->referenceId,
            metadata: array_merge($dto->metadata, [
                'withdrawal_amount' => $dto->amount,
                'withdrawal_id' => $dto->referenceId,
            ]),
            description: "Withdrawal of {$dto->amount}",
            storeId: $dto->storeId,
            createdBy: $dto->createdBy,
            ipAddress: $dto->ipAddress
        );

        return $this->deductBalance($transactionDto);
    }

    /**
     * Process refund (reverse purchase)
     */
    public function processRefund(RefundDTO $dto): WalletEvent
    {
        // Create transaction DTO for addition
        $transactionDto = new WalletTransactionDTO(
            customer: $dto->customer,
            type: WalletEventType::REFUND,
            ribCash: $dto->ribCash,
            topupCash: $dto->topupCash,
            bonus: $dto->bonus,
            referenceType: WalletReferenceType::SHOPIFY->value,
            referenceId: $dto->orderId,
            metadata: array_merge($dto->metadata, [
                'refund_for_order' => $dto->orderId,
                'refund_breakdown' => [
                    'rib_cash_refunded' => $dto->ribCash,
                    'topup_cash_refunded' => $dto->topupCash,
                    'bonus_refunded' => $dto->bonus,
                ],
            ]),
            description: "Refund for order {$dto->orderId}",
            storeId: $dto->storeId,
            createdBy: $dto->createdBy,
            ipAddress: $dto->ipAddress
        );

        return $this->addBalance($transactionDto);
    }



    /**
     * Get transaction history
     */
    public function getTransactionHistory(
        Customer $customer,
        int $limit = 50,
        string $storeId = '2game_br',
        ?WalletEventType $type = null,
        ?string $referenceId = null
    ): array {
        $wallet = $this->getOrCreateWallet($customer, $storeId);

        $query = $wallet->events();

        if ($type) {
            $query->where('type', $type);
        }

        if ($referenceId) {
            $query->where('reference_id', $referenceId);
        }

        $events = $query->limit($limit)->get();

        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'type' => $event->type->value,
                'type_label' => $event->type->label(),
                'is_credit' => $event->is_credit,
                'is_debit' => $event->is_debit,
                'delta' => [
                    'rib_cash' => (float) $event->rib_cash_delta,
                    'topup_cash' => (float) $event->topup_cash_delta,
                    'bonus' => (float) $event->bonus_delta,
                    'total' => (float) $event->total_delta,
                ],
                'balance_after' => [
                    'rib_cash' => (float) $event->rib_cash_balance,
                    'topup_cash' => (float) $event->topup_cash_balance,
                    'bonus' => (float) $event->bonus_balance,
                    'total' => (float) $event->total_balance,
                ],
                'reference' => [
                    'type' => $event->reference_type?->value,
                    'id' => $event->reference_id,
                ],
                'description' => $event->description,
                'metadata' => $event->metadata,
                'created_by' => $event->created_by,
                'ip_address' => $event->ip_address,
                'created_at' => $event->created_at->toIso8601String(),
            ];
        })->toArray();
    }






    /**
     * Validate sufficient balance
     */
    private function validateSufficientBalance(
        Wallet $wallet,
        float $ribCash,
        float $topupCash,
        float $bonus
    ): void {
        if ($wallet->rib_cash < $ribCash) {
            throw new Exception(
                "Insufficient RIB cash balance. Available: {$wallet->rib_cash}, Required: {$ribCash}"
            );
        }

        if ($wallet->topup_cash < $topupCash) {
            throw new Exception(
                "Insufficient top-up cash balance. Available: {$wallet->topup_cash}, Required: {$topupCash}"
            );
        }

        if ($wallet->bonus < $bonus) {
            throw new Exception(
                "Insufficient bonus balance. Available: {$wallet->bonus}, Required: {$bonus}"
            );
        }
    }


    private function createEvent(WalletEventDTO $dto): WalletEvent
    {
        return WalletEvent::create([
            'wallet_id' => $dto->wallet->id,
            'customer_id' => $dto->wallet->customer_id,
            'type' => $dto->type,

            'rib_cash_delta' => $dto->ribCashDelta,
            'topup_cash_delta' => $dto->topupCashDelta,
            'bonus_delta' => $dto->bonusDelta,

            'rib_cash_balance' => $dto->ribCashBalance,
            'topup_cash_balance' => $dto->topupCashBalance,
            'bonus_balance' => $dto->bonusBalance,

            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'metadata' => $dto->metadata,
            'description' => $dto->description,

            'created_by' => $dto->createdBy ?? Auth::id() ?? 'system',
            'ip_address' => $dto->ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Validate duplicate purchase
     */
    private function validateDuplicatePurchase(Customer $customer, string $orderId): void
    {
        $exists = WalletEvent::where('customer_id', $customer->id)
            ->where('reference_id', $orderId)
            ->where('type', WalletEventType::PURCHASE)
            ->exists();

        if ($exists) {
            throw new Exception("Purchase for order {$orderId} has already been processed");
        }
    }
}
