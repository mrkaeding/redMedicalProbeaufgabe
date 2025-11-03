<?php

namespace App\Providers;


use App\Services\RedProvider\MockOrderProvider;
use App\Services\RedProvider\OrderProvider as OrderProviderContract;
use App\Services\RedProvider\RedProviderPortalProvider;
use Illuminate\Support\ServiceProvider;

class OrderProviderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderProviderContract::class, function () {
            $useMock = (bool) config('redprovider.use_mock');
            if ($useMock) {
                if ($useMock) {
                    return new MockOrderProvider();
                }
            }
            return new RedProviderPortalProvider();
        });
    }
}
