<?php

namespace App\Providers;

use App\Listeners\LogJobHistory;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Services\RecaptchaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
            if ($credentialsPath && ! str_starts_with($credentialsPath, '/') && ! str_starts_with($credentialsPath, 'C:')) {
                $credentialsPath = storage_path('app/'.$credentialsPath);
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

        $this->throttleLivewireUpdates();
    }

    /**
     * Cap the volume of POST /livewire/update requests per IP.
     *
     * Bots replaying malformed Livewire snapshots have hammered this endpoint
     * (thousands of 422/500s from a single IP). The limit is generous enough
     * for legitimate pages with several interactive components, but cuts off
     * abusive volume at the application layer.
     */
    protected function throttleLivewireUpdates(): void
    {
        RateLimiter::for('livewire-update', function ($request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        Livewire::setUpdateRoute(function ($handle) {
            return $this->app['router']
                ->post('/livewire/update', $handle)
                ->middleware(['web', 'throttle:livewire-update']);
        });
    }
}
