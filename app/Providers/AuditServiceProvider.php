<?php

namespace App\Providers;

use App\Support\Audit\AuditRecorder;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Registering model listeners does not read the database, and the recorder checks that the Chatter table exists
     * before it writes, so booting is safe before the plugins are installed.
     */
    public function boot(): void
    {
        AuditRecorder::register();
    }
}
