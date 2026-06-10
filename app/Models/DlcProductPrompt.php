<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DlcProductPrompt extends Model
{
  protected $table = 'dlc_product_prompts';

  // Mass assignable attributes
  protected $fillable = [
    'template',
  ];
}
