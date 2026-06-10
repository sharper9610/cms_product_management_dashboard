<?php

namespace App\Services\Ztorm;

use App\Helpers\Helpers;
use App\Models\Localization;
use App\Models\Option;
use App\Models\Price;
use App\Models\PriceTempBackup;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductsSkipUpdate;
use App\Models\ZtormGameKey;
use App\Models\ZtormPrice;
use App\Models\ZtormProduct;
use App\Services\Cms\CurrencyExchange;
use App\Services\Repository\ProductRepository;
use Illuminate\Support\Facades\Log;

// (new App\Services\Ztorm\ProductImport)->all();
class ProductImport
{
	private $allowedCurrency = [];

	private $allowedCountry = [];

	protected $price = true;
	protected $media = true;
	protected $localizations = true;

	protected $productID = null;
	protected $importSingle = false;
	protected $isNewProduct = false;
	protected $lastAvgCostEur = 0.0;
	protected $ztormProductCount = 0;

	protected $updateFields = [];
	protected $skipUpdates = [];
	protected $langMap = [
		'en' => 'en',
		'pt-br' => 'pt-br',
		'es-mx' => 'es-419',
	];

	private $fieldMapping = [
		'name' => 'Name',
		'bundled_products' => 'BundledProducts',
		'developers' => 'Developers',
		'dlc_products' => 'DLCProducts',
		'is_dlc' => 'IsDLC',
		'dlc_master_product_id' => 'DLCMasterProductID',
		'download_date' => 'DownloadDate',
		'drm_type' => 'DRMType',
		'face_value' => 'FaceValue',
		'platform' => 'Platform',
		'pegi_ratings' => 'Ratings',
		'publisher_id' => 'PublisherID',
		'publisher_name' => 'PublisherName',
		'product_type' => 'CategoryName',
		'region_tag' => 'RegionTag',
		'release_date' => 'ReleaseDate',
		'status' => 'Status',
		'supported_languages' => 'SupportedLanguages',
		'systems' => 'Systems',
		'update_timestamp' => 'UpdateTimestamp',
	];

	private $textFieldMapping = [
		'short_description' => 'ShortDescriptions',
		'long_description' => 'LongDescriptions',
		'system_requirements' => 'Requirements',
		'legal_texts' => 'LegalTexts',
	];

	function __construct() {
		$this->allowedCurrency = Helpers::getAllowedCurrencies();
		$this->allowedCountry = Helpers::getAllowedCountries();
	}

	public function setIsNewProduct($isNewProduct)
	{
		$this->isNewProduct = $isNewProduct;

		return $this;
	}

	public function isNewProduct()
	{
		return $this->isNewProduct;
	}

	public function isOldProduct()
	{
		return ! $this->isNewProduct;
	}

	private function getSource()
	{
		return config('services.sources.ztorm');
	}

	private function setSkipUpdate(Product $newProd)
	{
		$this->skipUpdates = $newProd->skipUpdates
					->keyBy('field_name')
					->pluck('skip_update','field_name')
					->toArray();

		// ProductRepository::getSkipUpdateAttr($product->id)->toArray();
	}

	private function getSkipUpdate()
	{
		return $this->skipUpdates;
	}

	private function shouldSkip(string $field)
	{
		if ($field == '') {
			throw new \Exception("invalid field name");
		}

		$skipFields = $this->getSkipUpdate();

		if (isset($skipFields[$field])) {
			return $skipFields[$field];
		}

		return false;
	}

	private function shouldUpdate(string $field)
	{
		if ($this->isNewProduct()) {
			return true;
		}

		return ! $this->shouldSkip($field);
	}

