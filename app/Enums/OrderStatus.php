<?php

namespace App\Enums;

enum OrderStatus: string
{
    case ORDERED = 'ordered';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
}
