<?php

namespace Notate;

use Illuminate\Support\ServiceProvider;

class NotateServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('notate.php')
        ], 'config');

    }
}