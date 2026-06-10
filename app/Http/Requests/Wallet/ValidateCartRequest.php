<?php

namespace App\Http\Requests\Wallet;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shopify_customer_id' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|integer',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.title' => 'nullable|string',
            'store_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'shopify_customer_id.required' => 'Shopify customer ID is required.',
            'items.required' => 'Cart items are required.',
            'items.*.sku.required' => 'Product SKU is required.',
            'items.*.price.required' => 'Product price is required.',
            'items.*.quantity.required' => 'Product quantity is required.',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shopifyCustomerId = $this->input('shopify_customer_id');

            // Check if customer exists
            $customer = Customer::where('shopify_customer_id', $shopifyCustomerId)->first();

            if (!$customer) {
                $validator->errors()->add(
                    'shopify_customer_id',
                    "Customer with Shopify ID {$shopifyCustomerId} not found."
                );
            }
        });
    }
}