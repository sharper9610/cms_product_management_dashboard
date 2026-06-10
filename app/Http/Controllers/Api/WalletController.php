<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Wallet\PurchaseDTO;
use App\DTOs\Wallet\RefundDTO;
use App\DTOs\Wallet\WalletTransactionDTO;
use App\DTOs\Wallet\WithdrawalDTO;
use App\Enums\WalletEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\AddBalanceRequest;
use App\Http\Requests\Wallet\DeductBalanceRequest;
use App\Http\Requests\Wallet\ProcessPurchaseRequest;
use App\Http\Requests\Wallet\ProcessRefundRequest;
use App\Http\Requests\Wallet\ProcessWithdrawalRequest;
use App\Models\Customer;
use App\Services\Customer\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * GET /api/wallets/{customer_id}/balance
     * 
     * Get wallet balances for a customer
     */
    public function getBalance(string $customerId, Request $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $storeId = $request->input('store_id', '2game_br');

            $balances = $this->walletService->getBalances($customer, $storeId);

            return $this->successResponse(
                $balances,
                'Wallet balances retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve wallet balance', $e->getMessage());
        }
    }

    /**
     * GET /api/wallets/by-shopify/{shopify_customer_id}/balance
     * 
     * Get wallet balances by Shopify customer ID
     */
    public function getBalanceByShopifyId(string $shopifyCustomerId, Request $request): JsonResponse
    {
        try {
            $customer = Customer::byShopifyId($shopifyCustomerId)->firstOrFail();
            $storeId = $request->input('store_id', '2game_br');

            $balances = $this->walletService->getBalances($customer, $storeId);

            return $this->successResponse(
                array_merge($balances, [
                    'customer_id' => $customer->id,
                    'shopify_customer_id' => $customer->shopify_customer_id,
                ]),
                'Wallet balances retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse('Customer not found');
        }
    }


    /**
     * POST /api/wallets/{customer_id}/add-balance
     */
    public function addBalance(string $customerId, AddBalanceRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dto = new WalletTransactionDTO(
                customer: $customer,
                type: WalletEventType::from($request->input('type')),
                ribCash: $request->input('rib_cash', 0),
                topupCash: $request->input('topup_cash', 0),
                bonus: $request->input('bonus', 0),
                referenceType: $request->input('reference_type'),
                referenceId: $request->input('reference_id'),
                metadata: $request->input('metadata'),
                description: $request->input('description'),
                storeId: $request->input('store_id', '2game_br'),
                createdBy: Auth::id() ?? 'api',
                ipAddress: $request->ip()
            );

            $event = $this->walletService->addBalance($dto);
            $balances = $this->walletService->getBalances($customer, $dto->storeId);

            return $this->successResponse([
                'event_id' => $event->id,
                'type' => $event->type->value,
                'delta' => [
                    'rib_cash' => (float) $event->rib_cash_delta,
                    'topup_cash' => (float) $event->topup_cash_delta,
                    'bonus' => (float) $event->bonus_delta,
                ],
                'balances' => $balances,
                'created_at' => $event->created_at->toIso8601String(),
            ], 'Balance added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add balance', $e->getMessage());
        }
    }


    /**
     * POST /api/wallets/{customer_id}/deduct-balance
     */
    public function deductBalance(string $customerId, DeductBalanceRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dto = new WalletTransactionDTO(
                customer: $customer,
                type: WalletEventType::from($request->input('type')),
                ribCash: $request->input('rib_cash', 0),
                topupCash: $request->input('topup_cash', 0),
                bonus: $request->input('bonus', 0),
                referenceType: $request->input('reference_type'),
                referenceId: $request->input('reference_id'),
                metadata: $request->input('metadata'),
                description: $request->input('description'),
                storeId: $request->input('store_id', '2game_br'),
                createdBy: Auth::id() ?? 'api',
                ipAddress: $request->ip()
            );

            $event = $this->walletService->deductBalance($dto);
            $balances = $this->walletService->getBalances($customer, $dto->storeId);

            return $this->successResponse([
                'event_id' => $event->id,
                'type' => $event->type->value,
                'delta' => [
                    'rib_cash' => (float) $event->rib_cash_delta,
                    'topup_cash' => (float) $event->topup_cash_delta,
                    'bonus' => (float) $event->bonus_delta,
                ],
                'balances' => $balances,
                'created_at' => $event->created_at->toIso8601String(),
            ], 'Balance deducted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to deduct balance', $e->getMessage());
        }
    }


    /**
     * POST /api/wallets/{customer_id}/check-sufficient-balance
     * 
     * Check if customer has sufficient balance for a purchase
     */
    public function checkSufficientBalance(string $customerId, Request $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $storeId = $request->input('store_id', '2game_br');

            $wallet = $this->walletService->getOrCreateWallet($customer, $storeId);

            $requiredAmount = $request->input('required_amount', 0);
            $includingBonus = $request->input('including_bonus', false);

            $hasSufficientBalance = $wallet->hasBalanceFor($requiredAmount, $includingBonus);

            $balances = $this->walletService->getBalances($customer, $storeId);

            return $this->successResponse([
                'has_sufficient_balance' => $hasSufficientBalance,
                'required_amount' => (float) $requiredAmount,
                'available_cash' => (float) $wallet->total_cash,
                'available_bonus' => (float) $wallet->bonus,
                'total_available' => (float) ($wallet->total_cash + ($includingBonus ? $wallet->bonus : 0)),
                'shortfall' => $hasSufficientBalance ? 0 : ($requiredAmount - $wallet->total_cash),
                'balances' => $balances,
            ], 'Balance check completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check balance', $e->getMessage());
        }
    }


    /**
     * POST /api/wallets/{customer_id}/purchase
     */
    public function processPurchase(string $customerId, ProcessPurchaseRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dto = new PurchaseDTO(
                customer: $customer,
                bonusToUse: $request->input('bonus_to_use'),
                cashToUse: $request->input('cash_to_use'),
                orderId: $request->input('order_id'),
                metadata: $request->input('metadata', []),
                storeId: $request->input('store_id', '2game_br'),
                createdBy: Auth::id() ?? 'api',
                ipAddress: $request->ip()
            );

            $event = $this->walletService->processPurchase($dto);
            $balances = $this->walletService->getBalances($customer, $dto->storeId);

            return $this->successResponse([
                'event_id' => $event->id,
                'order_id' => $dto->orderId,
                'allocation' => $event->metadata['allocation'] ?? [],
                'balances' => $balances,
                'created_at' => $event->created_at->toIso8601String(),
            ], 'Purchase processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process purchase', $e->getMessage());
        }
    }

    /**
     * POST /api/wallets/{customer_id}/withdrawal
     */
    public function processWithdrawal(string $customerId, ProcessWithdrawalRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dto = new WithdrawalDTO(
                customer: $customer,
                amount: $request->input('amount'),
                referenceId: $request->input('reference_id'),
                metadata: $request->input('metadata', []),
                storeId: $request->input('store_id', '2game_br'),
                createdBy: Auth::id() ?? 'api',
                ipAddress: $request->ip()
            );

            $event = $this->walletService->processWithdrawal($dto);
            $balances = $this->walletService->getBalances($customer, $dto->storeId);

            return $this->successResponse([
                'event_id' => $event->id,
                'amount' => $dto->amount,
                'reference_id' => $dto->referenceId,
                'balances' => $balances,
                'created_at' => $event->created_at->toIso8601String(),
            ], 'Withdrawal processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process withdrawal', $e->getMessage());
        }
    }

    /**
     * POST /api/wallets/{customer_id}/refund
     * 
     * Process refund for an order
     */
    public function processRefund(string $customerId, ProcessRefundRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dto = new RefundDTO(
                customer: $customer,
                orderId: $request->input('order_id'),
                ribCash: $request->input('rib_cash', 0),
                topupCash: $request->input('topup_cash', 0),
                bonus: $request->input('bonus', 0),
                metadata: array_merge(
                    $request->input('metadata', []),
                    [
                        'refund_breakdown' => $request->getRefundBreakdown(),
                        'is_full_refund' => $request->isFullRefund(),
                    ]
                ),
                storeId: $request->input('store_id', '2game_br'),
                createdBy: Auth::id() ?? 'api',
                ipAddress: $request->ip()
            );

            $event = $this->walletService->processRefund($dto);
            $balances = $this->walletService->getBalances($customer, $dto->storeId);

            return $this->successResponse([
                'event_id' => $event->id,
                'order_id' => $dto->orderId,
                'refunded' => [
                    'rib_cash' => (float) $event->rib_cash_delta,
                    'topup_cash' => (float) $event->topup_cash_delta,
                    'bonus' => (float) $event->bonus_delta,
                    'total' => (float) $event->total_delta,
                ],
                'is_full_refund' => $request->isFullRefund(),
                'balances' => $balances,
                'created_at' => $event->created_at->toIso8601String(),
            ], 'Refund processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process refund', $e->getMessage());
        }
    }


    /**
     * GET /api/wallets/{customer_id}/transactions
     * 
     * Get transaction history
     */
    public function getTransactions(string $customerId, Request $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $storeId = $request->input('store_id', '2game_br');
            $limit = $request->input('limit', 50);

            $transactions = $this->walletService->getTransactionHistory(
                $customer,
                $limit,
                $storeId
            );

            return $this->successResponse(
                ['transactions' => $transactions],
                'Transaction history retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve transactions', $e->getMessage());
        }
    }
}
