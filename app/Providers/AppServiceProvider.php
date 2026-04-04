<?php

namespace App\Providers;

use App\Services\RecaptchaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RecaptchaService::class, function ($app) {
            $config = $app['config']['recaptcha'];

            $credentialsPath = $config['credentials_path'] ?? null;
            if ($credentialsPath && !str_starts_with($credentialsPath, '/') && !str_starts_with($credentialsPath, 'C:')) {
                $credentialsPath = storage_path('app/' . $credentialsPath);
            }

            return new RecaptchaService(
                siteKey: $config['site_key'] ?? '',
                projectId: $config['project_id'] ?? '',
                scoreThreshold: $config['score_threshold'] ?? 0.5,
                credentialsPath: $credentialsPath,
                credentialsJson: $config['credentials_json'] ?? null,
                enabled: $config['enabled'] ?? true,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
