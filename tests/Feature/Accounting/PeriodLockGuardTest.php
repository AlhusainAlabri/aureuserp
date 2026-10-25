<?php

use App\Exceptions\PeriodLockedException;
use App\Support\Accounting\PeriodLock;
use App\Support\Accounting\PeriodLockGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Database\Factories\BankStatementFactory;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Enums\PaymentStatus;
use Webkul\Account\Enums\PaymentType;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Http\Controllers\API\V1\InvoiceController;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\BankStatementLine;
use Webkul\Account\Models\Bill;
use Webkul\Account\Models\CreditNote;
use Webkul\Account\Models\Invoice;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Partner;
use Webkul\Account\Models\Payment;
use Webkul\Account\Models\PaymentMethodLine;
use Webkul\Account\Models\PaymentRegister;
use Webkul\Account\Models\Product;
use Webkul\Account\Models\Refund;
use Webkul\Chatter\Models\Message;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap();

    PeriodLock::forget();

    $this->lockDate = CarbonImmutable::today()->subMonthsNoOverflow(3)->endOfMonth();
    $this->inPeriod = $this->lockDate->subDays(20)->toDateString();
    $this->afterLock = CarbonImmutable::today()->toDateString();

    $this->currency = Currency::query()->first();
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->accountant = User::factory()->create(['default_company_id' => $this->company->id]);

    $this->actingAs($this->accountant);

    $this->partner = Partner::factory()->withAccounts()->create();
    $this->saleJournal = Journal::factory()->sale()->create(['currency_id' => $this->currency->id, 'company_id' => $this->company->id]);
    $this->purchaseJournal = Journal::factory()->purchase()->create(['currency_id' => $this->currency->id, 'company_id' => $this->company->id]);

    $this->bankJournal = $bankJournal = Journal::factory()->bank()->create([
        'currency_id'        => $this->currency->id,
        'company_id'         => $this->company->id,
        'default_account_id' => Account::factory()->create(['account_type' => AccountType::ASSET_CASH])->id,
    ]);

    foreach ([...Journal::getDefaultInboundPaymentMethodLines(), ...Journal::getDefaultOutboundPaymentMethodLines()] as $methodLine) {
        PaymentMethodLine::create($methodLine + ['journal_id' => $bankJournal->id]);
    }
});

afterEach(function (): void {
    PeriodLock::forget();
});

function periodLockGuardLock(?CarbonImmutable $date): void
{
    PeriodLock::setLockDate(test()->company->id, $date, test()->accountant);

    PeriodLock::forget();
}

/**
 * An invoice or bill built and (optionally) posted the way the invoice API builds it.
 */
function periodLockGuardDocument(string $date, MoveType $type = MoveType::OUT_INVOICE, float $price = 100, bool $post = true): Move
{
    $journal = in_array($type, [MoveType::IN_INVOICE, MoveType::IN_REFUND], true) ? test()->purchaseJournal : test()->saleJournal;

    $document = Invoice::create([
        'partner_id'       => test()->partner->id,
        'currency_id'      => test()->currency->id,
        'journal_id'       => $journal->id,
        'date'             => $date,
        'invoice_date'     => $date,
        'invoice_date_due' => $date,
        'move_type'        => $type,
        'state'            => MoveState::DRAFT,
    ]);

    $document->invoiceLines()->create([
        'product_id' => Product::factory()->withAccounts()->create(['is_configurable' => false])->id,
        'quantity'   => 1,
        'uom_id'     => UOM::factory()->create()->id,
        'price_unit' => $price,
    ]);

    $document = AccountFacade::computeAccountMove($document);

    return ($post ? AccountFacade::confirmMove($document) : $document)->refresh();
}

/**
 * Register a payment the way the invoice API and the Filament pay action do.
 */
