<?php

namespace App\Filament\Pages;

use App\Enums\Hr\SelfAssessmentStatus;
use App\Models\Hr\EmployeeSelfAssessment;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Employee;

class MySelfAssessment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'my-self-assessment';

    protected string $view = 'filament.pages.my-self-assessment';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'period_year'  => now()->year,
            'period_month' => now()->month,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('hr-extensions::self_assessment.form.section'))
                    ->schema([
                        Hidden::make('employee_id')
                            ->default(fn (): ?int => $this->getAuthEmployee()?->id),
                        Grid::make(2)
                            ->schema([
                                Select::make('period_year')
                                    ->label(__('hr-extensions::self_assessment.fields.period_year'))
                                    ->options(fn (): array => collect(range(now()->year - 1, now()->year))
                                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                        ->all())
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),
                                Select::make('period_month')
                                    ->label(__('hr-extensions::self_assessment.fields.period_month'))
                                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $month): array => [
                                        $month => __('hr-extensions::self_assessment.months.'.$month),
                                    ])->all())
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),
                            ]),
                        Textarea::make('employee_comments')
                            ->label(__('hr-extensions::self_assessment.fields.employee_comments'))
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label(__('hr-extensions::self_assessment.fields.attachment'))
                            ->directory(fn (): string => 'employees/'.($this->getAuthEmployee()?->id ?? 'unknown').'/self-assessments')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::self_assessment.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Employee::query()->where('user_id', Auth::id())->exists();
    }

    #[Computed]
    public function assessments()
    {
        $employee = $this->getAuthEmployee();

        if (! $employee) {
            return collect();
        }

        return EmployeeSelfAssessment::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();
    }

    public function submit(): void
    {
        $employee = $this->getAuthEmployee();

        if (! $employee) {
            Notification::make()
                ->danger()
                ->title(__('hr-extensions::self_assessment.notifications.no_employee'))
                ->send();

            return;
        }

        $data = $this->form->getState();
        $data['employee_id'] = $employee->id;
        $data['status'] = SelfAssessmentStatus::Submitted;
        $data['submitted_at'] = now();

        EmployeeSelfAssessment::query()->updateOrCreate(
            [
                'employee_id'  => $employee->id,
                'period_year'  => $data['period_year'],
                'period_month' => $data['period_month'],
            ],
            $data,
        );

        Notification::make()
            ->success()
            ->title(__('hr-extensions::self_assessment.notifications.submitted'))
            ->send();

        $this->form->fill([
            'period_year'        => now()->year,
            'period_month'       => now()->month,
            'employee_comments'  => null,
            'attachment_path'    => null,
        ]);
    }

    protected function getAuthEmployee(): ?Employee
    {
        return Employee::query()->where('user_id', Auth::id())->first();
    }
}
