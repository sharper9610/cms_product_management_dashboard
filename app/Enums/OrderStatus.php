<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case COMPLETED  = 'COMPLETED';
    case PARTIALLY_COMPLETED     = 'PARTIALLY_COMPLETED';
    case FAILED     = 'FAILED';
    case CANCELLED     = 'CANCELLED';
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
}