function periodLockGuardPay(Move $document, string $paymentDate, ?float $amount = null): void
{
    $data = ['payment_date' => $paymentDate] + ($amount !== null ? ['amount' => $amount] : []);

    $paymentData = (fn () => $this->preparePaymentData($document, $data))->call(app(InvoiceController::class));

    $lineIds = $document->paymentTermLines->filter(fn (MoveLine $line): bool => ! $line->reconciled)->pluck('id')->all();

    DB::transaction(function () use ($paymentData, $lineIds): void {
        $paymentRegister = PaymentRegister::create($paymentData);
        $paymentRegister->lines()->sync($lineIds);
        $paymentRegister->refresh();
        $paymentRegister->computeFromLines();
        $paymentRegister->save();

        AccountFacade::createPayments($paymentRegister);
    });
}

function periodLockGuardPostedEntry(string $date, array $attributes = []): Move
{
    return Move::factory()->posted()->create([
        'company_id'  => test()->company->id,
        'currency_id' => test()->currency->id,
        'journal_id'  => test()->saleJournal->id,
        'date'        => $date,
        ...$attributes,
    ]);
}

function periodLockGuardPayment(string $date, PaymentStatus $state): Payment
{
    $methodLine = PaymentMethodLine::query()->where('journal_id', test()->bankJournal->id)->firstOrFail();

    return Payment::create([
        'company_id'             => test()->company->id,
        'currency_id'            => test()->currency->id,
        'journal_id'             => test()->bankJournal->id,
        'partner_id'             => test()->partner->id,
        'payment_method_line_id' => $methodLine->id,
        'payment_type'           => PaymentType::RECEIVE,
        'partner_type'           => 'customer',
        'amount'                 => 25,
        'date'                   => $date,
        'state'                  => $state,
    ]);
}

function periodLockGuardOverrides(Move $move): int
{
    return Message::query()
        ->where('log_name', 'audit')
        ->where('event', 'lock_override')
        ->where('messageable_id', $move->id)
        ->count();
}

function periodLockGuardGrantOverride(User $user): User
{
    Permission::findOrCreate(PeriodLock::OVERRIDE_PERMISSION, 'web');

    $user->givePermissionTo(PeriodLock::OVERRIDE_PERMISSION);

    return $user;
}

dataset('period lock move classes', [
    'Move'                  => [Move::class],
    'accounts Invoice'      => [Invoice::class],
    'accounts Bill'         => [Bill::class],
    'accounts CreditNote'   => [CreditNote::class],
    'accounts Refund'       => [Refund::class],
    'accounting Invoice'    => ['Webkul\Accounting\Models\Invoice'],
    'accounting Bill'       => ['Webkul\Accounting\Models\Bill'],
    'accounting CreditNote' => ['Webkul\Accounting\Models\CreditNote'],
    'accounting Refund'     => ['Webkul\Accounting\Models\Refund'],
    'accounting Entry'      => ['Webkul\Accounting\Models\JournalEntry'],
    'invoices Invoice'      => ['Webkul\Invoice\Models\Invoice'],
    'invoices Bill'         => ['Webkul\Invoice\Models\Bill'],
    'invoices CreditNote'   => ['Webkul\Invoice\Models\CreditNote'],
    'invoices Refund'       => ['Webkul\Invoice\Models\Refund'],
    'purchases AccountMove' => ['Webkul\Purchase\Models\AccountMove'],
]);

