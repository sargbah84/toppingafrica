<?php

namespace App\Providers;

use App\Listeners\LogJobHistory;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Services\RecaptchaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
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
                enabled: $config['enabled'] ?? true,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);

        $jobListener = new LogJobHistory;
        Event::listen(JobProcessing::class, [$jobListener, 'handleProcessing']);
        Event::listen(JobProcessed::class, [$jobListener, 'handleProcessed']);
        Event::listen(JobFailed::class, [$jobListener, 'handleFailed']);
    }
}
