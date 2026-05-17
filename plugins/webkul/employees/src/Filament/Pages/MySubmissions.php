<?php

namespace Webkul\Employee\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;

class MySubmissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 10;

    protected string $view = 'employees::filament.pages.my-submissions';

    public ?array $data = [];

    public ?int $viewingSubmissionId = null;

    public ?string $replyBody = null;

    public function mount(): void
    {
        $this->form->fill([
            'type' => 'feedback',
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/pages/my-submissions.navigation.title');
    }

    public function getTitle(): string
    {
        return __('employees::filament/pages/my-submissions.navigation.title');
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('employees::filament/pages/my-submissions.form.section.title'))
                ->description(__('employees::filament/pages/my-submissions.form.section.description'))
                ->schema([
                    Hidden::make('employee_id')
                        ->default(fn () => $this->getAuthEmployee()?->id),
                    Select::make('type')
                        ->label(__('employees::filament/pages/my-submissions.form.fields.type'))
                        ->options([
                            'complaint'  => __('employees::filament/pages/my-submissions.form.fields.complaint'),
                            'suggestion' => __('employees::filament/pages/my-submissions.form.fields.suggestion'),
                            'inquiry'    => __('employees::filament/pages/my-submissions.form.fields.inquiry'),
                            'feedback'   => __('employees::filament/pages/my-submissions.form.fields.feedback'),
                        ])
                        ->required()
                        ->live(),
                    TextInput::make('subject')
                        ->label(__('employees::filament/pages/my-submissions.form.fields.subject'))
                        ->placeholder(__('employees::filament/pages/my-submissions.form.fields.subject-placeholder'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->label(__('employees::filament/pages/my-submissions.form.fields.body'))
                        ->placeholder(__('employees::filament/pages/my-submissions.form.fields.body-placeholder'))
                        ->required()
                        ->rows(6),
                    FileUpload::make('attachments')
                        ->label(__('employees::filament/pages/my-submissions.form.fields.attachments'))
                        ->multiple()
                        ->maxFiles(3)
                        ->maxSize(5120)
                        ->disk('private')
                        ->directory('submissions')
                        ->nullable(),
                ]),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $employee = $this->getAuthEmployee();
        if (! $employee) {
            Notification::make()
                ->danger()
                ->title(__('employees::filament/pages/my-submissions.notifications.no-employee.title'))
                ->body(__('employees::filament/pages/my-submissions.notifications.no-employee.body'))
                ->send();

            return;
        }

        $data['employee_id'] = $employee->id;

        $submission = EmployeeSubmission::create($data);

        Notification::make()
            ->success()
            ->title(__('employees::filament/pages/my-submissions.notifications.submitted.title'))
            ->body(__('employees::filament/pages/my-submissions.notifications.submitted.body', ['ticket' => $submission->ticket_number]))
            ->send();

        $this->form->fill(['type' => 'feedback']);
    }

    #[Computed]
    public function mySubmissions()
    {
        $employee = $this->getAuthEmployee();
        if (! $employee) {
            return collect();
        }

        return EmployeeSubmission::where('employee_id', $employee->id)
            ->withCount('replies')
            ->orderByDesc('created_at')
            ->get();
    }

    public function openSubmission(int $id): void
    {
        $this->viewingSubmissionId = $id;
        $this->replyBody = null;
        $this->dispatch('open-modal', id: 'view-submission-modal');
    }

    public function closeModal(): void
    {
        $this->viewingSubmissionId = null;
        $this->replyBody = null;
        $this->dispatch('close-modal', id: 'view-submission-modal');
    }

    public function getViewingSubmission(): ?EmployeeSubmission
    {
        if (! $this->viewingSubmissionId) {
            return null;
        }

        $employee = $this->getAuthEmployee();
        if (! $employee) {
            return null;
        }

        return EmployeeSubmission::where('id', $this->viewingSubmissionId)
            ->where('employee_id', $employee->id)
            ->with(['replies' => fn ($q) => $q->where('is_internal', false)->with('repliedBy')])
            ->first();
    }

    protected function getAuthEmployee(): ?Employee
    {
        return Employee::where('user_id', Auth::id())->first();
    }
}
