<?php

namespace App\Console\Commands;

use App\Services\Ztorm\ProductImport;
use Illuminate\Console\Command;

class ZtormProductImport extends Command
{
    private $import;

    public function __construct(ProductImport $import)
    {
        parent::__construct();

        $this->import = $import;
    }

    protected $signature = 'import:ztorm-products {product_id?} {data_type?}';

    protected $description = 'Import or update ztorm products from CMS';

    public function handle()
    {
        $productID = $this->argument('product_id');
        $dataType = $this->argument('data_type');

        $this->import->all($productID, $dataType);

        $this->info('Import completed');
    }
}
