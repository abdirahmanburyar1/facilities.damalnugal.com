<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use App\Models\SystemAudit;
use App\Models\User;

class LogUserAuthentication
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event)
    {
        $this->logAuthEvent($event->user, 'login');
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event)
    {
        $this->logAuthEvent($event->user, 'logout');
    }

    /**
     * Helper to log to system audits.
     */
    protected function logAuthEvent($user, $action)
    {
        if (!$user instanceof User) {
            return;
        }

        $metadata = [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if (method_exists($user, 'getAttribute') && $user->getAttribute('facility_id')) {
            $metadata['facility_id'] = $user->getAttribute('facility_id');
        }

        SystemAudit::create([
            'user_id' => $user->id,
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            Login::class,
            [LogUserAuthentication::class, 'handleUserLogin']
        );

        $events->listen(
            Logout::class,
            [LogUserAuthentication::class, 'handleUserLogout']
        );
    }
}
