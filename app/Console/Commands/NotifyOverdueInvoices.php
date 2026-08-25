<?php

namespace App\Console\Commands;

use App\Mail\Accounting\OverdueInvoicesMail;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDedupService;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Filament\Actions\Action;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Models\Move;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Security\Models\User;

class NotifyOverdueInvoices extends Command
{
    protected $signature = 'accounting:notify-overdue-invoices';

    protected $description = 'Notify finance managers about overdue customer invoices';

    public function handle(
        NotificationDispatcher $notifications,
        NotificationDedupService $dedup,
    ): int {
        if (! Schema::hasTable('accounts_account_moves')) {
            $this->warn('Accounts moves table not found.');

            return self::SUCCESS;
        }

        $invoices = Move::query()
            ->where('move_type', MoveType::OUT_INVOICE)
            ->where('state', MoveState::POSTED)
            ->where('invoice_date_due', '<', today())
            ->where('amount_residual', '>', 0)
            ->whereIn('payment_state', [PaymentState::NOT_PAID, PaymentState::PARTIAL])
            ->with('partner')
            ->orderBy('invoice_date_due')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices found.');

            return self::SUCCESS;
        }

        $managers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                'super_admin',
                'general_manager',
                'finance_manager',
                'Admin',
            ]))
            ->whereNotNull('email')
            ->get();

        if ($managers->isEmpty()) {
            $managers = User::query()->whereNotNull('email')->limit(5)->get();
        }

        $invoicesUrl = InvoiceResource::getUrl('index', ['activeTableView' => 'overdue']);
        $todayKey = today()->toDateString();
        $notified = 0;

        foreach ($managers as $manager) {
            $dedupKey = "accounting.invoice_overdue:{$todayKey}:user:{$manager->id}";

            $sent = $dedup->rememberIfNotSent($dedupKey, function () use (
                $notifications,
                $manager,
                $invoices,
                $invoicesUrl,
            ): void {
                $notifications->dispatch(
                    NotificationDefinition::make('accounting.invoice_overdue')
                        ->recipients([$manager])
                        ->title(__('accounting-extensions::notifications.overdue_invoices.title', [
                            'count' => $invoices->count(),
                        ]))
                        ->body(__('accounting-extensions::notifications.overdue_invoices.body', [
                            'count' => $invoices->count(),
                        ]))
                        ->icon('heroicon-o-banknotes')
                        ->color('danger')
                        ->actions([
                            Action::make('view')
                                ->label(__('accounting-extensions::notifications.overdue_invoices.action'))
                                ->url($invoicesUrl),
                        ])
                        ->channels([NotificationChannel::Database, NotificationChannel::Mail])
                        ->mail(fn () => new OverdueInvoicesMail($manager, $invoices, $invoicesUrl))
                        ->build(),
                );
            });

            if ($sent) {
                $notified++;
            }
        }

        $this->info("Notified {$notified} finance user(s) about {$invoices->count()} overdue invoice(s).");

        return self::SUCCESS;
    }
}
