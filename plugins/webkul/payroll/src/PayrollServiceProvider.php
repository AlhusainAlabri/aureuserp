<?php

namespace Webkul\Payroll;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Policies\GratuityTierPolicy;
use Webkul\Payroll\Policies\LoanPolicy;
use Webkul\Payroll\Policies\PasiRulePolicy;
use Webkul\Payroll\Policies\PayrollBatchPolicy;
use Webkul\Payroll\Policies\PayslipPolicy;
use Webkul\Payroll\Policies\SalaryComponentPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class PayrollServiceProvider extends PackageServiceProvider
{
    public static string $name = 'payroll';

    public static string $viewNamespace = 'payroll';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_23_120000_create_payroll_salary_components_table',
                '2026_05_23_120001_create_payroll_employee_components_table',
                '2026_05_23_120002_create_payroll_batches_table',
                '2026_05_23_120003_create_payroll_payslips_table',
                '2026_05_23_120004_create_payroll_payslip_lines_table',
                '2026_05_23_120005_create_payroll_loans_table',
                '2026_05_23_120006_create_payroll_loan_installments_table',
                '2026_10_26_100000_create_payroll_pasi_rules_table',
                '2026_10_26_100001_add_is_pasi_insurable_to_payroll_salary_components_table',
                '2026_10_26_100002_add_pasi_base_to_payroll_payslips_table',
                '2026_10_26_200000_create_payroll_gratuity_tiers_table',
                '2026_10_26_200001_create_payroll_end_of_service_settlements_table',
                '2026_10_26_200002_add_pays_gratuity_to_employees_departure_reasons_table',
            ])
            ->hasDependencies([
                'employees',
            ])
            ->runsMigrations()
            ->hasSettings([
                '2026_10_26_200003_create_gratuity_settings',
            ])
            ->runsSettings()
            ->hasSeeders(
                'Webkul\\Payroll\\Database\\Seeders\\DefaultSalaryComponentsSeeder',
                'Webkul\\Payroll\\Database\\Seeders\\PayrollBatchApprovalFlowSeeder',
                'Webkul\\Payroll\\Database\\Seeders\\LoanApprovalFlowSeeder',
                'Webkul\\Payroll\\Database\\Seeders\\EndOfServiceApprovalFlowSeeder',
            )
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->runsSeeders()
                    ->endWith(function (InstallCommand $command): void {
                        foreach ([
                            'SalaryComponentResource',
                            'PayrollBatchResource',
                            'PayslipResource',
                            'LoanResource',
                            'PasiRuleResource',
                            'GratuityTierResource',
                        ] as $resource) {
                            $command->call('shield:generate', [
                                '--resource' => $resource,
                                '--panel'    => 'admin',
                            ]);
                        }

                        $arPath = __DIR__.'/../resources/lang/ar/payroll.php';
                        $enPath = __DIR__.'/../resources/lang/en/payroll.php';

                        if (is_file($arPath) && is_file($enPath)) {
                            $ar = require $arPath;
                            $en = require $enPath;
                            $command->info(($ar['install']['success'] ?? '').' '.($en['install']['success'] ?? ''));
                        } else {
                            $command->info(__('payroll::payroll.install.success'));
                        }
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('payroll');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(PayrollPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        if (class_exists(SalaryComponentPolicy::class)) {
            Gate::policy(SalaryComponent::class, SalaryComponentPolicy::class);
        }

        if (class_exists(PayrollBatchPolicy::class)) {
            Gate::policy(PayrollBatch::class, PayrollBatchPolicy::class);
        }

        if (class_exists(PayslipPolicy::class)) {
            Gate::policy(Payslip::class, PayslipPolicy::class);
        }

        if (class_exists(LoanPolicy::class)) {
            Gate::policy(Loan::class, LoanPolicy::class);
        }

        Gate::policy(PasiRule::class, PasiRulePolicy::class);
        Gate::policy(GratuityTier::class, GratuityTierPolicy::class);

        if (class_exists(Employee::class) && Schema::hasTable('payroll_employee_components')) {
            Employee::resolveRelationUsing(
                'employeeComponents',
                fn (Employee $employee) => $employee->hasMany(EmployeeComponent::class, 'employee_id'),
            );
        }
    }
}
