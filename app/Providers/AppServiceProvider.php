<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\ResponseHelper;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(ResponseHelper::class, function () {
            return new ResponseHelper();
        });
    }
}
