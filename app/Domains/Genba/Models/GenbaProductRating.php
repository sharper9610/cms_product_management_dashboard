<?php 

namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenbaProductRating extends Model
{
    protected $connection = 'genba';
    protected $table      = 'product_rating_systems';
 
    public function product(): BelongsTo
    {
        return $this->belongsTo(GenbaProduct::class, 'product_id', 'id');
    }
}