<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Services\Cms\CurrencyExchange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertOrderPricesToEuro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orderItems = OrderItem::where('order_id', $this->orderId)->get();

        foreach ($orderItems as $item) {
            $rate = CurrencyExchange::getRate($item->currency_code, 'EUR');

            Log::info('currency_rate', $rate);

            if ($rate <= 0) {
                continue;
            }

            $item->sales_price_including_vat_eur = $item->sales_price_including_vat * $rate;
            $item->sales_price_excluding_vat_eur = $item->sales_price_excluding_vat * $rate;
            $item->discount_amount_eur           = $item->discount_amount * $rate;
            $item->vat_amount_eur                = $item->vat_amount * $rate;
            $item->giftcard_amount_eur           = $item->giftcard_amount * $rate;
            $item->row_total_eur                 = $item->row_total * $rate;
            $item->save();
        }
    }
}
