<?php
namespace App\Providers;

use App\Auth\CognitoGuard;
use App\Services\Cognito\CognitoClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

class CognitoAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(CognitoClient::class, function () {
            return new CognitoClient();
        });

        $this->app['auth']->extend('cognito', function (Application $app, $name, array $config) {
            $guard = new CognitoGuard(
                $name,
                $app->make(CognitoClient::class),
                $app['auth']->createUserProvider($config['provider']),
                $app['session.store'],
                $app['request']
            );

            $guard->setCookieJar($this->app['cookie']);
            $guard->setDispatcher($this->app['events']);
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
