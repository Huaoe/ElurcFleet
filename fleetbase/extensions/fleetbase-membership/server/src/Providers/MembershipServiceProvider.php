<?php

namespace Fleetbase\Membership\Providers;

use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Fleetbase\Membership\Services\SolanaRpcService;
use Fleetbase\Providers\CoreServiceProvider;

class MembershipServiceProvider extends CoreServiceProvider
{
    public $namespace = 'Fleetbase\\Membership';

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $this->mergeConfigFrom(__DIR__ . '/../../config/membership.php', 'membership');
    }

    public function register()
    {
        $this->app->singleton(SolanaRpcService::class, function ($app) {
            return new SolanaRpcService();
        });

        $this->app->singleton(MemberIdentityService::class, function ($app) {
            return new MemberIdentityService();
        });

        $this->app->singleton(MembershipVerificationService::class, function ($app) {
            return new MembershipVerificationService(
                $app->make(SolanaRpcService::class),
                $app->make(MemberIdentityService::class)
            );
        });
    }
}
