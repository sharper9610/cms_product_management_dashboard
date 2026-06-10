<?php 

namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenbaProductGraphic extends Model
{
    protected $connection = 'genba';
    protected $table      = 'product_graphics';
 
    public function product(): BelongsTo
    {
        return $this->belongsTo(GenbaProduct::class, 'product_id', 'id');
    }
}