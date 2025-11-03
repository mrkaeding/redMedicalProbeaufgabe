<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    protected $table = 'orders';

    protected $fillable = [
        'id',
        'name',
        'type',
        'status',
        'provider_reference',
    ];

    protected $casts = [
        'type' => OrderType::class,
        'status' => OrderStatus::class,
    ];
}
