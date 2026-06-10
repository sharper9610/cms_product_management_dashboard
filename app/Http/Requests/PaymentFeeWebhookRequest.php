<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentFeeWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'       => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'payment_fee'    => ['required', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'size:3'],
        ];
    }
}