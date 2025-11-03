<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\RedProvider\OrderProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(35);
    }

    /**
     * Execute the job.
     */
    public function handle(OrderProvider $provider): void
    {
        $order = Order::query()->find($this->orderId);
        if (! $order) {
            return;
        }

        if (! $order->provider_reference) {
            return;
        }

        try {
            $p = $provider->getOrder($order->provider_reference);
        } catch (\Throwable $e) {
            $order->status = OrderStatus::COMPLETED;
            $order->save();
            return;
        }

        if ($order->status->value !== $p->status->value) {
            $order->status = $p->status;
            $order->save();
        }

        if ($p->status !== OrderStatus::COMPLETED) {
            $this->release(30);
        }
    }
}
