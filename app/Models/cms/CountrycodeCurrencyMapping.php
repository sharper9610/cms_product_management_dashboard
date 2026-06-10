<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Model;

class CountrycodeCurrencyMapping extends Model
{
    protected $connection = 'mysql_cms';

    protected $table = 'countrycode_currency_mappings';

    protected $guarded = [];
}
