<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Services\RedProvider\RedProviderPortalProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class RedProviderPortalProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('redprovider.base_url', 'https://localhost:3000');
        Config::set('redprovider.client_id', 'Fun');
        Config::set('redprovider.client_secret', '=work@red');
        Config::set('redprovider.ssl_cert_path', null);
        Cache::clear();
    }

    public function test_token_is_cached_and_bearer_header_used_for_requests(): void
    {
        $tokenCalls = 0;

        Http::fake(function ($request) use (&$tokenCalls) {
            $url = $request->url();
            $path = parse_url($url, PHP_URL_PATH) ?? '';

            if (str_ends_with($path, '/api/v1/token')) {
                $tokenCalls++;
                return Http::response(['access_token' => 'abc123', 'ttl' => 60], 200);
            }

            $auth = $request->header('Authorization')[0] ?? '';
            Assert::assertSame('Bearer abc123', $auth, 'Bearer token missing or incorrect');

            if (str_ends_with($path, '/api/v1/orders') && $request->method() === 'POST') {
                $body = $request->data();
                Assert::assertSame(OrderType::CONNECTOR->value, $body['type'] ?? null);
                return Http::response([
                    'id' => 'p-1',
                    'type' => OrderType::CONNECTOR->value,
                    'status' => OrderStatus::ORDERED->value,
                ], 201);
            }

            if (preg_match('#/api/v1/order/(.+)$#', $path) && $request->method() === 'GET') {
                return Http::response([
                    'id' => 'p-1',
                    'type' => OrderType::CONNECTOR->value,
                    'status' => OrderStatus::PROCESSING->value,
                ], 200);
            }

            if (preg_match('#/api/v1/order/(.+)$#', $path) && $request->method() === 'DELETE') {
                return Http::response([], 204);
            }

            return Http::response(['unexpected' => $path], 500);
        });

        $provider = new RedProviderPortalProvider();

        $created = $provider->createOrder(OrderType::CONNECTOR);
        $this->assertSame('p-1', $created->id);
        $this->assertSame(OrderStatus::ORDERED->value, $created->status->value);

        $got = $provider->getOrder('p-1');
        $this->assertSame(OrderStatus::PROCESSING->value, $got->status->value);

        $provider->deleteOrder('p-1');

        $this->assertSame(1, $tokenCalls, 'Token endpoint should be called exactly once due to caching');
    }
}
