<?php

namespace App\Services\RedProvider;

use App\Enums\OrderStatus;
use App\Enums\OrderType;



class MockOrderProvider implements OrderProvider
{
    /**
     * In-memory map of providerId => status
     * This is only to emulate async processing; not persisted.
     */
    private static array $orders = [];

    public function createOrder(OrderType $type): ProviderOrder
    {
        $id = bin2hex(random_bytes(8));
        self::$orders[$id] = [
            'type' => $type,
            'status' => OrderStatus::ORDERED,
            'created_at' => time(),
        ];
        return new ProviderOrder($id, $type, OrderStatus::ORDERED);
    }

    public function getOrder(string $providerId): ProviderOrder
    {
        if (! isset(self::$orders[$providerId])) {
            return new ProviderOrder($providerId, OrderType::CONNECTOR, OrderStatus::COMPLETED);
        }
        $entry = &self::$orders[$providerId];
        $age = time() - $entry['created_at'];
        if ($age >= 5) {
            $entry['status'] = OrderStatus::COMPLETED;
        } elseif ($age >= 2) {
            $entry['status'] = OrderStatus::PROCESSING;
        }
        return new ProviderOrder($providerId, $entry['type'], $entry['status']);
    }

    public function deleteOrder(string $providerId): void
    {
        unset(self::$orders[$providerId]);
    }

}
