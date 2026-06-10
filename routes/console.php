<?php

use App\Models\Option;
use App\Services\Ztorm\ProductImport;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('run {method} {param?}', function () {

    $method = $this->argument('method');
    $param = $this->argument('param');
    $import = new ProductImport;
    // $import = new App\Services\OrderProcessing\ZtormProcessor;

    if (method_exists($import, $method)) {
        $import->$method($param);
        return;
    }

    $this->error("method: $method n/a");
})->purpose('run dynamic class');


foreach (['05:00', '11:00', '17:00', '23:00'] as $time) {
    Schedule::command('sync:pn-products')
        ->dailyAt($time)
        ->withoutOverlapping()
        ->before(function () {
            Option::set('pn_product_sync_start', time());
            Option::set('pn_product_import', 'running');
        })
        ->after(function () {
            Option::set('pn_product_sync_end', time());
            Option::set('pn_product_import', 'complete');
        });
}


// ─────────────────────────────────────────────────────────────────────────────
// Genba Products — every 12hr at 06:00 and 18:00
// ─────────────────────────────────────────────────────────────────────────────

foreach (['06:00', '18:00'] as $time) {
    Schedule::command('sync:genba-products')
        ->dailyAt($time)
        ->withoutOverlapping()
        ->before(function () {
            Option::set('genba_product_sync_start', time());
            Option::set('genba_product_import', 'running');
        })
        ->after(function () {
            Option::set('genba_product_sync_end', time());
            Option::set('genba_product_import', 'complete');
        });
}


// ─────────────────────────────────────────────────────────────────────────────
// Genba Prices — every 6hr at 09:00, 15:00, 21:00, 03:00
// ─────────────────────────────────────────────────────────────────────────────

foreach (['03:00', '09:00', '15:00', '21:00'] as $time) {
    Schedule::command('sync:genba-prices')
        ->dailyAt($time)
        ->withoutOverlapping()
        ->before(function () {
            Option::set('genba_price_sync_start', time());
            Option::set('genba_price_sync', 'running');
        })
        ->after(function () {
            Option::set('genba_price_sync_end', time());
            Option::set('genba_price_sync', 'complete');
        });
}

// foreach (['05:00', '17:00'] as $time) {
//     Schedule::command('sync:incomm-products')
//         ->dailyAt($time)
//         ->withoutOverlapping()
//         ->before(function () {
//             Option::set('incomm_product_sync_start', time());
//             Option::set('incomm_product_import', 'running');
//         })
//         ->after(function () {
//             Option::set('incomm_product_sync_end', time());
//             Option::set('incomm_product_import', 'complete');
//         });
// }

Schedule::command('product:localization')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->before(function () {
        Option::set('product_localization_start', time());
        Option::set('product_localization', 'running');
    })
    ->after(function () {
        Option::set('product_localization_end', time());
        Option::set('product_localization', 'complete');
    });


Schedule::command('import:ztorm-products')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->before(function () {
        Option::set('ztorm_product_import_start', time());
        (new ProductImport)->backupStempPrice();
        // (new ProductImport)->deleteOldPrices();
    })
    ->after(function () {
        (new ProductImport)->restoreSteamPrice();
        (new ProductImport)->updateDiscontinued();
        (new ProductImport)->disableProductsMissingPrice();
        Option::set('ztorm_product_import_end', time());
    });

Schedule::command('import:ztorm-products "" price')
    ->withoutOverlapping()
    ->hourly()
    ->when(function () {
        // in CMS run at 9,15,21 and take 57mins to complete
        return in_array(date('H'), [16, 22]);
    });

// Schedule::command('products:update-default-language-and-type')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('products:process-incomm-play-station-crawler')->hourly()->withoutOverlapping();
Schedule::command('orders:convert-to-eur --all')->dailyAt('00:00')->withoutOverlapping()->runInBackground();
Schedule::command('orders:convert-cost-estimate')->dailyAt('01:00')->withoutOverlapping()->runInBackground();

Schedule::command('products:generate-slugs')->dailyAt('10:30')->withoutOverlapping();
Schedule::command('products:upload-json')->dailyAt('02:00')->withoutOverlapping()->runInBackground();
Schedule::command('sku-mapping:sync-from-r2')->twiceDaily(0, 12)->withoutOverlapping()->runInBackground();