	public function process(ZtormProduct $product)
	{
		// todo: need to update write host
		$newProd = Product::firstOrNew(['sku'=>$product->id]);

		$this->setIsNewProduct(! $newProd->id);

		$stat = $newProd->id ? 'old' : 'new';
		echo "\nProcessing: single product ID: $product->id [$stat] \n";

		if ($this->isOldProduct()) {

			if ($newProd->shouldSkipUpdate()) {
				Helpers::logOrShow("{$product->id} skip update");
				return;
			}

			$this->setSkipUpdate($newProd);
		}

		$skipUpdate = $this->getSkipUpdate();

		$newProd->source = $this->getSource();
		$newProd->editions = $product->editions?->editions;
		$newProd->default_language = $newProd->default_language == '' ? 'en' : $newProd->default_language;

		foreach ($this->fieldMapping as $shopifyField => $ztormField) {

			if (isset($skipUpdate[$shopifyField])) {
				if ($skipUpdate[$shopifyField]) {
					continue;
				}
			}

			if (trim($product->$ztormField) == '') {
				// echo "blank $ztormField \n";
				continue;
			}

			$newProd->$shopifyField = $product->$ztormField;
		}

		if ($newProd->save()) {
			echo "Product ID: {$product->id} saved\n";

			if ($this->price) {
				$this->price($product->id, $product);
			}

			if ($this->media) {
				$this->media($product);
			}

			if ($this->localizations /*&& $this->isNewProduct()*/) {
				$this->localizations($product);
			}
		}
	}

	public function onlyProduct()
	{
		$this->price = false;
		$this->media = false;
		$this->localizations = false;
		return $this;
	}

	public function onlyLang()
	{
		$this->price = false;
		$this->media = false;
		return $this;
	}

	public function onlyMedia()
	{
		$this->price = false;
		$this->localizations = false;
		return $this;
	}

	public function onlyPrice()
	{
		$this->media = false;
		$this->localizations = false;
		return $this;
	}

	public function isSingleImport()
	{
		return $this->importSingle;
	}

	public function isBulkImport()
	{
		return ! $this->importSingle;
	}

	public function localizationSingle(int $productID)
	{
		$product = ZtormProduct::find($productID);

		if (! $product) {
			echo "product not found\n";
			return;
		}

		$this->localizations($product);
	}

	public function localizations(ZtormProduct $product)
	{
		echo "\nImport language ID: {$product->id}\n";

		$product->descriptions->groupBy('LocaleCode')->each(function ($group, $locale) use ($product) {

			if (! array_key_exists($locale, $this->langMap)) {
				// echo "skip $locale \n";
				return;
			}
			
			$locale = $this->langMap[$locale] ?? null;
			if (! $locale) {
				return;
			}
			// echo "$locale ";
			
			if ($this->shouldUpdate('name')) {
				$this->updateFields['title'] = $product->Name;
			}

			$group->each(function ($item) {

				// foreach ($this->textFieldMapping as $shopifyField => $ztormField) {
				// 	if ($this->textFieldMapping[$shopifyField] == $ztormField) {
				// 		$this->updateFields[$shopifyField] = $item->Value;
				// 	}
				// }

				if ($item->FieldName == 'ShortDescriptions' && $this->shouldUpdate('short_description')) {
					$this->updateFields['short_description'] = $item->Value;
				}

				elseif ($item->FieldName == 'LongDescriptions' && $this->shouldUpdate('long_description')) {
					$this->updateFields['long_description'] = $item->Value;
				}

				elseif ($item->FieldName == 'Requirements' && $this->shouldUpdate('system_requirements')) {
					$this->updateFields['system_requirements'] = $item->Value;
				}

				elseif ($item->FieldName == 'LegalTexts' && $this->shouldUpdate('legal_texts')) {
					$this->updateFields['legal_texts'] = $item->Value;
				}
			});

			$where = [
				'product_id' => $product->id,
				'locale' => $locale,
			];

			// dd($where, $this->updateFields);

			// if (! empty($this->updateFields)) {
			// 	Localization::updateOrCreate($where, $this->updateFields);
			// }

			$localization = Localization::onWriteConnection()->firstOrNew($where);

			$flag = 'new';
			if ($localization->id) {
				$flag = 'old';
			}
			echo "$locale $flag\n";

			foreach ($this->updateFields as $key => $value) {
				// dd($localization->$key, $key);
				if ($flag == 'new') {
					$localization->$key = $value;
				}
				// already exists & field is blank
				elseif (trim($localization->$key) == '') {
					$localization->$key = $value;
				}
			}

			$localization->save();
			$this->updateFields = []; // reset
		});

		echo "\n";

		if ($this->shouldUpdate('genre_tags')) {
			$this->addGenres($product);
		}
	}

