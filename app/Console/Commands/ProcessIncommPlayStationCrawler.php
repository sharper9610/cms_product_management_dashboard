<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Scraper\PsStoreScraperService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessIncommPlayStationCrawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-incomm-play-station-crawler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process each Incomm PlayStation product crawler';

    protected PsStoreScraperService $crawlerService;

    public function __construct(PsStoreScraperService $crawlerService)
    {
        parent::__construct();
        $this->crawlerService = $crawlerService;
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Incomm PlayStation crawler...');

        // Fetch all incomm products that need processing
        $products = Product::where([
            'source' => config('services.sources.incomm'),
            'status' => 1
        ])->with('prices')->get();


        if ($products->isEmpty()) {
            $this->info('No products found to process.');
            return 0;
        }


        foreach ($products as $product) {
            $this->info("Processing product ID: {$product->sku}");

            if ($product->prices->isEmpty()) {
                $this->info("  → No prices found for this product.");
                continue;
            }

            foreach ($product->prices as $price) {
                $this->info("  → Processing price ID: {$price->id} | Current price: {$price->price}");

                $scrape_url = $price->scrape_url;

                if (empty($scrape_url)) {
                    $this->info("    → No scrape URL found for this price. Skipping...");
                    continue;
                }


                try {
                    $data = $this->crawlerService->scrape($scrape_url);

                    $priceData = $data['price_data'] ?? [];



                    $formatted = [
                        'final_price' => $this->parsePrice($priceData['final_price'] ?? null),
                        'original_price' => $this->parsePrice($priceData['original_price'] ?? null),
                        'discount_percent' => $this->parseDiscount($priceData['discount_percent'] ?? null),
                        'discount_deadline' => $this->parseDate($priceData['discount_deadline'] ?? null),
                        'discount_deadline_unix' => $this->parseDateToUnix($priceData['discount_deadline'] ?? null),
                        'lowest_recent_price' => $this->parsePrice($priceData['lowest_recent_price'] ?? null),
                    ];

                    if (!is_null($formatted['discount_percent']) && !is_null($formatted['discount_deadline_unix'])) {
                        $updateData = [
                            'discount_percent' => $formatted['discount_percent'],
                            'discount_valid_to' => $formatted['discount_deadline_unix'],
                        ];
                        $price->update($updateData);
                        $this->info("    ✔ Price discount data updated for ID {$price->product_id} and Price {$price->price} updated successfully.");
                    } else {
                        $this->info("    → Skipping update because discount_percent or discount_deadline_unix is null.");
                    }


                    $this->info("    ✔ Price processed successfully.");
                } catch (\Exception $e) {
                    $this->error("    ✖ Failed to process price ID {$price->product_id}: {$e->getMessage()}");
                }

                $priceDelay = rand(8, 12);
                $this->info("    → Waiting {$priceDelay}s before next price...");
                sleep($priceDelay);
            }

            $productDelay = rand(1, 2);
            $this->info("  → Waiting {$productDelay}s before next product...");
            sleep($productDelay);
        }

        $this->info('All products processed.');
        return 0;
    }


    private function parsePrice($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,3}(?:\.\d{3})*(?:,\d{1,2})?)/', $str, $matches)) {
            $num = str_replace('.', '', $matches[1]);
            $num = str_replace(',', '.', $num);
            return floatval($num);
        }

        return null;
    }

    private function parseDiscount($str)
    {
        if (!$str) return null;
        preg_match('/(\d+)/', $str, $matches);
        return isset($matches[1]) ? (int)$matches[1] : null;
    }

    private function parseDate($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4}\s\d{1,2}:\d{2}\s?(?:AM|PM)?)/i', $str, $matches)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y h:i A', $matches[1], 'UTC');
                return $dt->format('d/m/y g:i A');
            } catch (\Exception $e) {
                return $matches[1];
            }
        }

        return $str;
    }

    private function parseDateToUnix($str)
    {
        if (!$str) return null;

        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4}\s\d{1,2}:\d{2}\s?(?:AM|PM)?)/i', $str, $matches)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y h:i A', $matches[1], 'UTC');

                $dt->setTimezone('Europe/Stockholm');

                return $dt->timestamp;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