it('PeriodLockGuardTest: refuses to edit reset cancel or delete a posted entry in the locked period', function (string $class): void {
    if (! class_exists($class)) {
        $this->markTestSkipped("{$class} is not available.");
    }

    $id = periodLockGuardPostedEntry($this->inPeriod)->id;

    periodLockGuardLock($this->lockDate);

    $load = fn () => $class::query()->withoutGlobalScopes()->findOrFail($id);

    expect($load())->toBeInstanceOf($class);

    expect(fn () => $load()->update(['reference' => 'Edited after closing']))->toThrow(PeriodLockedException::class)
        ->and(fn () => $load()->update(['state' => MoveState::DRAFT]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $load()->update(['state' => MoveState::CANCEL]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $load()->delete())->toThrow(PeriodLockedException::class);

    expect(Move::query()->withoutGlobalScopes()->find($id))
        ->reference->toBeNull()
        ->state->toBe(MoveState::POSTED);
})->with('period lock move classes');

it('PeriodLockGuardTest: refuses to reset or cancel a locked invoice through the account manager', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    expect(fn () => AccountFacade::resetToDraftMove($invoice->refresh()))->toThrow(PeriodLockedException::class)
        ->and(fn () => AccountFacade::cancelMove($invoice->refresh()))->toThrow(PeriodLockedException::class);

    expect($invoice->refresh()->state)->toBe(MoveState::POSTED);
});

it('PeriodLockGuardTest: refuses to change lines of a posted entry in the locked period', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);
    $line = $invoice->invoiceLines()->first();
    $lineCount = $invoice->lines()->count();

    periodLockGuardLock($this->lockDate);

    expect(fn () => $line->refresh()->update(['price_unit' => 250]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $invoice->invoiceLines()->create([
            'product_id' => Product::factory()->withAccounts()->create(['is_configurable' => false])->id,
            'quantity'   => 1,
            'uom_id'     => UOM::factory()->create()->id,
            'price_unit' => 10,
        ]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $line->refresh()->delete())->toThrow(PeriodLockedException::class);

    expect($invoice->lines()->count())->toBe($lineCount)
        ->and((float) $line->refresh()->price_unit)->toBe(100.0);
});

dataset('period lock line classes', [
    'accounts MoveLine'         => [MoveLine::class],
    'accounting MoveLine'       => ['Webkul\Accounting\Models\MoveLine'],
    'accounting JournalItem'    => ['Webkul\Accounting\Models\JournalItem'],
    'purchases AccountMoveLine' => ['Webkul\Purchase\Models\AccountMoveLine'],
]);

it('PeriodLockGuardTest: judges every journal item class by its posted entry', function (string $class): void {
    if (! class_exists($class)) {
        $this->markTestSkipped("{$class} is not available.");
    }

    $invoice = periodLockGuardDocument($this->inPeriod);
    $lineId = $invoice->invoiceLines()->value('id');

    periodLockGuardLock($this->lockDate);

    expect(fn () => $class::query()->findOrFail($lineId)->update(['price_unit' => 300]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $class::query()->findOrFail($lineId)->delete())->toThrow(PeriodLockedException::class);
})->with('period lock line classes');

it('PeriodLockGuardTest: refuses to post or move an entry into the locked period', function (): void {
    $draft = periodLockGuardDocument($this->inPeriod, post: false);
    $later = periodLockGuardDocument($this->afterLock);

    periodLockGuardLock($this->lockDate);

    expect(fn () => AccountFacade::confirmMove($draft->refresh()))->toThrow(PeriodLockedException::class)
        ->and(fn () => $later->refresh()->update(['date' => $this->inPeriod]))->toThrow(PeriodLockedException::class)
        ->and(fn () => periodLockGuardPostedEntry($this->inPeriod))->toThrow(PeriodLockedException::class);

    expect($draft->refresh()->state)->toBe(MoveState::DRAFT)
        ->and($later->refresh()->date->toDateString())->toBe($this->afterLock);
});

it('PeriodLockGuardTest: allows a draft in the locked period but not posting it', function (): void {
    periodLockGuardLock($this->lockDate);

    $draft = periodLockGuardDocument($this->inPeriod, post: false);
    $line = $draft->invoiceLines()->first();

    $line->update(['price_unit' => 120]);
    $draft->refresh()->update(['reference' => 'Still a draft']);

    expect($draft->refresh())
        ->state->toBe(MoveState::DRAFT)
        ->reference->toBe('Still a draft');

    expect(fn () => AccountFacade::confirmMove($draft->refresh()))->toThrow(PeriodLockedException::class);

    $line->refresh()->delete();
    $draft->refresh()->delete();

    expect(Move::query()->withoutGlobalScopes()->find($draft->id))->toBeNull();
});

it('PeriodLockGuardTest: lets a locked-period document be paid by a payment dated after the lock date', function (MoveType $type, ?float $amount, ?PaymentState $expected): void {
    $document = periodLockGuardDocument($this->inPeriod, $type);

    periodLockGuardLock($this->lockDate);

    periodLockGuardPay($document, $this->afterLock, $amount);

    $document->refresh();

    if ($expected) {
        expect($document->payment_state)->toBe($expected);
    }

    expect($document->state)->toBe(MoveState::POSTED)
        ->and($document->date->toDateString())->toBe($this->inPeriod)
        ->and((float) $document->amount_residual)->toBe($amount === null ? 0.0 : 100.0 - $amount);
})->with([
    'customer invoice paid in full' => [MoveType::OUT_INVOICE, null, PaymentState::PAID],
    'vendor bill paid in full'      => [MoveType::IN_INVOICE, null, PaymentState::PAID],
    'customer invoice paid in part' => [MoveType::OUT_INVOICE, 40.0, null],
]);

it('PeriodLockGuardTest: lets an older payment in the locked period settle a new invoice', function (): void {
    $old = periodLockGuardDocument($this->inPeriod);

    periodLockGuardPay($old, $this->inPeriod, 150);

    $payment = Payment::query()->latest('id')->firstOrFail();
    $new = periodLockGuardDocument($this->afterLock, price: 50);

    periodLockGuardLock($this->lockDate);

    // Somebody else reconciles it; the payment model stamps its creator again on every save.
    $this->actingAs(User::factory()->create(['default_company_id' => $this->company->id]));

    $outstanding = $payment->move->lines()
        ->where('reconciled', false)
        ->where('account_id', $new->paymentTermLines->first()->account_id)
        ->get();

    AccountFacade::reconcile($new->paymentTermLines->merge($outstanding));

    expect($new->refresh()->payment_state)->toBe(PaymentState::PAID)
        ->and($payment->refresh()->is_reconciled)->toBeTruthy()
        ->and($payment->date->toDateString())->toBe($this->inPeriod);
});

it('PeriodLockGuardTest: still blocks changing a paid locked invoice amount date or payments', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    periodLockGuardPay($invoice, $this->afterLock);

    expect(fn () => $invoice->refresh()->update(['amount_total' => 500]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $invoice->refresh()->update(['date' => $this->afterLock]))->toThrow(PeriodLockedException::class)
        ->and(fn () => DB::transaction(fn () => AccountFacade::resetToDraftMove($invoice->refresh())))->toThrow(PeriodLockedException::class);

    expect($invoice->refresh())
        ->payment_state->toBe(PaymentState::PAID)
        ->state->toBe(MoveState::POSTED)
        ->and((float) $invoice->amount_total)->toBe(100.0);
});

it('PeriodLockGuardTest: treats recomputed values that did not change as unchanged', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);
    $line = $invoice->invoiceLines()->first();

    periodLockGuardLock($this->lockDate);

    $invoice->refresh();
    $invoice->invoice_currency_rate = 1;
    $invoice->amount_total = (float) $invoice->amount_total;
    $invoice->state = MoveState::POSTED;
    $invoice->date = CarbonImmutable::parse($this->inPeriod);
    $invoice->save();

    $line->refresh();
    $line->tax_tag_invert = false;
    $line->debit = (float) $line->debit;
    $line->price_unit = '100.00';
    $line->save();

    $invoice->amount_total = (float) $invoice->amount_total + 0.01;

    expect(fn () => $invoice->save())->toThrow(PeriodLockedException::class);
});

