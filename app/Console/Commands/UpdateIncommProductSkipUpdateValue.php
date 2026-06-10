<?php

namespace App\Console\Commands;

use App\Models\Localization;
use App\Models\Product;
use App\Models\ProductsSkipUpdate;
use Illuminate\Console\Command;

class UpdateIncommProductSkipUpdateValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:update-incomm-product-skip-update-value';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script to update incomm product skip update values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::where('source', 2)->get();
        $skipRules = [];

        $productFields = [
            'name',
            'platform',
            'product_type',
            'publisher_name',
            'region_tag',
            'download_date',
            'release_date',
            'developers',
            'legal_texts'
        ];

        $localizedFields = [
            'seo_tags',
            'genre_tags',
            'system_requirements',
            'short_description',
            'long_description',
            'supported_languages',
            'legal_texts',
        ];

        $locales = ['en', 'pt-br', 'es-419'];

        foreach ($products as $product) {
            foreach ($productFields as $field) {
                if (!empty($product->$field)) {
                    $skipRules[$product->sku][$field] = true;
                }
            }

            $localizations = Localization::where('product_id', $product->sku)
                ->whereIn('locale', $locales)
                ->get()
                ->keyBy('locale');

            foreach ($localizedFields as $field) {
                $allLocalesHaveValue = true;

                foreach ($locales as $locale) {
                    if (!isset($localizations[$locale]) || empty($localizations[$locale]->$field)) {
                        $allLocalesHaveValue = false;
                        break;
                    }
                }

                if ($allLocalesHaveValue) {
                    $skipRules[$product->sku][$field] = true;
                }
            }
        }



        foreach ($skipRules as $productId => $fields) {
            foreach ($fields as $fieldName => $value) {
                ProductsSkipUpdate::updateOrInsert(
                    [
                        'product_id' => $productId,
                        'field_name' => $fieldName,
                    ],
                    [
                        'skip_update' => 1,
                        'updated_at' => now(),
                        'created_at' => now(), 
                    ]
                );
            }
        }
    }
}
