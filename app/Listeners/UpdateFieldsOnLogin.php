<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateFieldsOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $request = request();

        $user::withoutTimestamps(function () use ($user, $request) {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
        });
    }
}
