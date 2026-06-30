<?php

namespace App\Support\Dashboard;

use App\Filament\Projects\Pages\TaskOperationsHub;
use App\Support\FilamentUrl;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\PluginManager\Package;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\TaskResource;

class DashboardNavigation
{
    public static function meetingApprovalsUrl(): ?string
    {
        if (! self::meetingsAreAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']),
        );
    }

    public static function correspondenceApprovalsUrl(): ?string
    {
        if (! self::correspondenceIsAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            CorrespondenceResource::getUrl('index'),
        );
    }

    public static function meetingsIndexUrl(): ?string
    {
        if (! self::meetingsAreAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            MeetingResource::getUrl('index'),
        );
    }

    public static function correspondenceIndexUrl(): ?string
    {
        if (! self::correspondenceIsAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            CorrespondenceResource::getUrl('index'),
        );
    }

    public static function taskOperationsHubUrl(): ?string
    {
        if (! Schema::hasTable('projects_tasks') || ! TaskOperationsHub::canAccess()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(TaskOperationsHub::getUrl());
    }

    public static function projectTasksUrl(): ?string
    {
        if (! class_exists(TaskResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            TaskResource::getUrl('index'),
        );
    }

    public static function activeProjectsUrl(): ?string
    {
        if (! class_exists(ProjectResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            ProjectResource::getUrl('index'),
        );
    }

    public static function expiringDocumentsUrl(): ?string
    {
        if (! class_exists(EmployeeResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            EmployeeResource::getUrl('index'),
        );
    }

    protected static function correspondenceIsAvailable(): bool
    {
        return class_exists(CorrespondenceResource::class)
            && Schema::hasTable('correspondences')
            && Package::isPluginInstalled('correspondence');
    }

    protected static function meetingsAreAvailable(): bool
    {
        return class_exists(MeetingResource::class)
            && Schema::hasTable('meetings')
            && Package::isPluginInstalled('meetings');
    }
}
