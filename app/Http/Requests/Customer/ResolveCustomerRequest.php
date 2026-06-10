<?php

namespace App\Http\Requests\Customer;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResolveCustomerRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shopify_customer_id' => 'required_without:email|string|max:255',
            'email' => 'required_without:shopify_customer_id|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'shopify_customer_id.required_without' => 'Either shopify_customer_id or email is required.',
            'email.required_without' => 'Either email or shopify_customer_id is required.',
            'email.email' => 'The email must be a valid email address.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->validationErrorResponse(
                $validator->errors()->toArray()
            )
        );
    }
}
