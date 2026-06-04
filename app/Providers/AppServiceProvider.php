<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        // Force HTTPS for all generated URLs (route(), url(), asset(), OAuth
        // redirects, email links) whenever APP_URL is https. This is env-driven
        // so local dev on http://localhost stays untouched, while production
        // behind Nginx/Certbot always produces https links — preventing
        // mixed-content and broken OAuth callbacks even if a proxy header is
        // missing. Works together with trustProxies('*') in bootstrap/app.php.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register Microsoft Azure Socialite provider
        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);
    }
}
