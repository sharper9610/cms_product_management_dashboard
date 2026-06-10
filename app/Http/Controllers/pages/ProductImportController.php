<?php

namespace App\Http\Controllers\pages;

use App\Domains\Incomm\Models\IncommProduct;
use App\Domains\Incomm\Services\IncommSyncService;
use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Option;
use App\Services\Ztorm\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    use HttpResponses;

    protected ProductImport $productImportZtorm;

    protected IncommSyncService $productImportIncomm;

    public function __construct(ProductImport $productImportZtorm, IncommSyncService $productImportIncomm)
    {
        $this->productImportZtorm = $productImportZtorm;
        $this->productImportIncomm = $productImportIncomm;
    }

    public function index()
    {
        $this->checkPageAccess('product.import');

       $data['ztormLog']= $this->getZtormImportStatus();
       $data['incommLog']= $this->getIncommImportStatus();
       return view('content.pages.pages-import-products',$data);

    }

    public function importProducts(Request $request)
    {
        $check = $request->query('check') ?? '';

        $source = strtolower($request->source);
        $sku = trim($request->sku);
        $import_all = $request->import_all ?? '';

        // ✅ Step 1: Check if any import is already running
        if ($this->isAnyImportRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'An import process is already running. Please wait until it finishes before starting a new one.',
            ]);
        }

        try {
            switch ($source) {
                case 'incomm':
                    if ($import_all === 'on') {
                        //                        return $this->importAllIncommProducts();
                    }

                    if (! empty($sku)) {

                        return $this->importSingleIncommProductNew($sku, $check);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide a SKU or select Import All.',
                    ]);

                case 'ztorm':
                    if ($import_all === 'on') {
                        //                        return $this->importAllZtormProducts();
                    }

                    if (! empty($sku)) {
                        // ✅ Optional: check existence if you have similar Ztorm check
                        return $this->importSingleZtormProductNew($sku, $check);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide a SKU or select Import All.',
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid source selected.',
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'source' => $source,
                'sku' => $sku,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error during import: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Import all Ztorm products with logging and error handling
     */
    private function importAllZtormProducts()
    {
        try {
            Log::info('Ztorm: Starting full product import...');

            // Before import — same as scheduler before()
            Option::set('ztorm_product_import', 'running');
            Option::set('ztorm_product_import_start', time());

            // Run main import
            $this->productImportZtorm->all();

            // After successful import

            Log::info('Ztorm: Full product import completed successfully.');

            return response()->json([
                'success' => true,
                'message' => 'All Ztorm products imported successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Ztorm: Failed full product import.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import all Ztorm products.',
            ]);
        } finally {
            // ✅ Always run this — success or fail
            Option::set('ztorm_product_import', 'complete');
            Option::set('ztorm_product_import_end', time());
        }
    }

    /**
     * Import single Ztorm product by SKU with validation and logging
     */
    private function importSingleZtormProduct(string $sku, $check = '')
    {
        try {

            $product = $this->productImportZtorm->getProducts($sku);

            if (! $product->count()) {

                return response()->json([
                    'success' => false,
                    'message' => 'No product found with this SKU.',
                ]);
            }

            if ($check) {
                $this->productImportZtorm->all($sku);
            } else {
                ob_start();
                $this->productImportZtorm->all($sku);
                ob_end_clean();
            }

            activity('product')
                ->event('imported')
                ->log("Product with SKU {$sku} imported successfully.");

            return response()->json([
                'success' => true,
                'message' => "Product with SKU {$sku} imported successfully.",
            ]);

        } catch (\Throwable $e) {
            Log::error("Ztorm: Failed single product import for SKU: {$sku}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import product',
            ]);
        }
    }

    private function importSingleZtormProductNew(string $skuString, $check = '')
    {
        try {
            // Split comma-separated SKUs and trim whitespace
            $skus = array_filter(array_map('trim', explode(',', $skuString)));

            if (empty($skus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SKU provided.',
                ]);
            }

            $imported = [];
            $notFound = [];

            foreach ($skus as $sku) {
                $product = $this->productImportZtorm->getProducts($sku);

                if (! $product->count()) {
                    $notFound[] = $sku;

                    continue;
                }

                // Run import (suppress output unless $check is provided)
                if ($check) {
                    $this->productImportZtorm->all($sku);
                } else {
                    ob_start();
                    $this->productImportZtorm->all($sku);
                    ob_end_clean();
                }

                $imported[] = $sku;
            }

            $totalSkus = count($skus);
            $totalImported = count($imported);
            $totalNotFound = count($notFound);

            // If all SKUs are not found → no log, just return response
            if ($totalSkus === $totalNotFound) {
                return response()->json([
                    'success' => false,
                    'imported_count' => 0,
                    'not_found_count' => $totalNotFound,
                    'message' => 'No products found for the given SKUs: '.implode(', ', $notFound),
                ]);
            }

            // Prepare properties dynamically
            $properties = [
                'requested_skus' => $skus,
                'total_requested' => $totalSkus,
            ];

            $logParts = [];

            if (! empty($imported)) {
                $properties['imported_skus'] = $imported;
                $properties['total_imported'] = $totalImported;
                $logParts[] = 'Imported: '.implode(', ', $imported);
            }

            if (! empty($notFound)) {
                $properties['not_found_skus'] = $notFound;
                $properties['total_not_found'] = $totalNotFound;
                $logParts[] = 'Not found: '.implode(', ', $notFound);
            }

            // Log only if at least one SKU was imported
            if ($totalImported > 0) {
                activity('product')
                    ->event('ztorm_import')
                    ->withProperties($properties)
                    ->log('Ztorm Import Summary → '.implode(' | ', $logParts));
            }

            // Prepare message
            $message = implode(' | ', $logParts);

            return response()->json([
                'success' => true,
                'imported_count' => $totalImported,
                'not_found_count' => $totalNotFound,
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
            Log::error('Ztorm: Failed single product import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import product(s).',
            ]);
        }
    }

    public function importPrices(Request $request)
    {
        $source = strtolower(trim($request->source));
        $sku = trim($request->sku);
        $importAll = $request->import_all ?? '';

        // ✅ Step 1: Prevent concurrent import
        if ($this->isAnyImportRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'An import process is already running. Please wait until it finishes before starting a new one.',
            ]);
        }

        try {
            switch ($source) {
                // 🟢 INCOMM SOURCE
                case 'incomm':
                    //          if ($importAll === 'on') {
                    //            return $this->importAllIncommProducts();
                    //          }

                    if (! empty($sku)) {
                        // ✅ Verify product existence before importing
                        //                        if (! $this->checkIncommProductExitBySku($sku)) {
                        //                            Log::warning("Incomm: No product found for SKU: {$sku}");
                        //
                        //                            return response()->json([
                        //                                'success' => false,
                        //                                'message' => "No Incomm product found with SKU: {$sku}.",
                        //                            ]);
                        //                        }

                        return $this->importSingleIncommProduct($sku);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide a SKU or select Import All.',
                    ]);

                    // 🔵 ZTORM SOURCE
                case 'ztorm':
                    //          if ($importAll === 'on') {
                    //            return $this->importAllZtormPrices();
                    //          }

                    if (! empty($sku)) {
                        return $this->importSingleZtormPrice($sku);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide a SKU or select Import All.',
                    ]);

                    // ❌ INVALID SOURCE
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid source selected. Please choose Incomm or Ztorm.',
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error('Price import failed', [
                'message' => $e->getMessage(),
                'source' => $source,
                'sku' => $sku,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error during import: '.$e->getMessage(),
            ]);
        }
    }

    private function importAllZtormPrices()
    {
        try {
            Log::info('Ztorm: Starting full product import...');
            $this->productImportZtorm->all(null, 'price');

            Log::info('Ztorm: Full prices import completed successfully.');

            return response()->json([
                'success' => true,
                'message' => 'All Ztorm prices imported successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Ztorm: Failed full prices import.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import all ztorm prices',
            ]);
        }
    }

    /**
     * ✅ Import single Ztorm prices by SKU with validation and logging
     */
    private function importSingleZtormPrice(string $sku)
    {
        try {
            Log::info("Ztorm: Fetching product for SKU: {$sku}");

            $product = $this->productImportZtorm->getProducts($sku);

            if (! $product->count()) {
                Log::warning("Ztorm: No product found for SKU: {$sku}");

                return response()->json([
                    'success' => false,
                    'message' => 'No product found with this SKU.',
                ]);
            }

            Log::info("Ztorm: Starting prices import for SKU: {$sku}");
            $this->productImportZtorm->all($sku, 'price');

            Log::info("Ztorm: prices import completed for SKU: {$sku}");

            return response()->json([
                'success' => true,
                'message' => "prices with SKU {$sku} imported successfully.",
            ]);

        } catch (\Throwable $e) {
            Log::error("Ztorm: Failed prices import for SKU: {$sku}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import price',
            ]);
        }
    }

    private function importAllIncommProducts()
    {
        try {
            Log::info('Incomm: Starting full product import...');

            Option::set('incomm_product_import', 'running');
            Option::set('incomm_product_import_start', time());

            $this->productImportIncomm->syncProducts();

            Log::info('Incomm: Full product import completed successfully.');

            return response()->json([
                'success' => true,
                'message' => 'All Incomm products imported successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Incomm: Failed full product import.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import all ztorm products',
            ]);
        } finally {
            // ✅ Always run this — success or fail
            Option::set('incomm_product_import', 'complete');
            Option::set('incomm_product_import_end', time());
        }
    }

    /**
     * Import single Incomm product by SKU with validation and logging
     */
    private function importSingleIncommProduct(string $sku, $check = '')
    {
        try {

            $exists = $this->checkIncommProductExitBySku($sku);

            if (! $exists) {

                return response()->json([
                    'success' => false,
                    'message' => 'No product found with this SKU.',
                ]);
            }

            $incommProduct = IncommProduct::with([
                'idMapping',
                'cardImages',
                'commission',
                'productLine',
            ])->whereHas('idMapping', function ($query) use ($sku) {
                $query->where('productIdInt', $sku);
            })->first();

            // ✅ Suppress internal echo output

            if ($check) {
                $this->productImportIncomm->syncSingleProduct($incommProduct);

            } else {
                ob_start();
                $this->productImportIncomm->syncSingleProduct($incommProduct);
                ob_end_clean();
            }
            activity('product')
                ->event('imported')
                ->log("Product with SKU {$sku} imported successfully.");

            return response()->json([
                'success' => true,
                'message' => "Product with SKU {$sku} imported successfully.",
            ]);

        } catch (\Throwable $e) {
            Log::error("Incomm: Failed single product import for SKU: {$sku}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import product',
            ]);
        }
    }

    private function importSingleIncommProductNew(string $skuString, $check = '')
    {
        try {
            // Support both single SKU and comma-separated SKUs
            $skus = strpos($skuString, ',') !== false
              ? array_filter(array_map('trim', explode(',', $skuString)))
              : [trim($skuString)];

            if (empty($skus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SKU provided.',
                ]);
            }

            $imported = [];
            $notFound = [];

            foreach ($skus as $sku) {
                // Check if product exists first
                $exists = $this->checkIncommProductExitBySku($sku);

                if (! $exists) {
                    $notFound[] = $sku;

                    continue;
                }

                // Fetch product details
                $incommProduct = IncommProduct::with([
                    'idMapping',
                    'cardImages',
                    'commission',
                    'productLine',
                ])->whereHas('idMapping', function ($query) use ($sku) {
                    $query->where('productIdInt', $sku);
                })->first();

                if (! $incommProduct) {
                    $notFound[] = $sku;

                    continue;
                }

                // Import product (suppress output if not checking)
                if ($check) {
                    $this->productImportIncomm->syncSingleProduct($incommProduct);
                } else {
                    ob_start();
                    $this->productImportIncomm->syncSingleProduct($incommProduct);
                    ob_end_clean();
                }

                $imported[] = $sku;
            }

            $totalSkus = count($skus);
            $totalImported = count($imported);
            $totalNotFound = count($notFound);

            // If all SKUs are not found → no log, just return response
            if ($totalSkus === $totalNotFound) {
                return response()->json([
                    'success' => false,
                    'imported_count' => 0,
                    'not_found_count' => $totalNotFound,
                    'message' => 'No products found for the given SKU(s): '.implode(', ', $notFound),
                ]);
            }

            // Prepare properties for activity log
            $properties = [
                'requested_skus' => $skus,
                'total_requested' => $totalSkus,
            ];

            $logParts = [];

            if (! empty($imported)) {
                $properties['imported_skus'] = $imported;
                $properties['total_imported'] = $totalImported;
                $logParts[] = 'Imported: '.implode(', ', $imported);
            }

            if (! empty($notFound)) {
                $properties['not_found_skus'] = $notFound;
                $properties['total_not_found'] = $totalNotFound;
                $logParts[] = 'Not found: '.implode(', ', $notFound);
            }

            // Log activity only if at least one imported
            if ($totalImported > 0) {
                activity('product')
                    ->event('incomm_import')
                    ->withProperties($properties)
                    ->log('Incomm Import Summary → '.implode(' | ', $logParts));
            }

            // Prepare message
            $message = implode(' | ', $logParts);

            return response()->json([
                'success' => true,
                'imported_count' => $totalImported,
                'not_found_count' => $totalNotFound,
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
            Log::error('Incomm: Failed product import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import product(s).',
            ]);
        }
    }

    /**
     * ✅ Check if any import process is currently running
     */
    private function isAnyImportRunning(): bool
    {
        return Option::whereIn('key', [
            'ztorm_price_import',
            'ztorm_product_import',
            'incomm_product_import',
        ])
            ->whereRaw("JSON_UNQUOTE(value) = 'running'")
            ->exists();
    }

    private function checkIncommProductExitBySku($sku): bool
    {
        if (empty($sku)) {
            return false;
        }

        return IncommProduct::whereHas('idMapping', function ($query) use ($sku) {
            $query->where('productIdInt', $sku);
        })->exists();
    }



  private function getZtormImportStatus()
  {
    $options = Option::whereIn('key', [
      'ztorm_product_import_start',
      'ztorm_product_import_end'
    ])->pluck('value', 'key');

    $start = $options['ztorm_product_import_start'] ?? null;
    $end   = $options['ztorm_product_import_end'] ?? null;

    // Format datetime
    $startDate = $start ? date('Y-m-d H:i:s', $start) : "";
    $endDate   = $end ? date('Y-m-d H:i:s', $end) : "";

    // Running condition
    if ($start > $end) {
      return [
        'status'     => 'running',
        'start_time' => $startDate,
        'end_time'   => ""   // empty because still running
      ];
    }

    // Completed condition
    return [
      'status'     => 'completed',
      'start_time' => $startDate,
      'end_time'   => $endDate
    ];
  }


  private function getIncommImportStatus()
  {
    $options = Option::whereIn('key', [
      'incomm_product_import',
      'incomm_product_sync_start',
      'incomm_product_sync_end'
    ])->pluck('value', 'key');

    $start  = $options['incomm_product_sync_start'] ?? null;
    $end    = $options['incomm_product_sync_end'] ?? null;
    $status = $options['incomm_product_import'] ?? null;

    // Format datetime
    $startDate = $start ? date('Y-m-d H:i:s', $start) : "";
    $endDate   = $end ? date('Y-m-d H:i:s', $end) : "";

    // Status logic
    if ($status === "complete") {
      $finalStatus = "completed";
    } elseif (!empty($status)) {
      $finalStatus = $status; // pass other values as they are
    } else {
      $finalStatus = ""; // empty if not exists
    }

    return [
      'status'     => $finalStatus,
      'start_time' => $startDate,
      'end_time'   => $endDate
    ];
  }


}