it('PeriodLockGuardTest: refuses payments dated in the period', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);
    $posted = periodLockGuardPayment($this->inPeriod, PaymentStatus::IN_PROCESS);

    periodLockGuardLock($this->lockDate);

    $payments = Payment::query()->count();

    expect(fn () => periodLockGuardPay($invoice->refresh(), $this->inPeriod))->toThrow(PeriodLockedException::class);

    expect(Payment::query()->count())->toBe($payments)
        ->and($invoice->refresh()->payment_state)->toBe(PaymentState::NOT_PAID);

    expect(fn () => $posted->refresh()->update(['memo' => 'Changed']))->toThrow(PeriodLockedException::class)
        ->and(fn () => $posted->refresh()->update(['state' => PaymentStatus::DRAFT]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $posted->refresh()->delete())->toThrow(PeriodLockedException::class)
        ->and(fn () => periodLockGuardPayment($this->inPeriod, PaymentStatus::PAID))->toThrow(PeriodLockedException::class);

    $draft = periodLockGuardPayment($this->inPeriod, PaymentStatus::DRAFT);

    expect($draft->exists)->toBeTrue();
});

dataset('period lock payment classes', [
    'accounts Payment'   => [Payment::class],
    'accounting Payment' => ['Webkul\Accounting\Models\Payment'],
    'invoices Payment'   => ['Webkul\Invoice\Models\Payment'],
]);

