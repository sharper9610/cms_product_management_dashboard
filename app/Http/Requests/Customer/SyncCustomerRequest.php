<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SyncCustomerRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => 'required|array',
            'customer.id' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.firstName' => 'nullable|string|max:255',
            'customer.lastName' => 'nullable|string|max:255',
            'customer.displayName' => 'nullable|string|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'customer.legacyResourceId' => 'nullable|string|max:255',
            'customer.state' => 'nullable|in:ENABLED,DISABLED,INVITED,DECLINED',
            'customer.locale' => 'nullable|string|max:10',
            'customer.taxExempt' => 'nullable|boolean',
            'customer.verifiedEmail' => 'nullable|boolean',
            'customer.validEmailAddress' => 'nullable|boolean',
            'customer.note' => 'nullable|string',
            'customer.tags' => 'nullable|array',

            // Shopify sends amount as string
            'customer.amountSpent' => 'nullable|array',
            'customer.amountSpent.amount' => 'nullable|numeric|min:0',

            // Shopify sends number as string
            'customer.numberOfOrders' => 'nullable|numeric|min:0',

            'customer.createdAt' => 'nullable|date',
            'customer.updatedAt' => 'nullable|date',

            // Addresses
            'customer.addresses' => 'nullable|array',
            'customer.addresses.*.id' => 'nullable|string',
            'customer.addresses.*.address1' => 'nullable|string|max:255',
            'customer.addresses.*.address2' => 'nullable|string|max:255',
            'customer.addresses.*.city' => 'nullable|string|max:100',
            'customer.addresses.*.province' => 'nullable|string|max:100',
            'customer.addresses.*.provinceCode' => 'nullable|string|max:10',
            'customer.addresses.*.country' => 'nullable|string|max:100',
            'customer.addresses.*.countryCode' => 'nullable|string|max:10',
            'customer.addresses.*.zip' => 'nullable|string|max:20',
            'customer.addresses.*.phone' => 'nullable|string|max:50',
            'customer.addresses.*.company' => 'nullable|string|max:255',
            'customer.addresses.*.firstName' => 'nullable|string|max:255',
            'customer.addresses.*.lastName' => 'nullable|string|max:255',

            // Default Address
            'customer.defaultAddress' => 'nullable|array',
            'customer.defaultAddress.id' => 'nullable|string',
            'customer.defaultAddress.address1' => 'nullable|string|max:255',
            'customer.defaultAddress.address2' => 'nullable|string|max:255',
            'customer.defaultAddress.city' => 'nullable|string|max:100',
            'customer.defaultAddress.province' => 'nullable|string|max:100',
            'customer.defaultAddress.country' => 'nullable|string|max:100',
            'customer.defaultAddress.zip' => 'nullable|string|max:20',

            // Metafields
            'customer.metafields' => 'nullable|array',
            'customer.metafields.edges' => 'nullable|array',
            'customer.metafields.edges.*.node' => 'nullable|array',
            'customer.metafields.edges.*.node.id' => 'nullable|string',
            'customer.metafields.edges.*.node.namespace' => 'nullable|string|max:100',
            'customer.metafields.edges.*.node.key' => 'nullable|string|max:100',
            'customer.metafields.edges.*.node.value' => 'nullable|string',
            'customer.metafields.edges.*.node.type' => 'nullable|string|max:50',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->errorResponse(
                'Customer sync validation failed',
                ['errors' => $validator->errors()->toArray()],
                '1',
                422
            )
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerData = $this->input('customer', []);

            $this->validateShopifyId($customerData, $validator);
            $this->validateEmailUniqueness($customerData, $validator);
            $this->validateAddresses($customerData, $validator);
        });
    }

    private function validateShopifyId(array $customerData, Validator $validator): void
    {
        if (!isset($customerData['id'])) {
            return;
        }

        if (!preg_match('/^gid:\/\/shopify\/Customer\/\d+$/', $customerData['id'])) {
            $validator->errors()->add(
                'customer.id',
                'Shopify customer ID must be in valid GID format.'
            );
        }
    }

    private function validateEmailUniqueness(array $customerData, Validator $validator): void
    {
        if (!isset($customerData['email'])) {
            return;
        }

        $email = $customerData['email'];
        $shopifyId = $customerData['id'] ?? null;

        $existingCustomer = Customer::where('email', $email)
            ->when($shopifyId, fn ($q) => $q->where('shopify_customer_id', '!=', $shopifyId))
            ->first();

        if ($existingCustomer) {
            $validator->errors()->add(
                'customer.email',
                "Email {$email} is already associated with another customer."
            );
        }
    }

    /**
     * ✅ Shopify-safe address validation
     */
    private function validateAddresses(array $customerData, Validator $validator): void
    {
        if (empty($customerData['addresses']) || !is_array($customerData['addresses'])) {
            return;
        }

        foreach ($customerData['addresses'] as $index => $address) {

            // Skip completely empty address objects
            $hasAnyValue = collect($address)->filter(fn ($v) => !is_null($v))->isNotEmpty();
            if (!$hasAnyValue) {
                continue;
            }

            // Require at least ONE meaningful field
            if (
                empty($address['address1']) &&
                empty($address['city']) &&
                empty($address['country'])
            ) {
                $validator->errors()->add(
                    "customer.addresses.{$index}",
                    "Address must contain at least address1, city, or country."
                );
            }
        }
    }
}