	private function getGenreValuesByLangCodes($data, array $lang)
	{
	    $result = [];

	    if (!isset($data['Genre']) || !is_array($data['Genre'])) {
	        return [];
	    }

	    $target_langs_normalized = array_map('trim', $lang);

	    foreach ($data['Genre'] as $genre_item) {
	        if (isset($genre_item['value']) && isset($genre_item['attributes']['lang'])) {
	            $current_lang = $genre_item['attributes']['lang'];

	            if (in_array($current_lang, $target_langs_normalized)) {
	                $genre_value = $genre_item['value'];
	                $result[$current_lang][] = $genre_value;
	            }
	        }
	    }

	    return $result;
	}

	private function addGenres(ZtormProduct $product)
	{
		echo "Updating genres\n";
		$genres = $this->getGenreValuesByLangCodes($product->Genres, array_keys($this->langMap));

		if (empty($genres)) {
			return;
		}

		foreach ($genres as $locale => $genre) {
			$locale = $this->langMap[$locale] ?? null;
			if (! $locale) {
				continue;
			}
			$where = [
				'product_id' => $product->id,
				'locale' => $locale,
			];
			$update = [
				// 'genre_tags' => implode(',', $genre)
				'genre_tags' => serialize($genre)
			];
			Localization::updateOrCreate($where, $update);
		}
	}

	public function media(ZtormProduct $product)
	{
		if($this->shouldSkip('product_media')) {
			echo "\nSKIP media import, ID: {$product->id}\n";
			return;
		}

		echo "\nImport media ID: {$product->id}\n";

		$this->processSteamBoxshot($product);

		$this->processZtormBoxshot($product);

		$this->processVideo($product);

		$this->processScreenshot($product);
	}

	private function processSteamBoxshot(ZtormProduct $product)
	{
		$boxshot = $product->BoxshotsCustomS3 != '' ? $product->BoxshotsCustomS3 : $product->BoxshotsSteamS3;

		$options = [
			'type' => 'boxshot',
			'source' => 'steam',
			'orientation' => 'landscape',
			'is_main' => true,
		];

		if ($product->BoxshotsCustomS3 != '') {
			ProductMedia::steam()->boxshots()
				->whereProductId($product->id)
				->where('url', 'like', '%/boxshotst/%')
				->delete();
		}

		$this->storeMedia($product->id, $boxshot['_full'] ?? null, $options);
	}

	private function processZtormBoxshot(ZtormProduct $product)
	{
		$boxshot = $product->BoxshotsS3;

		$options = [
			'type' => 'boxshot',
			'source' => 'ztorm',
			'orientation' => 'portrait',
			'is_main' => false,
		];

		$this->storeMedia($product->id, $boxshot['_medium'] ?? null, $options);
	}

	private function processVideo(ZtormProduct $product)
	{
		// save ztorm youtube videos
		$videos = $product->getVideos();
		if (! empty($videos)) {
			foreach ($videos as $video) {
				$options = [
					'type' => 'videos',
					'source' => 'ztorm',
					'is_main' => false,
				];
				$this->storeMedia($product->id, $video, $options);
			}
		}

		// save manually added youtube videos
		$videos = $product->videos;
		if ($videos->count() > 0) {
			foreach ($videos as $video) {
				$options = [
					'type' => 'videos',
					'source' => 'ztorm',
					'is_main' => false,
				];
				$this->storeMedia($product->id, 'https://www.youtube.com/watch?v='.$video->video, $options);
			}
		}
	}

	private function processScreenshot(ZtormProduct $product)
	{
		if (! $product->ScreenshotsS3 || empty($product->ScreenshotsS3)) {
			return;
		}

		foreach ($product->ScreenshotsS3 as $screenshot) {
			if (empty($screenshot)) {
				continue;
			}

			$options = [
				'type' => 'screenshot',
				'source' => 'ztorm',
				'orientation' => 'landscape',
				'is_main' => false,
			];

			$this->storeMedia($product->id, $screenshot['_full'] ?? null, $options);
		}
	}

