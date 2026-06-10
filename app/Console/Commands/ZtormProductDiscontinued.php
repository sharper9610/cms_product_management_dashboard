<?php

namespace App\Console\Commands;

use App\Services\Ztorm\ProductImport;
use Illuminate\Console\Command;

class ZtormProductDiscontinued extends Command
{
    private $import;

    public function __construct(ProductImport $import)
    {
        parent::__construct();

        $this->import = $import;
    }

    protected $signature = 'discontinued:ztorm-products';

    protected $description = 'Update discontinued ztorm products';

    public function handle()
    {
        $this->import->updateDiscontinued();

        $this->info('DONE');
    }
}
