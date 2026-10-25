<?php

use App\Services\Hr\HrExtensionSchemaService;
use App\Support\Pdf\Documents\DocumentMoney;
use App\Support\Pdf\Documents\EndOfServiceSettlementDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\GratuityFixtures;
use Tests\Helpers\PasiFixtures;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\ViewEndOfServiceSettlement;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Services\FinalSettlementBuilder;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(HrExtensionSchemaService::class)->ensure();
    GratuityFixtures::tiers();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app()->setLocale('ar');
});

function pdfSettlement(string $nationality = 'IN', float $loan = 0): EndOfServiceSettlement
{
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), $nationality, '2020-01-01', '2025-01-16');

    if ($loan > 0) {
        Loan::query()->create([
            'reference_number' => 'LOAN-T-'.uniqid(), 'employee_id' => $employee->id, 'loan_type' => 'salary_advance',
            'total_amount'     => $loan + 100, 'installment_count' => 3, 'start_period_year' => 2024, 'start_period_month' => 10,
            'status'           => 'active', 'amount_repaid' => 100, 'amount_remaining' => $loan, 'company_id' => $employee->company_id,
        ]);
    }

    return app(FinalSettlementBuilder::class)->build($employee);
}

it('GratuityPdfTest: the document lists the tier rows and ends with the net total', function (): void {
    $settlement = pdfSettlement();
    $document = EndOfServiceSettlementDocument::make($settlement);
    $money = new DocumentMoney($settlement->company?->currency);

    expect($document)->toHaveKeys(['company', 'title', 'partyLabel', 'party', 'meta', 'columns', 'rows', 'totals', 'notes'])
        ->and($document['title'])->toContain($settlement->employee->name)
        ->and($document['rows'])->toHaveCount(2)
        ->and($document['rows'][0]['amount'])->toBe($money->number(1350))
        ->and(collect($document['totals'])->last())->toBe([
            __('payroll::payroll.settlement.fields.net_amount'),
            $money->format($settlement->net_amount),
            'grand',
        ]);
});

it('GratuityPdfTest: renders a real Arabic PDF', function (): void {
    $output = Pdf::loadView('pdf.trade-document', ['docs' => [EndOfServiceSettlementDocument::make(pdfSettlement())]])->output();

    expect($output)->toStartWith('%PDF');
});

it('GratuityPdfTest: an ineligible settlement prints the reason instead of tier rows', function (): void {
    $document = EndOfServiceSettlementDocument::make(pdfSettlement('OM'));

    expect($document['rows'])->toBe([])
        ->and(collect($document['notes'])->pluck('text')->implode(' '))->toContain(__('payroll::payroll.gratuity.reasons.not_eligible_category'));

    expect(Pdf::loadView('pdf.trade-document', ['docs' => [$document]])->output())->toStartWith('%PDF');
});

it('GratuityPdfTest: a negative net and the adjustment note render', function (): void {
    $settlement = pdfSettlement('IN', loan: 90000);
    $settlement->update(['other_adjustment' => -25, 'adjustment_note' => 'ملاحظة اختبار']);
    $document = EndOfServiceSettlementDocument::make($settlement->fresh());

    expect((float) $settlement->fresh()->net_amount)->toBeLessThan(0.0)
        ->and(collect($document['notes'])->pluck('text')->implode(' '))->toContain('ملاحظة اختبار')
        ->and(collect($document['totals'])->pluck(0)->all())->toContain(__('payroll::payroll.settlement.fields.other_adjustment'));

    expect(Pdf::loadView('pdf.trade-document', ['docs' => [$document]])->output())->toStartWith('%PDF');
});

it('GratuityPdfTest: the statement can be downloaded from the view page', function (): void {
    $settlement = pdfSettlement();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach (['view_any_payroll_end::of::service::settlement', 'view_payroll_end::of::service::settlement'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->assertActionVisible('downloadStatement')
        ->callAction('downloadStatement')
        ->assertFileDownloaded('end-of-service-'.$settlement->id.'.pdf');
});

it('GratuityPdfTest: the open-ended band reads "and above" instead of "to and above"', function (): void {
    $settlement = pdfSettlement();
    $document = EndOfServiceSettlementDocument::make($settlement);

    expect($document['rows'][0]['band'])->toBe(__('payroll::payroll.settlement.pdf.band', ['from' => '0', 'to' => '3']))
        ->and($document['rows'][1]['band'])->toBe(__('payroll::payroll.settlement.pdf.band_open', ['from' => '3']))
        ->and($document['rows'][1]['band'])->toContain('فما فوق')
        ->and($document['rows'][1]['band'])->not->toContain('إلى وما فوق');

    $lines = EndOfServiceSettlementResource::breakdownLines($settlement);

    expect($lines[1])->toContain('فما فوق')->and($lines[1])->not->toContain('إلى وما فوق');
});

it('GratuityPdfTest: the service length has no parentheses that the text direction would scramble', function (): void {
    $document = EndOfServiceSettlementDocument::make(pdfSettlement());
    $service = collect($document['meta'])->firstWhere(0, __('payroll::payroll.settlement.pdf.service'))[1];

    expect($service)->not->toContain('(')->and($service)->not->toContain(')')->and($service)->toContain('يوماً');
});
