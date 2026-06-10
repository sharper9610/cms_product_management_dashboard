<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportedLanguagePrompt extends Model
{

  protected $table = 'supported_language_prompts';

  // Mass assignable attributes
  protected $fillable = [
    'name',
    'interface',
    'full_audio',
    'subtitles',

  ];
}
