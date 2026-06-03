<?php

use App\Services\Projects\ProjectCompletionService;
use App\Services\Projects\ProjectFinancialSummaryService;
use App\Services\Projects\ProjectStageHelper;

it('formats completion percentages for display', function (): void {
    expect(app(ProjectCompletionService::class)->formatPercentage(66.666))
        ->toBe('66.7%');
});

it('formats omr amounts with three decimal places', function (): void {
    app()->setLocale('en');

    expect(app(ProjectFinancialSummaryService::class)->formatOmr(1.234))
        ->toBe('OMR 1.234');

    app()->setLocale('ar');

    expect(app(ProjectFinancialSummaryService::class)->formatOmr(1.234))
        ->toBe('ر.ع. 1.234');
});

it('maps project stage aliases for active completed and cancelled tabs', function (): void {
    $aliases = (new ReflectionClass(ProjectStageHelper::class))
        ->getConstant('STAGE_ALIASES');

    expect($aliases['in_progress'])->toContain('In Progress', 'قيد التنفيذ')
        ->and($aliases['done'])->toContain('Done', 'مكتمل')
        ->and($aliases['cancelled'])->toContain('Cancelled', 'ملغي');
});

it('returns empty stage ids when stages table is unavailable', function (): void {
    expect(ProjectStageHelper::stageIdsFor('done'))->toBeArray();
});
