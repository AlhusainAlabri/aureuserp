<?php

use App\Mail\Assets\BorrowingDueReminderMail;
use App\Mail\Assets\BorrowingOverdueMail;
use App\Mail\Assets\BorrowingRequestApprovedMail;
use App\Mail\Assets\BorrowingRequestRejectedMail;
use App\Mail\Assets\BorrowingRequestSubmittedMail;
use App\Mail\Assets\BorrowingReturnedMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

function assetBorrowingForMail(): AssetBorrowing
{
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create();

    return AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(7),
    ])->load(['asset', 'employee']);
}

it('builds asset borrowing mailable envelopes with translated subjects', function (): void {
    $borrowing = assetBorrowingForMail();
    $recipient = User::factory()->create();

    expect((new BorrowingRequestSubmittedMail($borrowing, $recipient))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.submitted.subject', ['asset' => $borrowing->asset?->name ?? '—']))
        ->and((new BorrowingRequestApprovedMail($borrowing))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.approved.subject', ['asset' => $borrowing->asset?->name ?? '—']))
        ->and((new BorrowingRequestRejectedMail($borrowing))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.rejected.subject', ['asset' => $borrowing->asset?->name ?? '—']))
        ->and((new BorrowingDueReminderMail($borrowing, $recipient))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.due_reminder.subject', ['asset' => $borrowing->asset?->name ?? '—']))
        ->and((new BorrowingOverdueMail($borrowing, $recipient))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.overdue.subject', ['asset' => $borrowing->asset?->name ?? '—']))
        ->and((new BorrowingReturnedMail($borrowing, $recipient))->envelope()->subject)
        ->toBe(__('assets-extensions::mail.returned.subject', ['asset' => $borrowing->asset?->name ?? '—']));
});

it('renders asset borrowing markdown mail views', function (): void {
    $borrowing = assetBorrowingForMail();
    $recipient = User::factory()->create();

    $html = (new BorrowingRequestSubmittedMail($borrowing, $recipient))->render();

    expect($html)
        ->toContain(__('assets-extensions::mail.submitted.heading'))
        ->toContain($borrowing->asset?->name ?? '—');
});
