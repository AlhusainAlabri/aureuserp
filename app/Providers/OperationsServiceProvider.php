<?php

namespace App\Providers;

use App\Models\Operations\SystemBackup;
use App\Policies\SystemBackupPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class OperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(SystemBackup::class, SystemBackupPolicy::class);
    }
}
