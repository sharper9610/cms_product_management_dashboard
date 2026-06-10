<?php

namespace App\Services\Customer;

use App\Models\Product;
use Illuminate\Support\Collection;

class BonusCalculationService
{
    /**
     * Bonus cap percentages
     */
    private const DEFAULT_BONUS_CAP_PERCENT = 100;
    private const GIFT_CARD_BONUS_CAP_PERCENT = 5;
    private const RESTRICTED_BONUS_CAP_PERCENT = 0;

    /**
     * Product type identifiers
     */
    private const GIFT_CARD_TYPES = ['gift_card', 'giftcard'];
    private const RESTRICTED_TYPES = ['restricted', 'no_bonus'];

    /**
     * Currency settings
     */
    // private const DEFAULT_CURRENCY = 'BRL';
    private const CURRENCY_SYMBOL = 'R$';
    private const DECIMAL_PRECISION = 2;

    /**
     * Messages
     */
    private const MSG_APPROVED = 'Payment approved. Total: %s %s';
    private const MSG_INSUFFICIENT = 'Some items limit how much Bonus can be used. Your cart requires %s %s in cash balance. You currently have %s %s. Add %s %s to continue.';

    /**
     * Default product title for missing products
     */
    private const PRODUCT_NOT_FOUND_TITLE = 'Product #%d';

    /**
     * Calculate bonus allocation for a single cart item
     *
     * @param int $sku
     * @param float $price
     * @param int $quantity
     * @return array
     */
    public function calculateItemBonus(int $sku, float $price, int $quantity = 1): array
    {
        $product = Product::where('sku', $sku)->first();

        if (!$product) {
            return $this->calculateFallbackBonus($sku, $price, $quantity);
        }

        return $product->calculateBonusAllocation($quantity, $price);
    }

    /**
     * Calculate bonus allocation for entire cart
     *
     * @param array $cartItems
     * @return array
     */
    public function calculateCartBonus(array $cartItems): array
    {
        $totalPrice = 0;
        $totalMaxBonus = 0;
        $itemBreakdown = [];

        foreach ($cartItems as $item) {
            $sku = $item['sku'];
            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'];

            $itemAllocation = $this->calculateItemBonus($sku, $price, $quantity);

            $totalPrice += $itemAllocation['item_total'];
            $totalMaxBonus += $itemAllocation['max_bonus_allowed'];

            $itemBreakdown[] = $itemAllocation;
        }

        return [
            'total_price' => $this->round($totalPrice),
            'total_max_bonus' => $this->round($totalMaxBonus),
            'total_required_cash' => $this->round($totalPrice - $totalMaxBonus),
            'items' => $itemBreakdown,
        ];
    }

    /**
     * Validate cart against wallet balance
     *
     * @param array $cartItems
     * @param float $availableCash
     * @param float $availableBonus
     * @return array
     */
    public function validateCartAgainstWallet(
        array $cartItems,
        float $availableCash,
        float $availableBonus
    ): array {
        $allocation = $this->calculateCartBonus($cartItems);

        // Calculate actual bonus to use (min of available and allowed)
        $bonusToUse = min($availableBonus, $allocation['total_max_bonus']);

        // Calculate required cash
        $requiredCash = $allocation['total_price'] - $bonusToUse;

        // Check if customer has sufficient balance
        $hasSufficientBalance = $availableCash >= $requiredCash;
        $cashShortfall = $hasSufficientBalance ? 0 : $this->round($requiredCash - $availableCash);

        return [
            'approved' => $hasSufficientBalance,
            'total_price' => $allocation['total_price'],
            'bonus_to_use' => $this->round($bonusToUse),
            'cash_to_use' => $this->round($requiredCash),
            'available_cash' => $availableCash,
            'available_bonus' => $availableBonus,
            'shortfall' => $cashShortfall,
            'allocation_plan' => [
                'total_max_bonus_allowed' => $allocation['total_max_bonus'],
                'bonus_to_use' => $this->round($bonusToUse),
                'cash_to_use' => $this->round($requiredCash),
            ],
            'items' => $allocation['items'],
            'message' => $this->getValidationMessage(
                $hasSufficientBalance,
                $allocation['total_price'],
                $requiredCash,
                $availableCash,
                $cashShortfall
            ),
        ];
    }

