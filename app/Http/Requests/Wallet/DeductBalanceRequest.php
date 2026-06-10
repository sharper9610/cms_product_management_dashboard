<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DeductBalanceRequest extends FormRequest
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
                    WalletEventType::PURCHASE->value,
                    WalletEventType::ADJUSTMENT->value,
                ]),
            ],
            'rib_cash' => 'nullable|numeric|min:0|max:999999.99',
            'topup_cash' => 'nullable|numeric|min:0|max:999999.99',
            'bonus' => 'nullable|numeric|min:0|max:999999.99',
            'reference_type' => 'required|string|max:50',
            'reference_id' => 'required|string|max:255',
            'metadata' => 'nullable|array',
            'description' => 'nullable|string|max:500',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Invalid transaction type for deducting balance.',
            'reference_type.required' => 'Reference type is required.',
            'reference_id.required' => 'Reference ID is required.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Deduct balance validation failed',
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