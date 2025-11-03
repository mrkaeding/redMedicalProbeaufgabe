<?php

namespace Tests\Support;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Services\RedProvider\OrderProvider;
use App\Services\RedProvider\ProviderOrder;

class FakeOrderProvider implements OrderProvider
{
    /** @var array<int, string> */
    public array $deleted = [];

    public string $createId = 'prov-1';
    public OrderStatus $createStatus = OrderStatus::ORDERED;

    public ?ProviderOrder $nextGetOrder = null;

    public function getOrder(string $providerId): ProviderOrder
    {
        if ($this->nextGetOrder) {
            return $this->nextGetOrder;
        }
        return new ProviderOrder($providerId, OrderType::CONNECTOR, OrderStatus::COMPLETED);
    }

    public function createOrder(OrderType $type): ProviderOrder
    {
        return new ProviderOrder($this->createId, $type, $this->createStatus);
    }

    public function deleteOrder(string $providerId): void
    {
        $this->deleted[] = $providerId;
    }
}
