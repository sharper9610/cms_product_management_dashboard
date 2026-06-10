<?php 
namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class GenbaProductVideo extends Model
{
    protected $connection = 'genba';
    protected $table      = 'product_videos';

    public function product(): BelongsTo
    {
        return $this->belongsTo(GenbaProduct::class, 'product_id', 'id');
    }
}