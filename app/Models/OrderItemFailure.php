<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemFailure extends Model
{
        protected $fillable = [
        'order_id',
        'order_item_id',
        'retry_attempt',
        'previous_status',
        'key_id',
        'retailer_order_id',
        'failed_reason',
        'sales_price_including_vat',
        'sales_price_excluding_vat',
        'discount_amount',
        'vat_amount',
        'giftcard_amount',
        'row_total',
        'currency_code',
        'source',
        'archived_at',
    ];
}
