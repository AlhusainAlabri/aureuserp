<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Accounting\Filament\Clusters\Accounting;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\CoreJournalEntryResource;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\CoreJournalItemResource;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages\CoreCreateJournalEntry;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages\CoreEditJournalEntry;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages\CreateJournalEntry;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages\EditJournalEntry;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalItemResource;
use Webkul\Accounting\Filament\Clusters\Customers;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource as AccountingInvoiceResource;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\BalanceSheet;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\CoreBalanceSheet;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\CoreGeneralLedger;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\CorePartnerLedger;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\CoreProfitLoss;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\CoreTrialBalance;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\GeneralLedger;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\PartnerLedger;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\ProfitLoss;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\TrialBalance;
use Webkul\Accounting\Filament\Clusters\Vendors;
use Webkul\Accounting\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\Accounting\Filament\Pages\Overview;

class AccountingExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerClusterOverrides();
        $this->registerResourceOverrides();
        $this->registerPageOverrides();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('accounting-extensions'), 'accounting-extensions');

        View::prependNamespace('accounting', resource_path('views/vendor/accounting'));

        if (! class_exists(Journal::class)) {
            return;
        }

        $this->registerLocalizedMasterData();
    }

    protected function registerClusterOverrides(): void
    {
        foreach ([Accounting::class, Customers::class, Vendors::class] as $cluster) {
            spl_autoload_register(
                function (string $class) use ($cluster): bool {
                    if ($class !== $cluster) {
                        return false;
                    }

                    $relative = str_replace('Webkul\\Accounting\\Filament\\Clusters\\', '', $cluster);
                    require app_path('Overrides/Webkul/Accounting/Filament/Clusters/'.$relative.'.php');

                    return true;
                },
                prepend: true,
            );
        }
    }

    protected function registerResourceOverrides(): void
    {
        $this->registerCoreClassOverride(
            JournalEntryResource::class,
            CoreJournalEntryResource::class,
            'plugins/webkul/accounting/src/Filament/Clusters/Accounting/Resources/JournalEntryResource.php',
            'core_journal_entry_resource.php',
            'JournalEntryResource',
            'CoreJournalEntryResource',
            'Overrides/Webkul/Accounting/Filament/Clusters/Accounting/Resources/JournalEntryResource.php',
        );

        $this->registerCoreClassOverride(
            JournalItemResource::class,
            CoreJournalItemResource::class,
            'plugins/webkul/accounting/src/Filament/Clusters/Accounting/Resources/JournalItemResource.php',
            'core_journal_item_resource.php',
            'JournalItemResource',
            'CoreJournalItemResource',
            'Overrides/Webkul/Accounting/Filament/Clusters/Accounting/Resources/JournalItemResource.php',
        );

        $directOverrides = [
            BillResource::class              => 'Overrides/Webkul/Accounting/Filament/Clusters/Vendors/Resources/BillResource.php',
            AccountingInvoiceResource::class => 'Overrides/Webkul/Accounting/Filament/Clusters/Customers/Resources/InvoiceResource.php',
        ];

        foreach ($directOverrides as $class => $path) {
            spl_autoload_register(
                function (string $requested) use ($class, $path): bool {
                    if ($requested !== $class) {
                        return false;
                    }

                    require app_path($path);

                    return true;
                },
                prepend: true,
            );
        }
    }

    protected function registerPageOverrides(): void
    {
        $this->registerCoreClassOverride(
            CreateJournalEntry::class,
            CoreCreateJournalEntry::class,
            'plugins/webkul/accounting/src/Filament/Clusters/Accounting/Resources/JournalEntryResource/Pages/CreateJournalEntry.php',
            'core_create_journal_entry.php',
            'CreateJournalEntry',
            'CoreCreateJournalEntry',
            'Overrides/Webkul/Accounting/Filament/Clusters/Accounting/Resources/JournalEntryResource/Pages/CreateJournalEntry.php',
        );

        $this->registerCoreClassOverride(
            EditJournalEntry::class,
            CoreEditJournalEntry::class,
            'plugins/webkul/accounting/src/Filament/Clusters/Accounting/Resources/JournalEntryResource/Pages/EditJournalEntry.php',
            'core_edit_journal_entry.php',
            'EditJournalEntry',
            'CoreEditJournalEntry',
            'Overrides/Webkul/Accounting/Filament/Clusters/Accounting/Resources/JournalEntryResource/Pages/EditJournalEntry.php',
        );

        spl_autoload_register(
            function (string $class): bool {
                if ($class !== Overview::class) {
                    return false;
                }

                require app_path('Overrides/Webkul/Accounting/Filament/Pages/Overview.php');

                return true;
            },
            prepend: true,
        );

        $reportingPages = [
            BalanceSheet::class   => [CoreBalanceSheet::class, 'BalanceSheet', 'CoreBalanceSheet', 'balance_sheet.php'],
            ProfitLoss::class     => [CoreProfitLoss::class, 'ProfitLoss', 'CoreProfitLoss', 'profit_loss.php'],
            TrialBalance::class   => [CoreTrialBalance::class, 'TrialBalance', 'CoreTrialBalance', 'trial_balance.php'],
            GeneralLedger::class  => [CoreGeneralLedger::class, 'GeneralLedger', 'CoreGeneralLedger', 'general_ledger.php'],
            PartnerLedger::class  => [CorePartnerLedger::class, 'PartnerLedger', 'CorePartnerLedger', 'partner_ledger.php'],
        ];

        foreach ($reportingPages as $class => [$coreClass, $sourceClass, $coreSourceClass, $cacheFile]) {
            $this->registerCoreClassOverride(
                $class,
                $coreClass,
                'plugins/webkul/accounting/src/Filament/Clusters/Reporting/Pages/'.$sourceClass.'.php',
                $cacheFile,
                $sourceClass,
                $coreSourceClass,
                'Overrides/Webkul/Accounting/Filament/Clusters/Reporting/Pages/'.$sourceClass.'.php',
            );
        }
    }

    protected function registerCoreClassOverride(
        string $class,
        string $coreClass,
        string $sourceRelativePath,
        string $cacheFilename,
        string $sourceClassName,
        string $coreClassName,
        string $overrideRelativePath,
    ): void {
        spl_autoload_register(
            function (string $requested) use ($class, $coreClass, $sourceRelativePath, $cacheFilename, $sourceClassName, $coreClassName, $overrideRelativePath): bool {
                if ($requested === $coreClass) {
                    $this->ensureCoreClassIsLoaded(
                        base_path($sourceRelativePath),
                        storage_path('framework/cache/'.$cacheFilename),
                        $sourceClassName,
                        $coreClassName,
                        $coreClass,
                    );

                    return true;
                }

                if ($requested !== $class) {
                    return false;
                }

                $this->ensureCoreClassIsLoaded(
                    base_path($sourceRelativePath),
                    storage_path('framework/cache/'.$cacheFilename),
                    $sourceClassName,
                    $coreClassName,
                    $coreClass,
                );

                require app_path($overrideRelativePath);

                return true;
            },
            prepend: true,
        );
    }

    protected function ensureCoreClassIsLoaded(
        string $source,
        string $cachePath,
        string $sourceClassName,
        string $coreClassName,
        string $coreClass,
    ): void {
        if (class_exists($coreClass, false)) {
            return;
        }

        if (! file_exists($cachePath) || filemtime($cachePath) < filemtime($source)) {
            $code = file_get_contents($source);
            $code = preg_replace('/\bclass '.$sourceClassName.'\b/', 'class '.$coreClassName, $code, 1);

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        require $cachePath;
    }

    protected function registerLocalizedMasterData(): void
    {
        Journal::retrieved(function (Journal $journal): void {
            if (app()->getLocale() !== 'ar') {
                return;
            }

            $translated = __('accounting-extensions::journals.'.$journal->code);

            if (! str_starts_with($translated, 'accounting-extensions::')) {
                $journal->setAttribute('name', $translated);
            }
        });

        Account::retrieved(function (Account $account): void {
            if (app()->getLocale() !== 'ar') {
                return;
            }

            $translated = __('accounting-extensions::accounts.'.$account->name);

            if (! str_starts_with($translated, 'accounting-extensions::')) {
                $account->setAttribute('name', $translated);
            }
        });
    }
}
