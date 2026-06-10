<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'reference_id' => 'required|string|max:255',
            'metadata' => 'nullable|array',
            'metadata.bank_account' => 'nullable|string',
            'metadata.withdrawal_method' => 'nullable|string',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Withdrawal amount is required.',
            'amount.min' => 'Withdrawal amount must be at least 0.01.',
            'reference_id.required' => 'Withdrawal reference ID is required.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Withdrawal validation failed',
                    'Value' => $validator->errors()->all(),
                ]
            ], 422)
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerId = $this->route('customer_id');
            $amount = $this->input('amount');

            if ($customerId) {
                // Check if customer has sufficient RIB cash
                $customer = \App\Models\Customer::find($customerId);
                if ($customer) {
                    $wallet = $customer->wallet;
                    if ($wallet && !$wallet->canWithdraw($amount)) {
                        $validator->errors()->add(
                            'amount',
                            "Insufficient RIB cash balance. Available: {$wallet->rib_cash}, Requested: {$amount}"
                        );
                    }
                }
            }
        });
    }
}