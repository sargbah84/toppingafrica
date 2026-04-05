<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->log('Logged out');
        }
    }
}