it('PeriodLockGuardTest: judges every payment class by its own date and state', function (string $class): void {
    if (! class_exists($class)) {
        $this->markTestSkipped("{$class} is not available.");
    }

    $id = periodLockGuardPayment($this->inPeriod, PaymentStatus::IN_PROCESS)->id;

    periodLockGuardLock($this->lockDate);

    expect(fn () => $class::query()->findOrFail($id)->update(['memo' => 'Changed']))->toThrow(PeriodLockedException::class);
})->with('period lock payment classes');

it('PeriodLockGuardTest: refuses bank statements dated in the period', function (): void {
    $locked = BankStatementFactory::new()->create(['company_id' => $this->company->id, 'date' => $this->inPeriod]);
    $later = BankStatementFactory::new()->create(['company_id' => $this->company->id, 'date' => $this->afterLock]);

    periodLockGuardLock($this->lockDate);

    expect(fn () => BankStatementFactory::new()->create(['company_id' => $this->company->id, 'date' => $this->inPeriod]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $locked->refresh()->update(['name' => 'Changed']))->toThrow(PeriodLockedException::class)
        ->and(fn () => $later->refresh()->update(['date' => $this->inPeriod]))->toThrow(PeriodLockedException::class)
        ->and(fn () => $locked->refresh()->delete())->toThrow(PeriodLockedException::class)
        ->and(fn () => (new BankStatementLine)->forceFill(['statement_id' => $locked->id, 'company_id' => $this->company->id, 'amount' => 5])->save())
        ->toThrow(PeriodLockedException::class);

    $later->refresh()->update(['name' => 'Fine']);

    expect($later->refresh()->name)->toBe('Fine');

    PeriodLockGuard::check((new BankStatementLine)->forceFill(['statement_id' => $later->id, 'company_id' => $this->company->id]), 'create');
});

it('PeriodLockGuardTest: lets a user holding post_in_locked_period change a locked entry and writes one lock_override entry', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    $manager = periodLockGuardGrantOverride(User::factory()->create(['default_company_id' => $this->company->id]));

    $this->actingAs($manager);

    $invoice->refresh()->update(['reference' => 'Corrected by the finance manager']);
    $invoice->invoiceLines()->first()->update(['price_unit' => 110]);
    $invoice->refresh()->update(['reference' => 'Corrected twice']);

    expect($invoice->refresh()->reference)->toBe('Corrected twice')
        ->and(periodLockGuardOverrides($invoice))->toBe(1);

    $entry = Message::query()->where('event', 'lock_override')->where('messageable_id', $invoice->id)->firstOrFail();

    expect((int) $entry->causer_id)->toBe($manager->id)
        ->and($entry->properties[PeriodLock::COLUMN]['new_value'])->toBe($this->lockDate->toDateString())
        ->and($entry->properties['operation']['new_value'])->toBe('update')
        ->and($entry->properties['reference']['new_value'])->toBe('Corrected by the finance manager');

    $created = periodLockGuardPostedEntry($this->inPeriod);

    expect(periodLockGuardOverrides($created))->toBe(1);

    $created->delete();

    expect(Message::query()->where('event', 'lock_override')->where('messageable_id', $created->id)->pluck('properties')->pluck('operation.new_value')->sort()->values()->all())
        ->toBe(['create', 'delete']);
});

