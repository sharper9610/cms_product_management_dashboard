<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletEventType;
use App\Models\WalletEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|string|max:255',
            'rib_cash' => 'nullable|numeric|min:0|max:999999.99',
            'topup_cash' => 'nullable|numeric|min:0|max:999999.99',
            'bonus' => 'nullable|numeric|min:0|max:999999.99',
            'metadata' => 'nullable|array',
            'metadata.refund_reason' => 'nullable|string|max:255',
            'metadata.original_order_total' => 'nullable|numeric|min:0',
            'metadata.refund_type' => 'nullable|string|in:full,partial',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required for refund.',
            'order_id.max' => 'Order ID cannot exceed 255 characters.',
            'rib_cash.numeric' => 'RIB cash refund amount must be a valid number.',
            'rib_cash.min' => 'RIB cash refund amount cannot be negative.',
            'topup_cash.numeric' => 'Top-up cash refund amount must be a valid number.',
            'topup_cash.min' => 'Top-up cash refund amount cannot be negative.',
            'bonus.numeric' => 'Bonus refund amount must be a valid number.',
            'bonus.min' => 'Bonus refund amount cannot be negative.',
            'metadata.refund_type.in' => 'Refund type must be either full or partial.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Refund validation failed',
                    'Value' => $validator->errors()->all(),
                ]
            ], 422)
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ribCash = $this->input('rib_cash', 0);
            $topupCash = $this->input('topup_cash', 0);
            $bonus = $this->input('bonus', 0);

            // At least one refund amount must be greater than zero
            if ($ribCash <= 0 && $topupCash <= 0 && $bonus <= 0) {
                $validator->errors()->add(
                    'amount',
                    'At least one refund amount (rib_cash, topup_cash, or bonus) must be greater than zero.'
                );
            }

            // Check if the order exists in wallet_events as a purchase
            $customerId = $this->route('customer_id');
            $orderId = $this->input('order_id');

            if ($customerId && $orderId) {
                $purchaseExists = WalletEvent::where('customer_id', $customerId)
                    ->where('reference_id', $orderId)
                    ->where('type', WalletEventType::PURCHASE)
                    ->exists();

                if (!$purchaseExists) {
                    $validator->errors()->add(
                        'order_id',
                        "No purchase found for order {$orderId}. Cannot process refund."
                    );
                }

                // Check if refund already exists for this order
                $refundExists = WalletEvent::where('customer_id', $customerId)
                    ->where('reference_id', $orderId)
                    ->where('type', WalletEventType::REFUND)
                    ->exists();

                if ($refundExists) {
                    $validator->errors()->add(
                        'order_id',
                        "Refund for order {$orderId} has already been processed."
                    );
                }

                // Validate refund amounts don't exceed original purchase
                if ($purchaseExists) {
                    $originalPurchase = WalletEvent::where('customer_id', $customerId)
                        ->where('reference_id', $orderId)
                        ->where('type', WalletEventType::PURCHASE)
                        ->first();

                    if ($originalPurchase) {
                        $originalRibCash = abs($originalPurchase->rib_cash_delta);
                        $originalTopupCash = abs($originalPurchase->topup_cash_delta);
                        $originalBonus = abs($originalPurchase->bonus_delta);

                        if ($ribCash > $originalRibCash) {
                            $validator->errors()->add(
                                'rib_cash',
                                "RIB cash refund amount ({$ribCash}) cannot exceed original purchase amount ({$originalRibCash})."
                            );
                        }

                        if ($topupCash > $originalTopupCash) {
                            $validator->errors()->add(
                                'topup_cash',
                                "Top-up cash refund amount ({$topupCash}) cannot exceed original purchase amount ({$originalTopupCash})."
                            );
                        }

                        if ($bonus > $originalBonus) {
                            $validator->errors()->add(
                                'bonus',
                                "Bonus refund amount ({$bonus}) cannot exceed original purchase amount ({$originalBonus})."
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * Get the refund amount breakdown
     */
    public function getRefundBreakdown(): array
    {
        return [
            'rib_cash' => (float) $this->input('rib_cash', 0),
            'topup_cash' => (float) $this->input('topup_cash', 0),
            'bonus' => (float) $this->input('bonus', 0),
            'total' => (float) (
                $this->input('rib_cash', 0) +
                $this->input('topup_cash', 0) +
                $this->input('bonus', 0)
            ),
        ];
    }

    /**
     * Check if this is a full refund
     */
    public function isFullRefund(): bool
    {
        $metadata = $this->input('metadata', []);
        return ($metadata['refund_type'] ?? null) === 'full';
    }
}