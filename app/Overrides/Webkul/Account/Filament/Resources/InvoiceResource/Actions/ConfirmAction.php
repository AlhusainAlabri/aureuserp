<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Component;
use Throwable;
use Webkul\Account\Enums\AutoPost;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;

class ConfirmAction extends Action
{
    protected bool|Closure $hasDatabaseTransactions = true;

    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounts::filament/resources/invoice/actions/confirm-action.title'))
            ->color('primary')
            ->action(function (Move $record, Component $livewire): void {
                // Save what is on the form first, otherwise confirming checks the stale stored values.
                if ($livewire instanceof EditRecord) {
                    $livewire->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    $record->refresh();
                }

                if (method_exists($record, 'guardApprovalBeforePosting')) {
                    try {
                        $record->guardApprovalBeforePosting();
                    } catch (\RuntimeException $exception) {
                        Notification::make()
                            ->warning()
                            ->title(__('invoices-extensions::messages.confirmation-error'))
                            ->body($exception->getMessage())
                            ->send();

                        $this->halt(shouldRollBackDatabaseTransaction: true);

                        return;
                    }
                }

                $record->checked = $record->journal->auto_check_on_post;

                try {
                    $record = AccountFacade::confirmMove($record);

                    $livewire->refreshFormData(['state', 'parent_state']);
                } catch (Throwable $e) {
                    Notification::make()
                        ->warning()
                        ->title(__('invoices-extensions::messages.confirmation-error'))
                        ->body($e->getMessage())
                        ->send();

                    $this->halt(shouldRollBackDatabaseTransaction: true);
                }
            })
            ->hidden(function (Move $record) {
                return
                    $record->state !== MoveState::DRAFT
                    || (
                        $record->auto_post !== AutoPost::NO
                        && $record->date > now()
                    );
            });
    }
}
