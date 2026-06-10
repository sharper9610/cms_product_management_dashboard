<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|string|max:255',
            'bonus_to_use' => 'required|numeric|min:0|max:999999.99',
            'cash_to_use' => 'required|numeric|min:0|max:999999.99',
            'metadata' => 'nullable|array',
            'metadata.order_total' => 'nullable|numeric|min:0',
            'metadata.cart_items' => 'nullable|array',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required.',
            'bonus_to_use.required' => 'Bonus amount to use is required.',
            'cash_to_use.required' => 'Cash amount to use is required.',
            'bonus_to_use.min' => 'Bonus amount cannot be negative.',
            'cash_to_use.min' => 'Cash amount cannot be negative.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Purchase validation failed',
                    'Value' => $validator->errors()->all(),
                ]
            ], 422)
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bonusToUse = $this->input('bonus_to_use', 0);
            $cashToUse = $this->input('cash_to_use', 0);

            // At least one amount must be greater than zero
            if ($bonusToUse <= 0 && $cashToUse <= 0) {
                $validator->errors()->add(
                    'amount',
                    'At least one of bonus_to_use or cash_to_use must be greater than zero.'
                );
            }

            // Check if order_id already exists in wallet_events
            $customerId = $this->route('customer_id');
            if ($customerId) {
                $exists = \App\Models\WalletEvent::where('reference_id', $this->input('order_id'))
                    ->where('customer_id', $customerId)
                    ->where('type', \App\Enums\WalletEventType::PURCHASE)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'order_id',
                        "Purchase for order {$this->input('order_id')} has already been processed."
                    );
                }
            }
        });
    }
}