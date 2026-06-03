<?php

namespace App\Providers;

use App\Filament\Correspondence\Pages\ExtendedCorrespondenceDashboard;
use App\Filament\DocumentArchive\Pages\ExtendedDocumentDashboard;
use App\Filament\Meetings\Pages\ExtendedMeetingDashboard;
use App\Filament\Projects\Pages\ExtendedProjectDashboard;
use App\Filament\Recruitment\Pages\ExtendedRecruitmentDashboard;
use App\Filament\TimeOff\Pages\ExtendedTimeOffDashboard;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webkul\Correspondence\Filament\Pages\CorrespondenceDashboard as BaseCorrespondenceDashboard;
use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard as BaseDocumentDashboard;
use Webkul\Meetings\Filament\Pages\MeetingDashboard as BaseMeetingDashboard;
use Webkul\Project\Filament\Pages\Dashboard as BaseProjectDashboard;
use Webkul\Recruitment\Filament\Pages\Recruitments as BaseRecruitmentDashboard;
use Webkul\TimeOff\Filament\Pages\Dashboard as BaseTimeOffDashboard;

class DashboardExtensionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            $this->registerLivewireOverrides();
        });

        Filament::serving(function (): void {
            $this->registerLivewireOverrides();
        });
    }

    protected function registerLivewireOverrides(): void
    {
        $this->override(BaseProjectDashboard::class, ExtendedProjectDashboard::class);
        $this->override(BaseMeetingDashboard::class, ExtendedMeetingDashboard::class);
        $this->override(BaseCorrespondenceDashboard::class, ExtendedCorrespondenceDashboard::class);
        $this->override(BaseDocumentDashboard::class, ExtendedDocumentDashboard::class);
        $this->override(BaseRecruitmentDashboard::class, ExtendedRecruitmentDashboard::class);
        $this->override(BaseTimeOffDashboard::class, ExtendedTimeOffDashboard::class);
    }

    /**
     * @param  class-string  $base
     * @param  class-string  $extended
     */
    protected function override(string $base, string $extended): void
    {
        if (! class_exists($base) || ! class_exists($extended)) {
            return;
        }

        Livewire::component($base, $extended);
    }
}