    /**
     * Get validation message for UX
     *
     * @param bool $approved
     * @param float $totalPrice
     * @param float $requiredCash
     * @param float $availableCash
     * @param float $shortfall
     * @return string
     */
    private function getValidationMessage(
        bool $approved,
        float $totalPrice,
        float $requiredCash,
        float $availableCash,
        float $shortfall
    ): string {
        if ($approved) {
            return sprintf(
                self::MSG_APPROVED,
                self::CURRENCY_SYMBOL,
                number_format($totalPrice, self::DECIMAL_PRECISION, ',', '.')
            );
        }

        return sprintf(
            self::MSG_INSUFFICIENT,
            self::CURRENCY_SYMBOL,
            number_format($requiredCash, self::DECIMAL_PRECISION, ',', '.'),
            self::CURRENCY_SYMBOL,
            number_format($availableCash, self::DECIMAL_PRECISION, ',', '.'),
            self::CURRENCY_SYMBOL,
            number_format($shortfall, self::DECIMAL_PRECISION, ',', '.')
        );
    }

    /**
     * Fallback calculation for products not in database
     *
     * @param int $sku
     * @param float $price
     * @param int $quantity
     * @return array
     */
    private function calculateFallbackBonus(int $sku, float $price, int $quantity): array
    {
        $itemTotal = $this->round($price * $quantity);

        return [
            'sku' => $sku,
            'title' => sprintf(self::PRODUCT_NOT_FOUND_TITLE, $sku),
            'quantity' => $quantity,
            'price' => $this->round($price),
            'item_total' => $itemTotal,
            'bonus_cap_percent' => self::DEFAULT_BONUS_CAP_PERCENT,
            'max_bonus_allowed' => $itemTotal,
            'required_cash' => 0,
            'can_use_full_bonus' => true,
        ];
    }

    /**
     * Get products with bonus restrictions
     *
     * @return Collection
     */
    public function getProductsWithRestrictions(): Collection
    {
        return Product::withBonusRestriction()
            ->where('status', Product::STATUS_ACTIVE)
            ->get(['sku', 'name', 'product_type', 'bonus_cap_percent'])
            ->map(function ($product) {
                return [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'product_type' => $product->product_type,
                    'bonus_cap_percent' => $product->getBonusCapPercent(),
                ];
            });
    }

    /**
     * Round monetary value
     *
     * @param float $amount
     * @return float
     */
    private function round(float $amount): float
    {
        return round($amount, self::DECIMAL_PRECISION);
    }

    /**
     * Get default bonus cap percent
     *
     * @return int
     */
    public static function getDefaultBonusCapPercent(): int
    {
        return self::DEFAULT_BONUS_CAP_PERCENT;
    }

    /**
     * Get gift card bonus cap percent
     *
     * @return int
     */
    public static function getGiftCardBonusCapPercent(): int
    {
        return self::GIFT_CARD_BONUS_CAP_PERCENT;
    }

    /**
     * Get restricted bonus cap percent
     *
     * @return int
     */
    public static function getRestrictedBonusCapPercent(): int
    {
        return self::RESTRICTED_BONUS_CAP_PERCENT;
    }

    /**
     * Check if product type is gift card
     *
     * @param string|null $productType
     * @return bool
     */
    public static function isGiftCardType(?string $productType): bool
    {
        if (empty($productType)) {
            return false;
        }

        return in_array(strtolower($productType), self::GIFT_CARD_TYPES, true);
    }

    /**
     * Check if product type is restricted
     *
     * @param string|null $productType
     * @return bool
     */
    public static function isRestrictedType(?string $productType): bool
    {
        if (empty($productType)) {
            return false;
        }

        return in_array(strtolower($productType), self::RESTRICTED_TYPES, true);
    }
}
