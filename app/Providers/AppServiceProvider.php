<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Schema;
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
        if (! app()->runningInConsole() && Schema::hasTable('settings')) {
            $siteName = Setting::query()
                ->where('key', 'site_name')
                ->value('value');

            if (filled($siteName)) {
                config(['app.name' => $siteName]);
            }
        }

        if (str_starts_with(config('app.url'), 'https://')) {
            app(UrlGenerator::class)->forceScheme('https');
        }
    }
}
