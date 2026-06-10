<?php

namespace App\Console\Commands;

use App\Domains\Genba\Services\GenbaSyncService;
use App\Services\Json\ProductJsonUploadService;
use Illuminate\Console\Command;

class SyncGenbaProductPrices extends Command
{
    protected $signature = 'sync:genba-prices';

    protected $description = 'Sync only product prices from Genba DB to Unify DB';

    public function handle(GenbaSyncService $syncService, ProductJsonUploadService $uploadService)
    {
        $this->info('Syncing Genba product prices...');
        $syncService->syncPrices();
        $this->info('✅ Price sync completed successfully.');

        $this->info('Uploading prices to R2...');
        $count = $uploadService->uploadAllPrices(ignoreTimestamp: true, priceSource: 4);
        $this->info("✅ Uploaded {$count} price(s) to R2.");
    }
}
