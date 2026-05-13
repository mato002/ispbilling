<?php

namespace App\Providers;

use App\View\Composers\IspBrandingComposer;
use App\View\Composers\IspSettingsComposer;
use App\View\Composers\IspSystemAnnouncementsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('isp.settings.*', IspSettingsComposer::class);
        View::composer('layouts.admin', IspBrandingComposer::class);
        View::composer('layouts.admin', IspSystemAnnouncementsComposer::class);
    }
}
