<?php

namespace REBELinBLUE\Zxcvbn;

use Illuminate\Support\ServiceProvider;
use ZxcvbnPhp\Zxcvbn;

class ZxcvbnServiceProvider extends ServiceProvider
{

    const TRANSLATIONS_PATH = __DIR__ . '/../resources/lang';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(self::TRANSLATIONS_PATH, 'zxcvbn');

        $this->publishes([
            self::TRANSLATIONS_PATH => resource_path('lang/vendor/zxcvbn'),
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
        $this->app->alias(Zxcvbn::class, 'zxcvbn');
    }
}
