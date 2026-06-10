<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'sales_price_including_vat',
        'sales_price_excluding_vat',
        'discount_amount',
        'vat_amount',
        'status',
        'source',
        'failed_reason',
        'currency_code',
        'key_id',
        'redeemed_at',
        'giftcard_amount',
        'row_total',
        'retailer_order_id',
        'cost_price',
        'cost_price_euro',
        'sales_price_including_vat_eur',
        'sales_price_excluding_vat_eur',
        'discount_amount_eur',
        'vat_amount_eur',
        'giftcard_amount_eur',
        'row_total_eur',
        'sku_mapping_snapshot'
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'is_cancelled' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'sku', 'product_id');
    }

    public function scopeNeedsEuroConversion($query)
    {
        $eur = [
            'sales_price_including_vat_eur',
            'sales_price_excluding_vat_eur',
            'row_total_eur',
        ];

        $orig = [
            'sales_price_including_vat',
            'sales_price_excluding_vat',
            'row_total',
        ];

        return $query
            // Check item _eur fields
            ->where(function ($q) use ($eur) {
                foreach ($eur as $f) {
                    $q->orWhereNull($f)->orWhere($f, 0);
                }
            })
            // Check item original values
            ->where(function ($q) use ($orig) {
                foreach ($orig as $f) {
                    $q->orWhere($f, '>', 0);
                }
            });
            // // Check order payment fee
            // ->whereHas('order', function ($q) {
            //     $q->whereNull('payment_fee_eur')
            //         ->orWhere('payment_fee_eur', 0)
            //         ->where('payment_fee', '>', 0);
            // });
    }
}