it('PeriodLockGuardTest: still blocks when nobody is signed in', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    $manager = periodLockGuardGrantOverride(User::factory()->create());

    app('auth')->forgetGuards();

    expect(auth()->user())->toBeNull()
        ->and(fn () => $invoice->refresh()->update(['reference' => 'From a queued job']))->toThrow(PeriodLockedException::class)
        ->and(periodLockGuardOverrides($invoice))->toBe(0)
        ->and($manager->can(PeriodLock::OVERRIDE_PERMISSION))->toBeTrue();
});

it('PeriodLockGuardTest: never locks when the lock date is null or the record has no company', function (): void {
    $invoice = periodLockGuardDocument($this->inPeriod);
    $statement = BankStatementFactory::new()->create(['company_id' => $this->company->id, 'date' => $this->inPeriod]);

    $invoice->refresh()->update(['reference' => 'No lock date yet']);
    $statement->refresh()->update(['name' => 'No lock date yet']);
    $invoice->invoiceLines()->first()->update(['price_unit' => 90]);

    periodLockGuardLock($this->lockDate);

    $orphan = BankStatementFactory::new()->create(['company_id' => null, 'date' => $this->inPeriod]);
    $orphan->update(['name' => 'Still editable']);

    PeriodLockGuard::check((new Move)->forceFill(['date' => $this->inPeriod, 'state' => MoveState::POSTED->value]), 'create');
    PeriodLockGuard::check((new MoveLine)->forceFill(['move_id' => null]), 'create');

    periodLockGuardLock(null);

    $invoice->refresh()->update(['reference' => 'Unlocked again']);

    expect($invoice->refresh()->reference)->toBe('Unlocked again')
        ->and($orphan->refresh()->name)->toBe('Still editable');
});

it('PeriodLockGuardTest: leaves entries after the lock date untouched', function (): void {
    $invoice = periodLockGuardDocument($this->afterLock);
    $entry = periodLockGuardPostedEntry($this->afterLock);

    periodLockGuardLock($this->lockDate);

    $invoice->refresh()->update(['reference' => 'After the lock date']);
    $invoice->invoiceLines()->first()->update(['price_unit' => 80]);

    $entry->refresh()->update(['state' => MoveState::DRAFT]);
    $entry->refresh()->delete();

    $posted = periodLockGuardPostedEntry($this->afterLock);

    expect($invoice->refresh()->reference)->toBe('After the lock date')
        ->and($posted->exists)->toBeTrue()
        ->and(Message::query()->where('event', 'lock_override')->count())->toBe(0);
});

class PeriodLockGuardProbe extends Component
{
    public int $moveId;

    public function editLockedEntry(): void
    {
        Move::query()->withoutGlobalScopes()->findOrFail($this->moveId)->update(['reference' => 'From the form']);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

it('PeriodLockGuardTest: shows a refusal in a Livewire action as a danger notification instead of an error', function (): void {
    $entry = periodLockGuardPostedEntry($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    Livewire::test(PeriodLockGuardProbe::class, ['moveId' => $entry->id])
        ->call('editLockedEntry')
        ->assertOk()
        ->assertNotified(__('audit.lock.refused_title'));

    expect($entry->refresh()->reference)->toBeNull();
});

it('PeriodLockGuardTest: answers a JSON request refused by the lock with a 422 and the message', function (): void {
    $entry = periodLockGuardPostedEntry($this->inPeriod);

    periodLockGuardLock($this->lockDate);

    Route::post('/_period-lock-guard-probe/{move}', function (int $move) {
        Move::query()->withoutGlobalScopes()->findOrFail($move)->update(['reference' => 'From the API']);

        return response()->json(['ok' => true]);
    });

    $this->postJson("/_period-lock-guard-probe/{$entry->id}")
        ->assertStatus(422)
        ->assertJson(['message' => __('audit.lock.refused', ['date' => $this->lockDate->toDateString()])]);
});