	private function getImageOrientation($orientation='')
	{
		if ($orientation=='') {
			return null;
		}

		if ($orientation=='portrait') {
			return ProductMedia::ORIENTATION_PORTRAIT;
		}

		if ($orientation=='landscape') {
			return ProductMedia::ORIENTATION_LANDSCAPE;
		}
	}

	private function getMediaType($type='images')
	{
		if ($type=='images') {
			return ProductMedia::TYPE_IMAGES;
		}

		if ($type=='videos') {
			return ProductMedia::TYPE_VIDEOS;
		}

		if ($type=='boxshot') {
			return ProductMedia::TYPE_BOXSHOT;
		}

		if ($type=='screenshot') {
			return ProductMedia::TYPE_SCREENSHOT;
		}
	}

	private function getMediaSource($source='')
	{
		if ($source=='') {
			return null;
		}

		if ($source=='ztorm') {
			return ProductMedia::SOURCE_ZTORM;
		}

		if ($source=='steam') {
			return ProductMedia::SOURCE_STEAM;
		}

		if ($source=='manual') {
			return ProductMedia::SOURCE_MANUAL;
		}
	}

	private function storeMedia(int $productID, $url, array $options = [])
	{
		if (! $url) {
			return;
		}

		$isMain = $options['is_main'] ?? false;
		$type = $options['type'] ?? 'images';
	    $source = $options['source'] ?? 'ztorm';
	    $orientation = $options['orientation'] ?? '';

		// set is_main=false for other boxshot images
		// if ($type == 'images' && $source == 'ztorm' && $isMain) {
		// 	ProductMedia::where('product_id', $productID)->update(['is_main' => false]);
		// }

		ProductMedia::updateOrCreate([
			'product_id' => $productID,
			'url'=> $url,
		], [
			'is_main' => $isMain,
			'media_type' => $this->getMediaType($type),
			'media_source' => $this->getMediaSource($source),
			'image_orientation' => $this->getImageOrientation($orientation),
		]);
	}

	public function deleteOldPrices()
	{
		$res = Price::ztorm()->delete();
		if ($res) {
			Log::info('delete Old Prices');
		}
	}

	public function backupStempPrice()
	{
		$skipIDs = ProductsSkipUpdate::whereFieldName('steam_price')->pluck('product_id')->toArray();

		if (empty($skipIDs)) {
			echo "no rows for backup\n";
			return;
		}

		$prices = Price::query()
			->whereIn('product_id', $skipIDs)
			->where('steam_price', '>', 0)
			->get()
			->select(['product_id', 'source', 'country_code', 'steam_price'])
			->toArray();

		$insert = PriceTempBackup::insert($prices);

		if ($insert) {
			Log::info("steam price backup");
		}
	}

	public function restoreSteamPrice()
	{
		$backups = PriceTempBackup::query();

		foreach ($backups->cursor() as $backup) {
			$update = Price::where('product_id', $backup->product_id)
				->where('country_code', $backup->country_code)
				->update(['steam_price' => $backup->steam_price]);

			if ($update) {
				Log::info("{$backup->product_id} {$backup->country_code} {$backup->steam_price} restored");
			}
		}

		$res = PriceTempBackup::truncate();
		if ($res) {
			Log::info("PriceTempBackup empty");
		}
	}

	public function setLastAvgCostEur(int $productID)
	{
		$this->lastAvgCostEur = ZtormGameKey::where('ProductID', $productID)->Value('LastAvgCost');
	}

	public function getLastAvgCostEur()
	{
		return $this->lastAvgCostEur;
	}

	public function getLastAvgCost($currency)
	{
		$rate = CurrencyExchange::getRate('EUR', $currency);
		return round($this->lastAvgCostEur * $rate, 2);
	}

