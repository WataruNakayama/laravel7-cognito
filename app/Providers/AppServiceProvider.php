<?php

namespace App\Providers;

use App\Validators\CustomValidator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // validate
        Validator::resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
            return new CustomValidator($translator, $data, $rules, $messages, $customAttributes);
        });

        // Validator::extendImplicit('cognito_user_email_unique',  CognitoUserEmailUniqueValidator::class.'@validate');
    }
}
