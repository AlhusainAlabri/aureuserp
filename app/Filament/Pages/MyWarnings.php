<?php

namespace App\Filament\Pages;

use App\Filament\Assets\Support\SignatureField;
use App\Mail\EmployeeWarningAcknowledgedMail;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Security\Models\User;

class MyWarnings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.my-warnings';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::warnings.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Employee::query()->where('user_id', Auth::id())->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EmployeeWarning::query()
                    ->where('employee_id', $this->getAuthEmployee()?->id)
                    ->with(['warningType'])
                    ->orderByDesc('issued_at'),
            )
            ->columns([
                TextColumn::make('warningType.name')
                    ->label(__('hr-extensions::warnings.fields.type')),
                TextColumn::make('subject')
                    ->label(__('hr-extensions::warnings.fields.subject'))
                    ->searchable(),
                TextColumn::make('issued_at')
                    ->label(__('hr-extensions::warnings.fields.issued_at'))
                    ->date(),
                IconColumn::make('employee_acknowledged_at')
                    ->label(__('hr-extensions::warnings.fields.acknowledged'))
                    ->boolean()
                    ->getStateUsing(fn (EmployeeWarning $record): bool => filled($record->employee_acknowledged_at)),
            ])
            ->recordActions([
                Action::make('acknowledge')
                    ->label(__('hr-extensions::warnings.actions.acknowledge'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (EmployeeWarning $record): bool => blank($record->employee_acknowledged_at))
                    ->schema([
                        SignatureField::make('acknowledgment_signature'),
                        FileUpload::make('signed_document_path')
                            ->label(__('hr-extensions::warnings.fields.signed_document'))
                            ->directory(fn (): string => 'employees/'.($this->getAuthEmployee()?->id ?? 'unknown').'/warnings/signed')
                            ->visibility('private')
                            ->nullable(),
                        Textarea::make('notes')
                            ->label(__('hr-extensions::warnings.fields.notes'))
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (EmployeeWarning $record, array $data): void {
                        $record->forceFill([
                            'acknowledgment_signature'   => $data['acknowledgment_signature'],
                            'signed_document_path'       => $data['signed_document_path'] ?? null,
                            'employee_acknowledged_at'   => now(),
                            'is_acknowledged'            => true,
                            'acknowledged_at'            => now(),
                            'acknowledged_by'            => Auth::id(),
                        ])->save();

                        $freshWarning = $record->fresh(['employee', 'warningType']);

                        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', 'hr_manager'))->get() as $hrManager) {
                            Mail::to($hrManager)->queue(new EmployeeWarningAcknowledgedMail($freshWarning));
                        }

                        Notification::make()
                            ->success()
                            ->title(__('hr-extensions::warnings.notifications.acknowledged'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('hr-extensions::warnings.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::warnings.empty_description'));
    }

    protected function getAuthEmployee(): ?Employee
    {
        return Employee::query()->where('user_id', Auth::id())->first();
    }
}
