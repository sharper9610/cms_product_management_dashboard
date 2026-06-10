<?php 

namespace App\Domains\Genba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenbaPrice extends Model
{
    protected $connection = 'genba';
    protected $table      = 'prices';
 
    protected $casts = [
        'wsp'              => 'decimal:2',
        'srp'              => 'decimal:2',
        'price'            => 'decimal:2',
        'cost_estimate'    => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active'        => 'boolean',
        'is_promotion'     => 'boolean',
        'skip_update'      => 'boolean',
    ];
 
    public function product(): BelongsTo
    {
        return $this->belongsTo(GenbaProduct::class, 'product_id', 'id');
    }
}