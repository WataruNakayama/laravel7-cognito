<?php

namespace App\Providers;

use App\Auth\CognitoUserProvider;
use App\Auth\CognitoGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
         'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('cognito', function ($app, array $config) {
            return new CognitoUserProvider($app['hash'], $config['model']);
        });

        Auth::extend('cognito', function ($app, string $name, array $config) {
            return new CognitoGuard(
                Auth::createUserProvider($config['provider']),
                $app["request"],
                $name
            );
        });
    }
}
