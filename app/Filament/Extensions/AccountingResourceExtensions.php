<?php

namespace App\Filament\Extensions;

use App\Filament\Extensions\Concerns\OptimizesRepeaterSelects;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Webkul\Field\Filament\Forms\Components\ProgressStepper as FormProgressStepper;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Models\Currency;

class AccountingResourceExtensions
{
    use OptimizesRepeaterSelects;

    public static function localizeForm(Schema $schema): Schema
    {
        static::walkComponents(
            $schema->getComponents(withHidden: true),
            function (mixed $component): void {
                if (! $component instanceof Component) {
                    return;
                }

                if ($component instanceof FormProgressStepper && $component->getName() === 'state') {
                    $component->label(__('accounting-extensions::forms.state'));
                }

                if ($component instanceof Select && $component->getName() === 'currency_id') {
                    $component->default(fn (): ?int => static::defaultOmrCurrencyId());
                }

                if ($component instanceof Select && $component->getName() === 'partner_id') {
                    static::excludeTrashedPartnersFromSelect($component);
                }
            },
        );

        return static::optimizeFormSelects($schema);
    }

    /**
     * Core invoice/bill forms use withTrashed() so deleted partners still appear in the list.
     * Keep labels for an already-selected deleted partner on edit, but do not offer them as options.
     */
    public static function excludeTrashedPartnersFromSelect(Select $component): Select
    {
        return $component
            ->relationship(
                'partner',
                'name',
                fn (Builder $query) => $query->orderBy('id'),
            )
            ->getOptionLabelUsing(function ($value): ?string {
                $partner = Partner::query()->withTrashed()->find($value);

                if (! $partner) {
                    return null;
                }

                return $partner->name.($partner->trashed() ? ' (Deleted)' : '');
            })
            ->disableOptionWhen(fn ($label): bool => str_contains((string) $label, ' (Deleted)'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeJournalLines(array $data): array
    {
        if (! isset($data['lines']) || ! is_array($data['lines'])) {
            return $data;
        }

        $balancingLabel = __('accounting-extensions::journal-entry.automatic-balancing');

        $data['lines'] = collect($data['lines'])
            ->filter(fn (array $line): bool => filled($line['account_id'] ?? null))
            ->map(function (array $line) use ($balancingLabel): array {
                if (($line['name'] ?? '') === 'Automatic Balancing') {
                    $line['name'] = $balancingLabel;
                }

                return $line;
            })
            ->values()
            ->all();

        return $data;
    }

    /**
     * @return array<string, array{0: Carbon, 1: Carbon}>
     */
    public static function localizedDateRanges(): array
    {
        return [
            __('accounting-extensions::reporting.ranges.today')        => [now()->startOfDay(), now()->endOfDay()],
            __('accounting-extensions::reporting.ranges.yesterday')    => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            __('accounting-extensions::reporting.ranges.this_month')   => [now()->startOfMonth(), now()->endOfMonth()],
            __('accounting-extensions::reporting.ranges.last_month')   => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            __('accounting-extensions::reporting.ranges.this_quarter') => [now()->startOfQuarter(), now()->endOfQuarter()],
            __('accounting-extensions::reporting.ranges.last_quarter') => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            __('accounting-extensions::reporting.ranges.this_year')    => [now()->startOfYear(), now()->endOfYear()],
            __('accounting-extensions::reporting.ranges.last_year')    => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
        ];
    }

    public static function defaultOmrCurrencyId(): ?int
    {
        if (! SchemaFacade::hasTable('support_currencies')) {
            return null;
        }

        return Currency::query()
            ->where('name', 'OMR')
            ->orWhere('full_name', 'like', '%Omani%')
            ->value('id');
    }

    /**
     * @param  array<Component>  $components
     */
    protected static function walkComponents(array $components, Closure $callback): void
    {
        foreach ($components as $component) {
            $callback($component);

            if (! method_exists($component, 'getDefaultChildComponents')) {
                continue;
            }

            $children = $component->getDefaultChildComponents();

            if ($children instanceof Schema) {
                $children = $children->getComponents(withHidden: true);
            }

            if (! is_array($children) || $children === []) {
                continue;
            }

            static::walkComponents($children, $callback);
        }
    }
}
