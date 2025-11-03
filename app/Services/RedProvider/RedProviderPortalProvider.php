<?php

namespace App\Services\RedProvider;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class RedProviderPortalProvider implements OrderProvider
{
    protected string $baseUrl;
    protected ?string $sslCertPath;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('redprovider.base_url'), '/');
        $this->sslCertPath = config('redprovider.ssl_cert_path');
        $this->clientId = (string) config('redprovider.client_id');
        $this->clientSecret = (string) config('redprovider.client_secret');

        if (! $this->baseUrl) {
            throw new InvalidArgumentException('redprovider.base_url is not configured.');
        }
    }

    protected function client()
    {
        $options = [];
        if ($this->sslCertPath) {
            $options['verify'] = $this->sslCertPath;
        }
        return Http::withOptions($options)->baseUrl($this->baseUrl);
    }

    protected function getAccessToken(): string
    {
        $cacheKey = 'redprovider_token_'.md5($this->clientId);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return (string) $cached;
        }

        $response = $this->client()->post('api/v1/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ])->throw();

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $ttl = (int) ($data['ttl'] ?? 60);
        if (! $token) {
            throw new \RuntimeException('Failed to obtain access token from RedProviderPortal');
        }
        Cache::put($cacheKey, $token, now()->addSeconds(max(1, $ttl - 5)));
        return (string) $token;
    }

    protected function authClient()
    {
        $token = $this->getAccessToken();
        return $this->client()->withToken($token);
    }

    public function getOrder(string $providerId): ProviderOrder
    {
        $resp = $this->authClient()->get('api/v1/order/'.urlencode($providerId))->throw();
        $data = $resp->json();
        return new ProviderOrder(
            id: (string) $data['id'],
            type: OrderType::from((string) $data['type']),
            status: OrderStatus::from((string) $data['status']),
        );
    }

    public function createOrder(OrderType $type): ProviderOrder
    {
        $resp = $this->authClient()->post('api/v1/orders', [
            'type' => $type->value,
        ])->throw();
        $data = $resp->json();
        return new ProviderOrder(
            id: (string) $data['id'],
            type: OrderType::from((string) $data['type']),
            status: OrderStatus::from((string) $data['status']),
        );
    }

    public function deleteOrder(string $providerId): void
    {
        $this->authClient()->delete('api/v1/order/'.urlencode($providerId))->throw();
    }

}
