<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateCartWithBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|integer',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.title' => 'nullable|string',
            'available_cash' => 'required|numeric|min:0',
            'available_bonus' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Cart items are required.',
            'items.min' => 'Cart must contain at least one item.',
            'items.*.sku.required' => 'Product SKU is required.',
            'items.*.sku.integer' => 'Product SKU must be an integer.',
            'items.*.price.required' => 'Product price is required.',
            'items.*.price.numeric' => 'Product price must be a number.',
            'items.*.price.min' => 'Product price cannot be negative.',
            'items.*.quantity.required' => 'Product quantity is required.',
            'items.*.quantity.integer' => 'Product quantity must be an integer.',
            'items.*.quantity.min' => 'Product quantity must be at least 1.',
            'available_cash.required' => 'Available cash balance is required.',
            'available_cash.numeric' => 'Available cash must be a number.',
            'available_cash.min' => 'Available cash cannot be negative.',
            'available_bonus.required' => 'Available bonus balance is required.',
            'available_bonus.numeric' => 'Available bonus must be a number.',
            'available_bonus.min' => 'Available bonus cannot be negative.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version' => '1.0',
                    'ErrorCode' => '1',
                    'ErrorMsg' => 'Cart validation failed',
                    'Value' => $validator->errors()->all(),
                ]
            ], 422)
        );
    }
}