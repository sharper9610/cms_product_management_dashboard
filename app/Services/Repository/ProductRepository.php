<?php

namespace App\Services\Repository;

use App\Models\ProductsSkipUpdate;

class ProductRepository
{
	public static function getSkipUpdateAttr($id, $col=['*'])
	{
		return ProductsSkipUpdate::whereProductId($id)->select($col)->get();
	}
}