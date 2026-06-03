<?php

namespace Webkul\Payroll;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Policies\LoanPolicy;
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
            ])
            ->hasDependencies([
                'employees',
            ])
            ->runsMigrations()
            ->hasSeeders(
                'Webkul\\Payroll\\Database\\Seeders\\DefaultSalaryComponentsSeeder',
                'Webkul\\Payroll\\Database\\Seeders\\PayrollBatchApprovalFlowSeeder',
                'Webkul\\Payroll\\Database\\Seeders\\LoanApprovalFlowSeeder',
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

        if (class_exists(Employee::class) && Schema::hasTable('payroll_employee_components')) {
            Employee::resolveRelationUsing(
                'employeeComponents',
                fn (Employee $employee) => $employee->hasMany(EmployeeComponent::class, 'employee_id'),
            );
        }
    }
}
