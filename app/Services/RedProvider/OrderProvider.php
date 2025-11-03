<?php

namespace App\Services\RedProvider;

use App\Enums\OrderStatus;
use App\Enums\OrderType;

class ProviderOrder
{
    public function __construct(
        public string $id,
        public OrderType $type,
        public OrderStatus $status,
    ) {}
}

interface OrderProvider
{

    /**
     * Fetch a provider order by provider id.
     */
    public function getOrder(string $providerId): ProviderOrder;

    /**
     * Create an order at the provider and return its representation (with provider id and current status).
     */
    public function createOrder(OrderType $type): ProviderOrder;

    /**
     * Delete a provider order by provider id.
     */
    public function deleteOrder(string $providerId): void;
}
