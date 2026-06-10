<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class UpdateProductDefaultLanguageAndType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-default-language-and-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product default_language and product_type based on source value';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting product language update...');

        // Update source 1 => en
        $countEn = Product::where('source', 1)
            ->update(['default_language' => 'en']);

        // Update source 2 => pt-br
        $countPt = Product::where('source', 2)
            ->update(['default_language' => 'pt-br', 'product_type' => 'Top up']);

        $this->info("✅ Updated {$countEn} products to 'en'");
        $this->info("✅ Updated {$countPt} products to 'pt-br' and product type to Top up");
        $this->info('Product language update completed.');

        return Command::SUCCESS;
    }
}
