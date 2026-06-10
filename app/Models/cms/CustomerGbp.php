<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Model;

class CustomerGbp extends Model
{
    protected $connection = 'mysql_cms';
    protected $table = 'customer_gbp';
}