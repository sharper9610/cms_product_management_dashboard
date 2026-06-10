<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AddBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    WalletEventType::TOPUP->value,
                    WalletEventType::BONUS->value,
                    WalletEventType::TOURNAMENT_WIN->value,
                    WalletEventType::REFUND->value,
                ]),
            ],
            'rib_cash' => 'nullable|numeric|min:0|max:999999.99',
            'topup_cash' => 'nullable|numeric|min:0|max:999999.99',
            'bonus' => 'nullable|numeric|min:0|max:999999.99',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'description' => 'nullable|string|max:500',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Invalid transaction type for adding balance.',
            'rib_cash.min' => 'RIB cash amount cannot be negative.',
            'topup_cash.min' => 'Top-up cash amount cannot be negative.',
            'bonus.min' => 'Bonus amount cannot be negative.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Add balance validation failed',
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

            // At least one amount must be greater than zero
            if ($ribCash <= 0 && $topupCash <= 0 && $bonus <= 0) {
                $validator->errors()->add(
                    'amount',
                    'At least one balance amount must be greater than zero.'
                );
            }
        });
    }
}