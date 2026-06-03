<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;

class MyEmployeeSubmissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'employee-submissions';

    protected string $view = 'employees::filament.pages.my-submissions';

    public ?array $data = [];

    public ?int $viewingSubmissionId = null;

    public ?string $replyBody = null;

    public function mount(): void
    {
        $this->form->fill([
            'type'          => 'feedback',
            'is_anonymous'  => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('employees::filament/pages/my-submissions.form.section.title'))
                    ->description(__('employees::filament/pages/my-submissions.form.section.description'))
                    ->schema([
                        Hidden::make('employee_id')
                            ->default(fn (): ?int => $this->getAuthEmployee()?->id),
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
                            ->required()
                            ->maxLength(255),
                        Textarea::make('body')
                            ->label(__('employees::filament/pages/my-submissions.form.fields.body'))
                            ->required()
                            ->rows(6),
                        Toggle::make('is_anonymous')
                            ->label(__('hr-extensions::submissions.anonymous_toggle'))
                            ->helperText(__('hr-extensions::submissions.anonymous_hint'))
                            ->default(false),
                        FileUpload::make('attachments')
                            ->label(__('employees::filament/pages/my-submissions.form.fields.attachments'))
                            ->multiple()
                            ->maxFiles(3)
                            ->maxSize(5120)
                            ->disk('local')
                            ->directory('submissions')
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/pages/my-submissions.navigation.title');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return Auth::check();
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

        $isAnonymous = (bool) ($data['is_anonymous'] ?? false);
        unset($data['is_anonymous']);
        $data['employee_id'] = $employee->id;

        $submission = new EmployeeSubmission($data);
        $submission->forceFill([
            'is_anonymous'   => $isAnonymous,
            'submitter_name' => $isAnonymous
                ? __('hr-extensions::submissions.anonymous_label')
                : $employee->name,
        ]);
        $submission->save();

        Notification::make()
            ->success()
            ->title(__('employees::filament/pages/my-submissions.notifications.submitted.title'))
            ->body(__('employees::filament/pages/my-submissions.notifications.submitted.body', ['ticket' => $submission->ticket_number]))
            ->send();

        $this->form->fill(['type' => 'feedback', 'is_anonymous' => false]);
    }

    #[Computed]
    public function mySubmissions()
    {
        $employee = $this->getAuthEmployee();
        if (! $employee) {
            return collect();
        }

        return EmployeeSubmission::query()
            ->where('employee_id', $employee->id)
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

        return EmployeeSubmission::query()
            ->where('id', $this->viewingSubmissionId)
            ->where('employee_id', $employee->id)
            ->with(['replies' => fn ($q) => $q->where('is_internal', false)->with('repliedBy')])
            ->first();
    }

    protected function getAuthEmployee(): ?Employee
    {
        return Employee::query()->where('user_id', Auth::id())->first();
    }
}
