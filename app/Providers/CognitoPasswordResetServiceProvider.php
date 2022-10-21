<?php

namespace App\Providers;

use App\Auth\Passwords\CognitoPasswordBrokerManager;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;

class CognitoPasswordResetServiceProvider extends PasswordResetServiceProvider
{
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new CognitoPasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }
}
