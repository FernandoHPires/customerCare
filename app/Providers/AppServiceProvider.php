<?php

namespace App\Providers;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\Logger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ILogger::class, Logger::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if(env('APP_ENV') === 'production' || env('APP_ENV') === 'development') {
            \URL::forceScheme('https');
        }
    }
}
