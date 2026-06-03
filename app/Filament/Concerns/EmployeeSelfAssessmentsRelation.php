<?php

namespace App\Filament\Concerns;

use App\Enums\Hr\SelfAssessmentStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

trait EmployeeSelfAssessmentsRelation
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label(__('hr-extensions::self_assessment.fields.period'))
                    ->state(fn ($record): string => $record->periodLabel()),
                TextColumn::make('status')
                    ->label(__('hr-extensions::self_assessment.fields.status'))
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label(__('hr-extensions::self_assessment.fields.submitted_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('reviewer.name')
                    ->label(__('hr-extensions::self_assessment.fields.reviewed_by'))
                    ->placeholder('—'),
                TextColumn::make('reviewed_at')
                    ->label(__('hr-extensions::self_assessment.fields.reviewed_at'))
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->defaultSort('period_year', 'desc')
            ->emptyStateHeading(__('hr-extensions::self_assessment.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::self_assessment.empty_description'))
            ->recordActions([
                Action::make('review')
                    ->label(__('hr-extensions::self_assessment.actions.review'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn ($record): bool => $record->status === SelfAssessmentStatus::Submitted)
                    ->schema([
                        Hidden::make('reviewed_by')
                            ->default(fn () => Auth::id()),
                        Textarea::make('manager_feedback')
                            ->label(__('hr-extensions::self_assessment.fields.manager_feedback'))
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'manager_feedback' => $data['manager_feedback'],
                            'reviewed_by'      => Auth::id(),
                            'reviewed_at'      => now(),
                            'status'           => SelfAssessmentStatus::Reviewed,
                        ]);

                        if ($record->employee?->user) {
                            Notification::make()
                                ->title(__('hr-extensions::self_assessment.notifications.reviewed_title'))
                                ->body(__('hr-extensions::self_assessment.notifications.reviewed_body', [
                                    'period' => $record->periodLabel(),
                                ]))
                                ->success()
                                ->sendToDatabase($record->employee->user);
                        }

                        Notification::make()
                            ->title(__('hr-extensions::self_assessment.notifications.review_saved'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
