<?php

namespace App\Http\Requests\Webhooks;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SkuMappingWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event'                => ['required', 'string', 'max:255'],
            'label'                => ['nullable', 'string', 'max:255'],
            'kind'                 => ['nullable', 'string', 'max:100'],
            'scope'                => ['nullable', 'string', 'max:100'],
            'generated_at'         => ['nullable', 'string'],
            'count'                => ['nullable', 'integer', 'min:0'],
            'data'                 => ['required', 'array', 'min:1'],
            'data.*.parent_sku'    => ['required', 'string'],
            'data.*.child_skus'    => ['required', 'array'],
            'data.*.child_skus.*'  => ['string'],
            'data.*.mapped_at'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'event.required'             => 'The webhook event type is required.',
            'data.required'              => 'The payload must contain a data array.',
            'data.min'                   => 'The data array must not be empty.',
            'data.*.parent_sku.required' => 'Each mapping must have a parent_sku.',
            'data.*.parent_sku.string'   => 'The parent_sku must be a string.',
            'data.*.child_skus.required' => 'Each mapping must have a child_skus array.',
            'data.*.child_skus.array'    => 'The child_skus must be an array.',
            'data.*.child_skus.*.string' => 'Each child SKU must be a string.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401)
        );
    }
}