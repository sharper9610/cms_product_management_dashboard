<?php

namespace App\Http\Requests;

use App\Models\SkuMapping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessParentSkuOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id_2game'        => 'required|string|max:50',
            'total_price'           => 'required|numeric|min:0',
            'consumer_ip'           => 'required|ip',
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
            'country_code'          => 'required|string|size:2',
            'email'                 => 'required|email|max:255',

            'items'                             => 'required|array|min:1',
            'items.*.product_id'                => 'required|integer',
            'items.*.sales_price_including_vat' => 'required|numeric|min:0',
            'items.*.sales_price_excluding_vat' => 'required|numeric|min:0',
            'items.*.discount_amount'           => 'nullable|numeric|min:0',
            'items.*.vat_amount'                => 'required|numeric|min:0',
            'items.*.currency_code'             => 'required|string|size:3',
            'items.*.giftcard_amount'           => 'required|numeric|min:0',
            'items.*.row_total'                 => 'required|numeric|min:0',

            'items.*._sku_mapping_snapshot'     => 'nullable|string',

            'sale_transaction_id'          => 'nullable|string|max:100',
            'transactions'                 => 'sometimes|array|min:1',
            'transactions.*.id'            => 'required|string|max:255',
            'transactions.*.gateway'       => 'nullable|string|max:255',
            'transactions.*.kind'          => 'nullable|string|max:50',
            'transactions.*.status'        => 'nullable|string|max:50',
            'transactions.*.createdAt'     => 'nullable|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = collect($this->items ?? []);

            // ── Duplicate product IDs ─────────────────────────────────────────
            $duplicateProducts = $items->groupBy('product_id')
                ->filter(fn($group) => $group->count() > 1)
                ->keys();

            if ($duplicateProducts->isNotEmpty()) {
                foreach ($duplicateProducts as $id) {
                    $validator->errors()->add(
                        'items.product_id',
                        "Duplicate product ID {$id} found in items."
                    );
                }
            }

            // ── Parent SKU existence check ────────────────────────────────────
            // Each product_id must exist in sku_mappings.parent_sku.
            // foreach ($items as $index => $item) {
            //     $sku = (int) ($item['product_id'] ?? 0);

            //     $exists = SkuMapping::where('parent_sku', $sku)->exists();

            //     if (!$exists) {
            //         $validator->errors()->add(
            //             "items.{$index}.product_id",
            //             "Product ID {$sku} was not found as a known parent SKU in sku_mappings."
            //         );
            //     }
            // }
        });
    }

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
}