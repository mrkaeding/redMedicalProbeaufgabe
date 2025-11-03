<?php

namespace Tests\Unit;

use App\Services\RedProvider\MockOrderProvider;
use App\Services\RedProvider\OrderProvider as OrderProviderContract;
use App\Services\RedProvider\RedProviderPortalProvider;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OrderProviderBindingTest extends TestCase
{
    public function test_resolves_mock_provider_when_flag_true(): void
    {
        Config::set('redprovider.use_mock', true);
        $instance = $this->app->make(OrderProviderContract::class);
        $this->assertInstanceOf(MockOrderProvider::class, $instance);
    }

    public function test_resolves_real_provider_when_flag_false(): void
    {
        Config::set('redprovider.use_mock', false);
        Config::set('redprovider.base_url', 'https://localhost:3000');
        Config::set('redprovider.client_id', 'Fun');
        Config::set('redprovider.client_secret', '=work@red');
        $instance = $this->app->make(OrderProviderContract::class);
        $this->assertInstanceOf(RedProviderPortalProvider::class, $instance);
    }
}
