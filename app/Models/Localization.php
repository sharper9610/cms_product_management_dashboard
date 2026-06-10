<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Localization extends Model
{
  protected $table = 'localizations';

  protected $guarded = [];

  protected $appends = [
    'supported_languages_formatted',
    'supported_languages_raw',
  ];

  // protected $fillable = [ 'product_id', 'locale', 'title', 'short_description', 'long_description', 'system_requirements', 'seo_tags', 'genre_tags', 'franchise_tags', 'created_at', 'updated_at' ];

  // Belongs to a product
  public function product()
  {
    return $this->belongsTo(Product::class, 'product_id', 'sku');
  }

  
  public function scopeWithDescription(Builder $query): void
  {

    $query->where(function ($q) {
      // Short description check
      $q->whereNotNull('short_description')
        ->where('short_description', '!=', '');
    })->orWhere(function ($q) {
      // Long description check
      $q->whereNotNull('long_description')
        ->where('long_description', '!=', '');
    });
  }
  public function scopeKeptLocales(Builder $query): void
  {
    // Refactored your logic for clarity and efficiency.
    $query->where(function ($q) {
      $q->where('locale', 'es-419')
        // Exclude all other 'es-*' languages, but keep all non-'es-*' languages.
        ->orWhere('locale', 'NOT LIKE', 'es-%');
    });
  }
  public function scopeWithTags(Builder $query): void
  {
    // Checks for any non-null tag field
    $query->whereNotNull('seo_tags')
      ->orWhereNotNull('genre_tags')
      ->orWhereNotNull('franchise_tags')
      ->orWhereNotNull('community_tags');
  }
  public function scopeWithSystemRequirements(Builder $query): void
  {
    // Using != '' is often better than just checking for NOT NULL when dealing with user-editable text fields.
    $query->whereNotNull('system_requirements')
      ->where('system_requirements', '!=', '');
  }
  public function scopeWithLegalTexts(Builder $query): void
  {
    // Using != '' is often better than just checking for NOT NULL when dealing with user-editable text fields.
    $query->whereNotNull('legal_texts')
      ->where('legal_texts', '!=', '');
  }
  public function scopeWithSupportedLanguages(Builder $query): void
  {
    // Using != '' is often better than just checking for NOT NULL when dealing with user-editable text fields.
    $query->whereNotNull('supported_languages')
      ->where('supported_languages', '!=', '');
  }

  public function getSupportedLanguagesAttribute(): array
  {
    $html = $this->attributes['supported_languages'] ?? '';

    if (empty($html)) {
      return [];
    }

    $languageMap = config('languages');

    $text = html_entity_decode($html);

    $text = preg_replace('/(&lt;br\s*\/&gt;|<br\s*\/?>)/i', "\n", $text);

    $text = strip_tags($text);

    $lines = explode("\n", $text);

    $languages = [];

    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line)) {
        continue;
      }

      if (strpos($line, ':') !== false) {
        [$key, $values] = explode(':', $line, 2);

        $key = \Illuminate\Support\Str::snake(trim($key));

        $valuesArray = array_filter(array_map('trim', explode(',', $values)));

        $codesArray = array_map(function ($lang) use ($languageMap) {
          return $languageMap[$lang] ?? strtolower($lang);
        }, $valuesArray);

        if (isset($languages[$key])) {
          $languages[$key] = array_unique(array_merge($languages[$key], $codesArray));
        } else {
          $languages[$key] = $codesArray;
        }
      }
    }

    return $languages;
  }

  public function getSupportedLanguagesRawAttribute()
  {
    return $this->attributes['supported_languages'];
  }
  public function getSupportedLanguagesFormattedAttribute()
  {
    $raw = $this->attributes['supported_languages'] ?? null;

    if (empty($raw)) {
      return [];
    }

    // Decode HTML entities
    $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Normalize non-breaking spaces to regular spaces
    $decoded = str_replace("\xc2\xa0", ' ', $decoded); // UTF-8 NBSP
    $decoded = str_replace('&nbsp;', ' ', $decoded);   // Just in case

    // Remove <p> tags, keep <br>
    $decoded = strip_tags($decoded, '<br>');

    // Split by <br> or newline
    $lines = preg_split('/<br\s*\/?>|\r?\n/', $decoded, -1, PREG_SPLIT_NO_EMPTY);

    $result = [];

    foreach ($lines as $line) {
      // Normalize spaces
      $line = preg_replace('/\s+/', ' ', $line);
      $line = trim($line);

      if ($line === '') {
        continue;
      }

      // Split by first colon
      if (strpos($line, ':') !== false) {
        [$key, $value] = explode(':', $line, 2);

        // Normalize key
        $key = strtolower(trim(str_replace('-', '_', $key)));
        $key = preg_replace('/\s+/', '_', $key);

        // ✅ Split by comma OR slash
        $items = array_filter(array_map(function ($v) {
          $v = preg_replace('/\s+/', ' ', $v); // collapse spaces

          return trim($v);
        }, preg_split('/[,\/]/', $value)));

        $result[$key] = $items;
      }
    }

    return $result;
  }

  public function scopeWhereNullOrEmpty($query, $field)
  {
    return $query->where(function ($q) use ($field) {
      $q->whereNull($field)
        ->orWhere($field, '');
    });
  }


}
