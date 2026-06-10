<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\ValidateCartRequest;
use App\Http\Requests\Wallet\ValidateCartWithBalanceRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Customer\BonusCalculationService;
use App\Services\Customer\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BonusController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BonusCalculationService $bonusService,
        private WalletService $walletService
    ) {}

    /**
     * GET /api/bonus/product/{sku}
     * 
     * Get bonus cap for specific product
     */
    public function getProductBonusCap(int $sku): JsonResponse
    {
        try {
            $product = Product::where('sku', $sku)->first();

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse([
                'sku' => $product->sku,
                'name' => $product->name,
                'product_type' => $product->product_type,
                'bonus_cap_percent' => $product->getBonusCapPercent(),
                'can_use_bonus' => $product->canUseBonus(),
                'is_full_bonus_allowed' => $product->isFullBonusAllowed(),
            ], 'Bonus cap retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve bonus cap', $e->getMessage());
        }
    }

    /**
     * POST /api/bonus/calculate-cart
     * 
     * Calculate bonus allocation for cart
     */
    public function calculateCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|integer',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.title' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $allocation = $this->bonusService->calculateCartBonus(
                $request->input('items')
            );

            return $this->successResponse(
                $allocation,
                'Cart bonus allocation calculated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to calculate cart bonus', $e->getMessage());
        }
    }

    /**
     * POST /api/bonus/validate-cart
     * 
     * Validate cart against customer's wallet balance (dynamic lookup)
     */
    public function validateCart(ValidateCartRequest $request): JsonResponse
    {
        try {
            $shopifyCustomerId = $request->input('shopify_customer_id');
            $storeId = $request->input('store_id', '2game_br');

            // Find customer by Shopify ID
            $customer = Customer::where('shopify_customer_id', $shopifyCustomerId)->first();

            if (!$customer) {
                return $this->notFoundResponse('Customer not found');
            }

            // Get customer's wallet balance dynamically
            $balances = $this->walletService->getBalances($customer, $storeId);

            $availableCash = $balances['total_cash'];
            $availableBonus = $balances['bonus'];

            // Validate cart against wallet
            $validation = $this->bonusService->validateCartAgainstWallet(
                $request->input('items'),
                $availableCash,
                $availableBonus
            );

            // Add customer info to response
            // $validation['customer'] = [
            //     'id' => $customer->id,
            //     'shopify_customer_id' => $customer->shopify_customer_id,
            //     'email' => $customer->email,
            //     'full_name' => $customer->full_name,
            // ];

            $validation['wallet_balances'] = [
                'rib_cash' => $balances['rib_cash'],
                'topup_cash' => $balances['topup_cash'],
                'bonus' => $balances['bonus'],
                'total_cash' => $balances['total_cash'],
                'total_balance' => $balances['total_balance'],
                'currency' => $balances['currency'],
            ];

            return $this->successResponse(
                $validation,
                $validation['approved'] ? 'Cart validation passed' : 'Insufficient balance'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to validate cart', $e->getMessage());
        }
    }

    /**
     * POST /api/bonus/validate-cart-with-balance
     * 
     * Validate cart against provided balance (no customer lookup)
     * Use case: Frontend preview, guest checkout, or pre-validation
     */
    public function validateCartWithBalance(ValidateCartWithBalanceRequest $request): JsonResponse
    {
        try {
            $availableCash = $request->input('available_cash');
            $availableBonus = $request->input('available_bonus');

            // Validate cart against provided balance
            $validation = $this->bonusService->validateCartAgainstWallet(
                $request->input('items'),
                $availableCash,
                $availableBonus
            );

            return $this->successResponse(
                $validation,
                $validation['approved'] ? 'Cart validation passed' : 'Insufficient balance'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to validate cart', $e->getMessage());
        }
    }

    /**
     * GET /api/bonus/restricted-products
     * 
     * Get list of products with bonus restrictions
     */
    public function getRestrictedProducts(): JsonResponse
    {
        try {
            $products = $this->bonusService->getProductsWithRestrictions();

            return $this->successResponse([
                'count' => $products->count(),
                'products' => $products,
            ], 'Restricted products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve restricted products', $e->getMessage());
        }
    }
}
