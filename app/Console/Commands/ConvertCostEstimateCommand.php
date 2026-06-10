<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderItem;
use App\Services\Cms\CurrencyExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConvertCostEstimateCommand extends Command
{
    protected $signature = 'orders:convert-cost-estimate';
    protected $description = 'Convert cost estimate and populate cost_price & cost_price_euro for order items';

    public function handle()
    {
        OrderItem::where('cost_price', 0)
            ->chunkById(200, function ($items) {
                foreach ($items as $item) {
                    $this->updateCost($item);
                }
            });

        $this->info('Cost estimate conversion completed.');
    }

    private function updateCost(OrderItem $item)
    {
        $cmsOrder = DB::connection('mysql_cms')
            ->table('basket_gbp as b')
            ->join('basket_item_gbp as bi', 'b.BasketID', '=', 'bi.BasketID')
            ->select(
                'b.Store',
                'b.CountryRedirectCurrency',
                'bi.CostEstimate',
                'bi.CostEstimateEUR',
                'bi.Currency'
            )
            ->where('b.RetailerOrderID', $item->retailer_order_id)
            ->first();

        if (! $cmsOrder) {
            return;
        }

        $date = $item->created_at->format('Y-m-d');

        try {
            $localCost = $this->getLocalCost($cmsOrder, $date);
        } catch (\Exception $e) {
            Log::error('ConvertCostEstimateCommand '.$e->getMessage());
            $localCost = 0;
        }

        $item->update([
            'cost_price'       => $localCost,
        ]);
    }

    private function getLocalCost($cmsOrder, $order_date)
    {

        if (is_null($cmsOrder->CountryRedirectCurrency)) {
            if ($cmsOrder->Store === $cmsOrder->Currency) {
                return $cmsOrder->CostEstimate;
            }

            if ($cmsOrder->Store !== $cmsOrder->Currency) {
                $rate = CurrencyExchange::getRate($cmsOrder->Currency, $cmsOrder->Store, $order_date);
                return round($cmsOrder->CostEstimate * $rate, 2);
            }
        }

        else {
            if ($cmsOrder->Store === $cmsOrder->Currency && $cmsOrder->Store === $cmsOrder->CountryRedirectCurrency) {
                return $cmsOrder->CostEstimate;
            }

            if ($cmsOrder->Store === $cmsOrder->Currency && $cmsOrder->Store !== $cmsOrder->CountryRedirectCurrency) {
                $rate = CurrencyExchange::getRate($cmsOrder->Currency, $cmsOrder->CountryRedirectCurrency, $order_date);
                return round($cmsOrder->CostEstimate * $rate, 2);
            }
        }

        return 0;
    }
}