	public function price(int $productID, ZtormProduct $product)
	{
		if($this->shouldSkip('prices')) {
			echo "\nSKIP price import, ID: {$product->id}\n";
			return;
		}

		echo "\nImport price ID: {$productID}\n";

		// $prices = $this->getPrices($productID);
		$prices = $this->getPrices2($productID);

		if (! $prices->count()) {
			Log::info('No prices found:'.$productID);
			echo "No prices found\n";
			return;
		}

		$this->setLastAvgCostEur($productID);

		foreach ($prices as $price) {
			$this->processPrice($price);
		}
	}

	public function processPrice(ZtormPrice $price)
	{
		$newPrice = Price::firstOrNew([
			'product_id' => $price->product_id,
			'country_code'=> $price->country_code
		]);

		$stat = $newPrice->id ? 'old': 'new';

		$newPrice->product_id = $price->product_id;
		$newPrice->source = $this->getSource();
		$newPrice->currency = $price->currency;
		$newPrice->country_code = $price->country_code;
		$newPrice->price = $price->price;

		$newPrice->cost_estimate = $price->cost_estimate;
		$newPrice->discount_valid_from = $price->discount_valid_from;
		$newPrice->discount_valid_to = $price->discount_valid_to;
		$newPrice->discount_percent = $price->discount_percent;
		$newPrice->discount_valid_from_2game = $price->discount_valid_from_2game;
		$newPrice->discount_valid_to_2game = $price->discount_valid_to_2game;
		$newPrice->discount_percent_2game = $price->discount_percent_2game;
		$newPrice->is_active = $price->is_active;
		$newPrice->is_converted = $price->parent_id > 0 ? true : false;
		$newPrice->price_update_timestamp = $price->price_update_timestamp;
		$newPrice->last_avg_cost = $this->getLastAvgCost($price->currency);
		$newPrice->last_avg_cost_eur = $this->getLastAvgCostEur();

		if($this->shouldUpdate('steam_price')) {
			$newPrice->steam_price = $price->steam_price;
		}

		if ($newPrice->save()) {
			echo "$price->currency $price->country_code [$stat] saved\n";
		}
	}

	public function updateDiscontinued()
	{
		echo "\nUpdating discontinued products...\n";

		$ids = Product::select('sku')->pluck('sku')->toArray();

		$discontinuedIDs1 = ZtormProduct::select('id')
			->whereIn('id', $ids)
			->where('Status', 'Discontinued')
			->pluck('id')
			->toArray();

		$discontinuedIDs2 = ZtormGameKey::whereBetween('ProductID', [1000, 2000])
			->where('Available', 0)
			->pluck('ProductID')
			->toArray();
		
		$discontinuedIDs = array_unique(array_merge($discontinuedIDs1, $discontinuedIDs2));

		if (empty($discontinuedIDs)) {
			echo "no discontinued products found\n";
			return;
		}

		$count = Product::whereIn('sku', $discontinuedIDs)->update(['status' => false]);

		echo "$count products discontinued\n";
		Log::info("$count products discontinued");
	}

	public function all($productID=null, $dataType='')
	{
		if ($productID > 0) {
			$this->productID = $productID;
			$this->importSingle = true;
		}

		if ($dataType == 'product') {
			$this->onlyProduct();
		}

		if ($dataType == 'lang') {
			$this->onlyLang();
		}

		if ($dataType == 'media') {
			$this->onlyMedia();
		}

		if ($dataType == 'price') {
			$this->onlyPrice();
		}

		$str = $productID ? $productID : 'all';
		echo "start $str product import {$dataType}\n";

		// $products = $this->getProducts($productID);
		$products = $this->getProducts2($productID);
		if (! $this->ztormProductCount) {
			echo "no products found\n";
		}

		if ($this->isBulkImport() && ($dataType == '' || $dataType == 'price')) {
			Option::set('ztorm_price_import', 'running');
			Log::info("ztorm_{$dataType}_import_start");
		}

		foreach ($products as $product) {
			try {
				$this->process($product);
			} catch (\Exception $e) {
				echo $e->getMessage() ."\n";
				Log::error("Import Ztorm ID: $product->id ", $this->getErrDetails($e));
			}
		}

		if ($this->isBulkImport()) {
			Option::set('ztorm_price_import', 'complete');
			Log::info("ztorm_{$dataType}_import_end");
		}
	}

