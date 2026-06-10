<?php 

namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenbaProductLanguage extends Model
{
    protected $connection = 'genba';
    protected $table      = 'product_languages';
 
    public function product(): BelongsTo
    {
        return $this->belongsTo(GenbaProduct::class, 'product_id', 'id');
    }
}