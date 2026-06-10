<?php

namespace App\Services\OrderProcessing;

use App\Models\OrderItem;

interface OrderItemProcessorInterface
{
    public function process(OrderItem $item): bool;
}
