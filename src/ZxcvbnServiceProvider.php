<?php

namespace REBELinBLUE\Zxcvbn;

use Illuminate\Support\ServiceProvider;
use ZxcvbnPhp\Zxcvbn;

class ZxcvbnServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $translations = realpath(__DIR__ . '/../resources/lang');

        $this->loadTranslationsFrom($translations, 'zxcvbn');

        $this->publishes([
            $translations => resource_path('lang/vendor/zxcvbn'),
        ]);

        $this->app->make('validator')->extend('zxcvbn', ZxcvbnValidator::class . '@validate');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('zxcvbn', function () {
            return new Zxcvbn();
        });

        $this->app->alias(Zxcvbn::class, 'zxcvbn');
    }
}
