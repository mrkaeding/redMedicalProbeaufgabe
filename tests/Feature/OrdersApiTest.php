<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Jobs\SyncOrderStatusJob;
use App\Models\Order;
use App\Services\RedProvider\OrderProvider as OrderProviderContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeOrderProvider;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakeProvider(?FakeOrderProvider $fake = null): FakeOrderProvider
    {
        $fake ??= new FakeOrderProvider();
        $this->app->singleton(OrderProviderContract::class, fn () => $fake);
        return $fake;
    }

    public function test_create_order_success_connector_and_queues_status_sync(): void
    {
        Queue::fake();
        $provider = $this->bindFakeProvider();
        $provider->createId = 'prov-123';
        $provider->createStatus = OrderStatus::ORDERED;

        $resp = $this->postJson('/api/orders', [
            'name' => 'Test A',
            'type' => OrderType::CONNECTOR->value,
        ]);

        $resp->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'type', 'status']])
            ->assertJsonPath('data.type', OrderType::CONNECTOR->value)
            ->assertJsonPath('data.name', 'Test A');

        $orderId = $resp->json('data.id');
        $this->assertNotEmpty($orderId);

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame('prov-123', $order->provider_reference);
        $this->assertSame(OrderStatus::ORDERED->value, $order->status->value);

        Queue::assertPushed(SyncOrderStatusJob::class, function ($job) use ($orderId) {
            return $job->orderId === $orderId && $job->delay !== null;
        });
    }

    public function test_create_order_success_vpn_connection(): void
    {
        $this->bindFakeProvider();

        $resp = $this->postJson('/api/orders', [
            'name' => 'VPN X',
            'type' => OrderType::VPN_CONNECTION->value,
        ]);

        $resp->assertCreated()
            ->assertJsonPath('data.type', OrderType::VPN_CONNECTION->value)
            ->assertJsonPath('data.name', 'VPN X');
    }

    public function test_create_order_validation_errors_for_missing_and_invalid_type(): void
    {
        $this->bindFakeProvider();

        $this->postJson('/api/orders', [])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);

        $resp = $this->postJson('/api/orders', [
            'name' => 'X',
            'type' => 'invalid_type',
        ]);
        $resp->assertStatus(422)
            ->assertJson(function ($json) {
                $json->where('message', 'The selected type is invalid. Allowed values are: connector, vpn_connection.')
                    ->has('errors.type', 1)
                    ->where('errors.type.0', function ($v) {
                        \PHPUnit\Framework\Assert::assertIsString($v);
                        \PHPUnit\Framework\Assert::assertStringContainsString('connector, vpn_connection', $v);
                        return true;
                    })
                    ->etc();
            });
    }

    public function test_show_order_returns_resource(): void
    {
        $order = Order::create([
            'name' => 'Demo',
            'type' => OrderType::CONNECTOR->value,
            'status' => OrderStatus::PROCESSING->value,
        ]);

        $this->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.id', (string) $order->id)
            ->assertJsonPath('data.name', 'Demo')
            ->assertJsonPath('data.status', OrderStatus::PROCESSING->value)
            ->assertJsonPath('data.type', OrderType::CONNECTOR->value);
    }

    public function test_list_orders_filter_and_sort(): void
    {
        $a = Order::create(['name' => 'Alpha', 'type' => OrderType::CONNECTOR->value, 'status' => OrderStatus::ORDERED->value]);
        usleep(1000);
        $b = Order::create(['name' => 'Beta', 'type' => OrderType::VPN_CONNECTION->value, 'status' => OrderStatus::ORDERED->value]);
        usleep(1000);
        $c = Order::create(['name' => 'Albatros', 'type' => OrderType::CONNECTOR->value, 'status' => OrderStatus::ORDERED->value]);

        $res = $this->getJson('/api/orders?name=Al&sort=name&order=asc')
            ->assertOk()
            ->json('data');
        $this->assertEquals(['Albatros', 'Alpha'], array_column($res, 'name'));

        $res2 = $this->getJson('/api/orders')
            ->assertOk()
            ->json('data');
        $this->assertEquals([(string) $c->id, (string) $b->id, (string) $a->id], array_column($res2, 'id'));

        $res3 = $this->getJson('/api/orders?sort=created_at&order=asc')
            ->assertOk()
            ->json('data');
        $this->assertEquals([(string) $a->id, (string) $b->id, (string) $c->id], array_column($res3, 'id'));
    }

    public function test_delete_requires_completed_and_calls_provider_delete(): void
    {
        $provider = $this->bindFakeProvider();

        $order = Order::create([
            'name' => 'ToDelete',
            'type' => OrderType::CONNECTOR->value,
            'status' => OrderStatus::PROCESSING->value,
            'provider_reference' => 'prov-1',
        ]);

        $this->deleteJson('/api/orders/'.$order->id)
            ->assertStatus(422);

        $order->status = OrderStatus::COMPLETED;
        $order->save();

        $this->deleteJson('/api/orders/'.$order->id)
            ->assertNoContent();

        $this->assertContains('prov-1', $provider->deleted);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
