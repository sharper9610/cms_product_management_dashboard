<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id_2game',
        'consumer_ip',
        'payment_method',
        'payment_fee',
        'subtotal',
        'grand_total',
        'total_qty_ordered',
        'total_amount_paid',
        'total_amount_ordered',
        'total_discount_amount',
        'total_price',
        'status',
        'country_code',
        'email',
        'payment_fee_eur',
        'shopify_plus_fee',
        'shopify_plus_fee_eur',
        'gateway_fee',
        'gateway_fee_eur',
        'sale_transaction_id',
        'retry_count',
        'last_retry_at',
        'last_failure_reason',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,     // Final model
            OrderItem::class,   // Intermediate model
            'order_id',         // Foreign key on order_items table
            'sku',              // Foreign key on products table
            'id',               // Local key on orders table
            'product_id'        // Local key on order_items table
        );
    }

    // --- Completed items ---
    public function completedItems()
    {
        return $this->items()->where('status', OrderStatus::COMPLETED);
    }

    // --- Profit ---
    public function getProfit(): float
    {
        $completedCount = $this->completedItems()->count();

        if ($completedCount === 0) {
            return 0;
        }

        return $this->getCompletedOrderTotalPrice()
            - ($this->getCompletedOrderCostPrice() + $this->getCompletedOrderVatAmount() + $this->getPaymentFee());
    }

    public function getProfitEur(): float
    {
        $completedCount = $this->completedItems()->count();

        if ($completedCount === 0) {
            return 0;
        }

        return $this->getCompletedOrderTotalPriceEur()
            - ($this->getCompletedOrderCostPriceEur() + $this->getCompletedOrderVatAmountEur() + $this->getPaymentFeeEur());
    }

    // --- Total price ---
    public function getTotalPrice(): float
    {
        return (float) $this->items->sum('row_total');
    }

    public function getTotalPriceEur(): float
    {
        return (float) $this->items->sum('row_total_eur');
    }

    public function getCompletedOrderTotalPrice(): float
    {
        return (float) $this->completedItems->sum('row_total');
    }

    public function getCompletedOrderTotalPriceEur(): float
    {
        return (float) $this->completedItems->sum('row_total_eur');
    }

    // --- Cost ---
    public function getCostPrice(): float
    {
        return (float) $this->items->sum('cost_price');
    }

    public function getCostPriceEur(): float
    {
        return (float) $this->items->sum('cost_price_euro');
    }

    public function getCompletedOrderCostPrice(): float
    {
        return (float) $this->completedItems->sum('cost_price');
    }

    public function getCompletedOrderCostPriceEur(): float
    {
        return (float) $this->completedItems->sum('cost_price_euro');
    }

    // --- VAT ---
    public function getVatAmount(): float
    {
        return (float) $this->items->sum('vat_amount');
    }

    public function getVatAmountEur(): float
    {
        return (float) $this->items->sum('vat_amount_eur');
    }

    public function getCompletedOrderVatAmount(): float
    {
        return (float) $this->completedItems->sum('vat_amount');
    }

    public function getCompletedOrderVatAmountEur(): float
    {
        return (float) $this->completedItems->sum('vat_amount_eur');
    }

    public function getPaymentFee(): float
    {
        return $this->payment_fee;
    }

    public function getPaymentFeeEur(): float
    {
        return $this->payment_fee_eur;
    }


    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class);
    }
}
