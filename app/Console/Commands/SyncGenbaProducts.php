<?php

namespace App\Console\Commands;

use App\Domains\Genba\Services\GenbaSyncService;
use App\Services\Json\ProductJsonUploadService;
use Illuminate\Console\Command;

class SyncGenbaProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:genba-products';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from Genba DB to Unify DB';

    /**
     * Execute the console command.
     */
    public function handle(GenbaSyncService $syncService, ProductJsonUploadService $uploadService)
    {
        $this->info('Syncing Genba products...');
        $syncService->syncProducts();
        $this->info('✅ Sync completed successfully.');

        $this->info('Uploading products to R2...');
        $count = $uploadService->uploadAllProducts(ignoreTimestamp: true, priceSource: 4);
        $this->info("✅ Uploaded {$count} product(s) to R2.");
    }
}
