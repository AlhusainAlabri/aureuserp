<?php

namespace Webkul\Employee\Filament\Resources\SubmissionResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Models\EmployeeSubmission;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all'          => Tab::make(__('employees::filament/resources/submission.tabs.all'))
                ->badge(EmployeeSubmission::count()),
            'open'         => Tab::make(__('employees::filament/resources/submission.tabs.open'))
                ->badge(EmployeeSubmission::open()->count())
                ->modifyQueryUsing(fn ($query) => $query->open()),
            'under_review' => Tab::make(__('employees::filament/resources/submission.tabs.under_review'))
                ->badge(EmployeeSubmission::underReview()->count())
                ->modifyQueryUsing(fn ($query) => $query->underReview()),
            'resolved'     => Tab::make(__('employees::filament/resources/submission.tabs.resolved'))
                ->badge(EmployeeSubmission::resolved()->count())
                ->modifyQueryUsing(fn ($query) => $query->resolved()),
            'closed'       => Tab::make(__('employees::filament/resources/submission.tabs.closed'))
                ->badge(EmployeeSubmission::where('status', 'closed')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'closed')),
        ];
    }
}