	public function getProducts($productID)
	{
		$query = ZtormProduct::query()
			->join('prices', 'product_gbp.id', '=', 'prices.product_id')
            ->select('product_gbp.*')
			->where('product_gbp.Status', ZtormProduct::STATUS_ACTIVE)
            ->where('prices.is_active', ZtormPrice::STATUS_ACTIVE)
            ->whereIn('prices.country_code', $this->allowedCountry)
            ->whereNull('prices.parent_id')
			->distinct();

		if ($productID > 0) {
			$query->where('product_gbp.id', $productID);
			$this->ztormProductCount = $query->count();
			// $query->ddRawSql();
			return $query->get();
		}

		$this->ztormProductCount = $query->count();
		// $query->ddRawSql();

		return $query->cursor();
	}

	public function getProducts2($productID)
	{
		$time_start = microtime(true);

		$query = ZtormProduct::query()
			->where('Status', ZtormProduct::STATUS_ACTIVE);

		if ($productID > 0) {
			$query->where('id', $productID);
			$this->ztormProductCount = $query->count();
			return $query->get();
		}

		$ids = $this->getProductIDs();
		$query->whereIn('id', $ids);
		$this->ztormProductCount = 2;

		$time_end = microtime(true);
		$time = $time_end - $time_start;
		dump($time);

		// $query->ddRawSql();
		return $query->cursor();
	}

	public function getPrices($productID)
	{
		$query = ZtormPrice::query()
			->whereProductId($productID)
			->whereIsActive(ZtormPrice::STATUS_ACTIVE)
			->whereNull('child_id')
			->whereIn('currency', $this->allowedCurrency); // must check config/currency

		// $query->ddRawSql();
		return $query->get();
	}

	public function getPrices2($productID)
	{
		$curCc = $this->getAllowedCurrencies();
		$query = ZtormPrice::query()
			->whereProductId($productID)
			->whereIsActive(ZtormPrice::STATUS_ACTIVE)
			->whereNull('child_id')
			// ->whereRaw("(`currency` = 'EUR' OR (`currency`, `country_code`) IN ($curCc))");
			->whereRaw("(`currency`, `country_code`) IN ($curCc)");

		// $query->ddRawSql();
		return $query->get();
	}

	public function getProductIDs()
	{
		$curCc = $this->getAllowedCurrencies();
		$query = ZtormPrice::query()
			->select('product_id')
			->whereIsActive(ZtormPrice::STATUS_ACTIVE)
			->whereNull('child_id')
			// ->whereRaw("(`currency` = 'EUR' OR (`currency`, `country_code`) IN ($curCc))")
			->whereRaw("(`currency`, `country_code`) IN ($curCc)")
			->groupBy('product_id')
			;

		// $query->ddRawSql();
		return $query->pluck('product_id')->toArray();
	}

	public function getErrDetails(\Exception $e)
    {
        return [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
        ];
    }

    public function getAllowedCurrencies()
    {
		$tuples = [];
    	$data = config('currency');

		foreach ($data as $currency => $countries) {
		    foreach ($countries as $country) {
		        $tuples[] = "('$currency', '$country')";
		    }
		}

		return implode(', ', $tuples);
    }

    public function disableProductsMissingPrice()
    {
    	// SELECT
		//     `sku`, products.name, prices.product_id
		// FROM
		//     `products` LEFT JOIN prices ON products.sku = prices.product_id
		// WHERE
		//     `sku` BETWEEN 9 AND 99999 AND
		//      prices.product_id IS NULL
		//     AND products.status = 1
		// GROUP BY `sku`

		return Product::query()->active()->ztorm()
			->leftJoin('prices', 'products.sku', '=', 'prices.product_id')
			->whereNull('prices.product_id') // price not exists
			->select('products.sku')
			->update(['products.status' => Product::STATUS_INACTIVE]);

		// $q->ddRawSql();
    }
}