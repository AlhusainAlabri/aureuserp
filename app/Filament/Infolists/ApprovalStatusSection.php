<?php

namespace App\Filament\Infolists;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Wezlo\FilamentApproval\FilamentApprovalPlugin;

/**
 * Filament v5-compatible approval status section.
 *
 * The package section uses relationship dot-notation (latestApproval.status) but
 * latestApproval() is a helper method, not an Eloquent relationship — which
 * triggers getRelated() errors once a record has been submitted for approval.
 */
class ApprovalStatusSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-approval::approval.infolist.approval_status'))
            ->schema([
                TextEntry::make('approval_status_display')
                    ->label(__('filament-approval::approval.infolist.status'))
                    ->state(fn (Model $record): mixed => $record->latestApproval()?->status)
                    ->badge()
                    ->placeholder(__('filament-approval::approval.infolist.not_submitted'))
                    ->columnSpan(1),
                TextEntry::make('approval_flow_display')
                    ->label(__('filament-approval::approval.infolist.flow'))
                    ->state(fn (Model $record): ?string => $record->latestApproval()?->flow?->name)
                    ->placeholder('-')
                    ->columnSpan(1),
                TextEntry::make('approval_submitter_display')
                    ->label(__('filament-approval::approval.infolist.submitted_by'))
                    ->state(fn (Model $record): ?string => $record->latestApproval()?->submitter?->name)
                    ->placeholder('-')
                    ->columnSpan(1),
                TextEntry::make('approval_submitted_at_display')
                    ->label(__('filament-approval::approval.infolist.submitted'))
                    ->state(fn (Model $record): mixed => $record->latestApproval()?->submitted_at)
                    ->dateTime()
                    ->placeholder('-')
                    ->columnSpan(1),
                TextEntry::make('approval_completed_at_display')
                    ->label(__('filament-approval::approval.infolist.completed'))
                    ->state(fn (Model $record): mixed => $record->latestApproval()?->completed_at)
                    ->dateTime()
                    ->placeholder(__('filament-approval::approval.infolist.in_progress'))
                    ->columnSpan(1),

                Section::make(__('filament-approval::approval.infolist.current_step'))
                    ->schema([
                        TextEntry::make('approval_step_name_display')
                            ->label(__('filament-approval::approval.infolist.step'))
                            ->state(fn (Model $record): ?string => $record->currentApproval()?->currentStepInstance()?->step?->name)
                            ->placeholder('N/A'),
                        TextEntry::make('approval_step_status_display')
                            ->label(__('filament-approval::approval.infolist.status'))
                            ->state(fn (Model $record): mixed => $record->currentApproval()?->currentStepInstance()?->status)
                            ->badge()
                            ->placeholder('N/A'),
                        TextEntry::make('pending_approvers_display')
                            ->label(__('filament-approval::approval.infolist.pending_approvers'))
                            ->state(function (Model $record): string {
                                $ids = $record->currentApproval()?->currentStepInstance()?->assigned_approver_ids;

                                if (empty($ids)) {
                                    return '-';
                                }

                                $userModel = FilamentApprovalPlugin::resolveUserModel();

                                return $userModel::whereIn('id', $ids)
                                    ->pluck('name')
                                    ->join(', ') ?: '-';
                            }),
                        TextEntry::make('approval_progress_display')
                            ->label(__('filament-approval::approval.infolist.progress'))
                            ->state(function (Model $record): string {
                                $stepInstance = $record->currentApproval()?->currentStepInstance();

                                if (! $stepInstance) {
                                    return '-';
                                }

                                return __('filament-approval::approval.infolist.approvals_count', [
                                    'received' => $stepInstance->received_approvals,
                                    'required' => $stepInstance->required_approvals,
                                ]);
                            })
                            ->placeholder('-'),
                        TextEntry::make('approval_sla_deadline_display')
                            ->label(__('filament-approval::approval.infolist.sla_deadline'))
                            ->state(fn (Model $record): mixed => $record->currentApproval()?->currentStepInstance()?->sla_deadline)
                            ->dateTime()
                            ->placeholder(__('filament-approval::approval.infolist.no_sla'))
                            ->color(function (Model $record): ?string {
                                $deadline = $record->currentApproval()?->currentStepInstance()?->sla_deadline;

                                if (! $deadline) {
                                    return null;
                                }

                                return $deadline->isPast() ? 'danger' : null;
                            }),
                    ])
                    ->columns(3)
                    ->visible(fn (Model $record): bool => $record->currentApproval()?->currentStepInstance() !== null),

                Section::make(__('filament-approval::approval.infolist.recent_activity'))
                    ->schema([
                        RepeatableEntry::make('approval_actions_display')
                            ->label('')
                            ->state(fn (Model $record): array => $record->latestApproval()?->actions()->latest()->limit(10)->get()->all() ?? [])
                            ->schema([
                                TextEntry::make('type')
                                    ->label(__('filament-approval::approval.fields.type'))
                                    ->badge(),
                                TextEntry::make('user.name')
                                    ->label(__('filament-approval::approval.infolist.by'))
                                    ->placeholder(__('filament-approval::approval.infolist.system')),
                                TextEntry::make('comment')
                                    ->label(__('filament-approval::approval.fields.comment'))
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label(__('filament-approval::approval.infolist.date'))
                                    ->since(),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->visible(fn (Model $record): bool => $record->latestApproval()?->actions()->exists() ?? false),
            ])
            ->columns(3)
            ->collapsible()
            ->visible(fn (Model $record): bool => method_exists($record, 'approvals') && $record->latestApproval() !== null);
    }
}
