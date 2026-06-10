<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use App\Services\Json\ProductJsonUploadService;

class UploadProductJsonToR2 extends Command
{
    protected $signature = 'products:upload-json
                            {sku? : Upload single product}
                            {--folder=product-json/v3 : R2 folder path}
                            {--ignore-timestamp : Upload all without checking update timestamps}
                            {--price-source= : Upload prices for specific source only}';

    protected $description = 'Upload product and price JSON files to Cloudflare R2';

    public function handle(ProductJsonUploadService $service): int
    {
        $sku = $this->argument('sku');
        $folder = $this->option('folder');
        $ignoreTimestamp = $this->option('ignore-timestamp') ?? null;
        $priceSource = $this->option('price-source');

        if ($sku) {
            $product = Product::with('media')
                ->where('sku', $sku)
                ->first();

            if (!$product) {
                $this->error("Product not found: {$sku}");
                return self::FAILURE;
            }

            $result = $service->upload(
                $product,
                $folder,
                $priceSource
            );

            $this->info("Product uploaded: {$result['product']}");

            if ($result['price']) {
                $this->info("Price uploaded: {$result['price']}");
            } else {
                $this->warn("No price data found for SKU {$sku}");
            }

            return self::SUCCESS;
        }

        $this->info('Starting bulk upload...');

        $result = $service->uploadAll(
            $folder,
            $ignoreTimestamp,
            $priceSource
        );

        $this->info("Products uploaded: {$result['products']}");
        $this->info("Prices uploaded: {$result['prices']}");
        $this->info('Bulk upload completed successfully.');

        return self::SUCCESS;
    }
}