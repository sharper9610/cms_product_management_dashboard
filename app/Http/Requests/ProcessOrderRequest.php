<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class ProcessOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Update with auth if needed
    }

    public function rules(): array
    {
        return [
            'order_id_2game' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $order = Order::where('order_id_2game', $value)->first();

                    if ($order && !in_array($order->status, [
                        OrderStatus::FAILED,
                        OrderStatus::PARTIALLY_COMPLETED,
                    ])) {
                        $fail('Order already processed and cannot be retried.');
                    }
                }
            ],
            'total_price'           => 'required|numeric|min:0',
            'consumer_ip'            => 'required|ip',
            'payment_method'        => 'required|string',
            'payment_fee'           => 'required|numeric|min:0',
            'gatewayFee'            => 'nullable|numeric|min:0',
            'shopifyPlusFee'        => 'nullable|numeric|min:0',
            'subtotal'              => 'required|numeric|min:0',
            'grand_total'           => 'required|numeric|min:0',
            'total_qty_ordered'     => 'required|integer|min:1',
            'total_amount_paid'     => 'required|numeric|min:0',
            'total_amount_ordered'  => 'required|numeric|min:0',
            'total_discount_amount' => 'nullable|numeric|min:0',
            'total_price'           => 'required|numeric|min:0',
            'country_code'          => 'required|string|size:2',
            'email'                 => 'required|email|max:255',

            'items'                             => 'required|array|min:1',
            'items.*.product_id'                => 'required|integer|exists:products,sku',
            'items.*.sales_price_including_vat' => 'required|numeric|min:0',
            'items.*.sales_price_excluding_vat' => 'required|numeric|min:0',
            'items.*.discount_amount'           => 'nullable|numeric|min:0',
            'items.*.vat_amount'                => 'required|numeric|min:0',
            'items.*.currency_code'             => 'required|string|size:3',
            'items.*.giftcard_amount'           => 'required|numeric|min:0',
            'items.*.row_total'                 => 'required|numeric|min:0',
            'items.*._sku_mapping_snapshot' => 'nullable|string',

            'sale_transaction_id' => 'nullable|string|max:100',



            'transactions'                 => 'sometimes|array|min:1',
            'transactions.*.id'            => 'required|string|max:255',
            'transactions.*.gateway'       => 'nullable|string|max:255',
            'transactions.*.kind'          => 'nullable|string|max:50',
            'transactions.*.status'        => 'nullable|string|max:50',
            'transactions.*.createdAt'     => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.product_id.exists' => 'One or more products in the order do not exist.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->all();

        throw new HttpResponseException(
            response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '422',
                    'ErrorMsg'  => 'Order Payload Validation Failed',
                    'Value'     => $errors,
                ]
            ], 422)
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = collect($this->items ?? []);

            $countryCode = strtoupper($this->input('country_code', ''));
            $currencyMap = config('currency');


            // 🔹 Check invalid products
            $invalidProducts = $items->filter(function ($item) {
                return !Product::where('sku', $item['product_id'])->exists();
            })->pluck('product_id');

            if ($invalidProducts->isNotEmpty()) {
                foreach ($invalidProducts as $id) {
                    $validator->errors()->add('items.product_id', "Product ID {$id} does not exist.");
                }
            }

            // 🔹 Check duplicate product IDs
            $duplicateProducts = $items->groupBy('product_id')
                ->filter(fn($group) => $group->count() > 1)
                ->keys();

            if ($duplicateProducts->isNotEmpty()) {
                foreach ($duplicateProducts as $id) {
                    $validator->errors()->add('items.product_id', "Duplicate product ID {$id} found in items.");
                }
            }


            // $allowedCountries = collect($currencyMap)->flatten()->unique()->all();
            // $allowedCurrencies = array_keys($currencyMap);

            // // 🔹 Validate country_code
            // if (! in_array($countryCode, $allowedCountries, true)) {
            //     $validator->errors()->add(
            //         'country_code',
            //         "Country {$countryCode} is not in the allowed country list."
            //     );
            // }

            // // 🔹 Validate currency_code for each item
            // foreach ($items as $index => $item) {
            //     $currency = strtoupper($item['currency_code'] ?? '');

            //     if (! in_array($currency, $allowedCurrencies, true)) {
            //         $validator->errors()->add(
            //             "items.{$index}.currency_code",
            //             "Currency {$currency} is not in the allowed currency list."
            //         );
            //     }
            // }


            // 🔹 Check if sales price exists in price table for products with source = 2
            foreach ($items as $index => $item) {
                $product = Product::with('prices')->where('sku', $item['product_id'])->first();

                if ($product && (int) $product->source === 2) {
                    $salesPrice = $item['sales_price_including_vat'] ?? null;

                    if ($salesPrice !== null) {
                        $exists = $product->prices()
                            ->where('is_active', 1)
                            ->where('price', $salesPrice)
                            ->exists();

                        if (!$exists) {
                            $validator->errors()->add(
                                "items.{$index}.sales_price_including_vat",
                                "Sales price {$salesPrice} for product {$product->sku} does not exist in allowed prices."
                            );
                        }
                    }
                }

                if ($product && (int) $product->source === 3) {
                    $salesPrice   = $item['sales_price_including_vat'] ?? null;
                    $currencyCode = $item['currency_code'] ?? null;
                    $countryCode  = $this->input('country_code');

                    if ($salesPrice !== null) {
                        $exists = $product->prices()
                            ->where('is_active', 1)
                            ->where('price', $salesPrice)
                            ->where('currency', $currencyCode)
                            ->where('country_code', $countryCode)
                            ->exists();

                        if (!$exists) {
                            $validator->errors()->add(
                                "items.{$index}.sales_price_including_vat",
                                "Sales price {$salesPrice} ({$currencyCode}) for product {$product->sku}"
                                    . " in country {$countryCode} does not exist in allowed prices."
                            );
                        }
                    }
                }

                if ($product && (int) $product->source === 4) {
                    $salesPrice   = $item['sales_price_including_vat'] ?? null;
                    $currencyCode = $item['currency_code'] ?? null;
                    $countryCode  = $this->input('country_code');

                    if ($salesPrice !== null) {
                        $exists = $product->prices()
                            ->where('is_active', 1)
                            ->where('price', $salesPrice)
                            ->where('currency', $currencyCode)
                            ->where('country_code', $countryCode)
                            ->exists();

                        if (!$exists) {
                            $validator->errors()->add(
                                "items.{$index}.sales_price_including_vat",
                                "Sales price {$salesPrice} ({$currencyCode}) for product {$product->sku}"
                                    . " in country {$countryCode} does not exist in allowed prices."
                            );
                        }
                    }
                }
            }
        });
    }
}
