<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTransaction extends Model
{
    const TYPE_CANCEL = 'cancel';
    const TYPE_ORDER = 'order';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'gateway',
        'kind',
        'status',
        'transaction_created_at',
        'type',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
