<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    /**
     * Find or create customer from Shopify data
     */
    public function syncFromShopify(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $shopifyId = $data['id'];

            $customer = Customer::byShopifyId($shopifyId)->first();

            if ($customer) {
                return $this->updateCustomer($customer, $data);
            }

            return $this->createCustomer($data);
        });
    }

    /**
     * Create new customer
     */
    private function createCustomer(array $data): Customer
    {
        $customer = Customer::create($this->normalizeCustomerData($data));

        // Sync addresses
        if (!empty($data['addresses'])) {
            $this->syncAddresses($customer, $data['addresses']);
        }

        // Sync default address
        if (!empty($data['defaultAddress'])) {
            $this->syncDefaultAddress($customer, $data['defaultAddress']);
        }

        // Sync metafields
        if (!empty($data['metafields']['edges'])) {
            $this->syncMetafields($customer, $data['metafields']['edges']);
        }

        $customer->markAsSynced();

        Log::info('Customer created', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return $customer->fresh(['addresses', 'defaultAddress', 'metafields']);
    }

    /**
     * Update existing customer
     */
    private function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($this->normalizeCustomerData($data));

        // Sync addresses if provided
        if (isset($data['addresses'])) {
            $this->syncAddresses($customer, $data['addresses']);
        }

        if (isset($data['defaultAddress'])) {
            $this->syncDefaultAddress($customer, $data['defaultAddress']);
        }

        if (isset($data['metafields']['edges'])) {
            $this->syncMetafields($customer, $data['metafields']['edges']);
        }

        $customer->markAsSynced();

        Log::info('Customer updated', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return $customer->fresh(['addresses', 'defaultAddress', 'metafields']);
    }

    /**
     * Resolve customer by Shopify ID or email
     */
    public function resolve(string $shopifyCustomerId = null, string $email = null): ?Customer
    {
        if ($shopifyCustomerId) {
            return Customer::byShopifyId($shopifyCustomerId)->first();
        }

        if ($email) {
            return Customer::byEmail($email)->first();
        }

        return null;
    }

    /**
     * Normalize Shopify data to our schema
     */
    private function normalizeCustomerData(array $data): array
    {
        return [
            'shopify_customer_id' => $data['id'],
            'shopify_legacy_id' => $data['legacyResourceId'] ?? null,
            'email' => $data['email'],
            'first_name' => $data['firstName'] ?? null,
            'last_name' => $data['lastName'] ?? null,
            'display_name' => $data['displayName'] ?? null,
            'phone' => $data['phone'] ?? null,
            'locale' => $data['locale'] ?? 'en',
            'state' => $data['state'] ?? 'ENABLED',
            'tax_exempt' => $data['taxExempt'] ?? false,
            'verified_email' => $data['verifiedEmail'] ?? $data['validEmailAddress'] ?? false,
            'note' => $data['note'] ?? null,
            'tags' => $data['tags'] ?? [],
            'amount_spent' => $data['amountSpent']['amount'] ?? 0,
            'number_of_orders' => is_numeric(data_get($data, 'numberOfOrders'))
                ? (int) data_get($data, 'numberOfOrders')
                : 0,
            'shopify_created_at' => isset($data['createdAt'])
                ? \Carbon\Carbon::parse($data['createdAt'])
                : null,
            'shopify_updated_at' => isset($data['updatedAt'])
                ? \Carbon\Carbon::parse($data['updatedAt'])
                : null,
        ];
    }

    /**
     * Sync addresses
     */
    private function syncAddresses(Customer $customer, array $addresses): void
    {
        foreach ($addresses as $addressData) {
            if (empty($addressData['id'])) {
                continue;
            }


            $customer->addresses()->updateOrCreate(
                ['shopify_address_id' => $addressData['id'] ?? null],
                [
                    'address1' => $addressData['address1'] ?? null,
                    'address2' => $addressData['address2'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'province' => $addressData['province'] ?? null,
                    'province_code' => $addressData['provinceCode'] ?? null,
                    'country' => $addressData['country'] ?? null,
                    'country_code' => $addressData['countryCode'] ?? null,
                    'zip' => $addressData['zip'] ?? null,
                    'phone' => $addressData['phone'] ?? null,
                    'company' => $addressData['company'] ?? null,
                    'first_name' => $addressData['firstName'] ?? null,
                    'last_name' => $addressData['lastName'] ?? null,
                ]
            );
        }
    }

    /**
     * Sync default address
     */
    private function syncDefaultAddress(Customer $customer, array $defaultAddress): void
    {
        if (empty($defaultAddress['id'])) {
            return;
        }

        $customer->addresses()->updateOrCreate(
            ['shopify_address_id' => $defaultAddress['id'] ?? null],
            [
                'address1' => $defaultAddress['address1'] ?? null,
                'address2' => $defaultAddress['address2'] ?? null,
                'city' => $defaultAddress['city'] ?? null,
                'province' => $defaultAddress['province'] ?? null,
                'province_code' => $defaultAddress['provinceCode'] ?? null,
                'country' => $defaultAddress['country'] ?? null,
                'country_code' => $defaultAddress['countryCode'] ?? null,
                'zip' => $defaultAddress['zip'] ?? null,
                'phone' => $defaultAddress['phone'] ?? null,
                'company' => $defaultAddress['company'] ?? null,
                'first_name' => $defaultAddress['firstName'] ?? null,
                'last_name' => $defaultAddress['lastName'] ?? null,
                'is_default' => true,
            ]
        );
    }

    /**
     * Sync metafields
     */
    private function syncMetafields(Customer $customer, array $metafieldEdges): void
    {
        foreach ($metafieldEdges as $edge) {
            $node = $edge['node'] ?? [];

            if (empty($node['namespace']) || empty($node['key'])) {
                continue;
            }

            $customer->metafields()->updateOrCreate(
                [
                    'namespace' => $node['namespace'],
                    'key' => $node['key'],
                ],
                [
                    'shopify_metafield_id' => $node['id'] ?? null,
                    'value' => $node['value'] ?? '',
                    'type' => $node['type'] ?? 'string',
                ]
            );
        }
    }
}
