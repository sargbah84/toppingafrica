<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Logged in');
    }
}
