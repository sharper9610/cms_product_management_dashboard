<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'order_id' => 'required|string|max:255',
            'transactions'             => 'sometimes|array|min:1',
            'transactions.*.id'        => 'required|string|max:255',
            'transactions.*.gateway'   => 'nullable|string|max:255',
            'transactions.*.kind'      => 'nullable|string|max:50',
            'transactions.*.status'    => 'nullable|string|max:50',
            'transactions.*.createdAt' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'order_id is required',
            'transactions.array' => 'transactions must be an array',
            'transactions.*.id.required' => 'transaction id is required',
        ];
    }
}
