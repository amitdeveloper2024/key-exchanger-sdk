<?php
namespace amitdeveloper2024\KeyExchanger;

use Illuminate\Support\ServiceProvider;

class KeyExchangerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/key_exchanger.php',
            'key_exchanger'
        );

        $this->app->singleton(
            KeyExchangerManager::class
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/key_exchanger.php' =>
                config_path('key_exchanger.php'),
        ], 'key-exchanger-config');
    }
}