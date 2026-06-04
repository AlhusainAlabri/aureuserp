<?php

namespace App\Services\Hr;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrExtensionSchemaService
{
    public function ensure(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        $this->ensureDepartmentEmployeeTable();
        $this->ensureEmployeeTrainingsTable();
        $this->ensureEmployeeContractsTable();
        $this->ensureEmployeeSalaryRaisesTable();
        $this->ensureEmployeeSelfAssessmentsTable();
        $this->ensureEmployeeProfileFields();
        $this->ensureEmployeeClosureFields();
        $this->ensureEmployeeDocumentTypes();
        $this->ensureEmployeeWarningAcknowledgmentFields();
        $this->ensureAnonymousSubmissionField();
        $this->ensureLeaveSubstituteFields();
    }

    protected function ensureDepartmentEmployeeTable(): void
    {
        if (Schema::hasTable('department_employee') || ! Schema::hasTable('employees_departments')) {
            return;
        }

        Schema::create('department_employee', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('employees_departments')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'department_id']);
            $table->index(['employee_id', 'is_primary']);
        });
    }

    protected function ensureEmployeeTrainingsTable(): void
    {
        if (Schema::hasTable('employee_trainings')) {
            return;
        }

        Schema::create('employee_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->string('course_name');
            $table->string('provider')->nullable();
            $table->enum('type', ['internal', 'external', 'online', 'workshop', 'conference', 'certification'])->default('external');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->decimal('cost', 12, 3)->nullable();
            $table->string('cost_currency')->default('OMR');
            $table->string('certificate_path')->nullable();
            $table->date('certificate_expiry_date')->nullable();
            $table->date('certificate_notified_at')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function ensureEmployeeContractsTable(): void
    {
        if (Schema::hasTable('employee_contracts')) {
            return;
        }

        Schema::create('employee_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->enum('contract_type', ['permanent', 'fixed_term', 'temporary', 'probation'])->default('fixed_term');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->date('first_joining_date')->nullable();
            $table->decimal('wage', 12, 3)->nullable();
            $table->string('contract_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('notified_at')->nullable();
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'is_active']);
            $table->index('end_date');
        });
    }

    protected function ensureEmployeeSalaryRaisesTable(): void
    {
        if (Schema::hasTable('employee_salary_raises')) {
            return;
        }

        Schema::create('employee_salary_raises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->date('effective_date');
            $table->decimal('old_amount', 12, 3);
            $table->decimal('new_amount', 12, 3);
            $table->decimal('raise_amount', 12, 3);
            $table->decimal('raise_percent', 6, 2);
            $table->enum('reason', [
                'annual_review',
                'performance',
                'promotion',
                'cost_of_living',
                'market_adjustment',
                'other',
            ]);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->index(['employee_id', 'effective_date']);
        });

        if (Schema::hasTable('employee_contracts')) {
            Schema::table('employee_salary_raises', function (Blueprint $table): void {
                $table->foreign('contract_id')->references('id')->on('employee_contracts')->nullOnDelete();
            });
        }
    }

    protected function ensureEmployeeSelfAssessmentsTable(): void
    {
        if (! Schema::hasTable('employee_self_assessments')) {
            Schema::create('employee_self_assessments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->text('employee_comments')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
                $table->text('manager_feedback')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('creator_id')->constrained('users');
                $table->timestamps();
                $table->unique(['employee_id', 'period_year', 'period_month'], 'emp_self_assess_period_unique');
            });

            return;
        }

        $this->ensureEmployeeSelfAssessmentsPeriodUnique();
    }

    protected function ensureEmployeeSelfAssessmentsPeriodUnique(): void
    {
        $indexName = 'emp_self_assess_period_unique';

        if (in_array($indexName, Schema::getIndexListing('employee_self_assessments'), true)) {
            return;
        }

        Schema::table('employee_self_assessments', function (Blueprint $table) use ($indexName): void {
            $table->unique(['employee_id', 'period_year', 'period_month'], $indexName);
        });
    }

    protected function ensureEmployeeProfileFields(): void
    {
        Schema::table('employees_employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees_employees', 'primary_job_responsibilities')) {
                $table->text('primary_job_responsibilities')->nullable();
            }
        });
    }

    protected function ensureEmployeeDocumentTypes(): void
    {
        if (! Schema::hasTable('employees_employee_documents')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $column = collect(Schema::getColumns('employees_employee_documents'))
            ->firstWhere('name', 'document_type');

        if ($column && str_contains((string) ($column['type_name'] ?? ''), 'professional_conduct')) {
            return;
        }

        Schema::table('employees_employee_documents', function (Blueprint $table): void {
            $table->enum('document_type', [
                'id_card',
                'passport',
                'residence_permit',
                'contract',
                'certificate',
                'professional_conduct',
                'other',
            ])->change();
        });
    }

    protected function ensureEmployeeWarningAcknowledgmentFields(): void
    {
        if (! Schema::hasTable('employees_employee_warnings')) {
            return;
        }

        Schema::table('employees_employee_warnings', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees_employee_warnings', 'acknowledgment_signature')) {
                $table->text('acknowledgment_signature')->nullable();
            }

            if (! Schema::hasColumn('employees_employee_warnings', 'signed_document_path')) {
                $table->string('signed_document_path')->nullable();
            }

            if (! Schema::hasColumn('employees_employee_warnings', 'employee_acknowledged_at')) {
                $table->timestamp('employee_acknowledged_at')->nullable();
            }
        });
    }

    protected function ensureAnonymousSubmissionField(): void
    {
        if (! Schema::hasTable('employees_employee_submissions')) {
            return;
        }

        Schema::table('employees_employee_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees_employee_submissions', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false);
            }
        });
    }

    protected function ensureEmployeeClosureFields(): void
    {
        Schema::table('employees_employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees_employees', 'is_closed')) {
                $table->boolean('is_closed')->default(false);
            }

            if (! Schema::hasColumn('employees_employees', 'closure_reason')) {
                $table->enum('closure_reason', [
                    'administrative',
                    'ethical',
                    'resignation',
                    'retirement',
                    'contract_ended',
                    'other',
                ])->nullable();
            }

            if (! Schema::hasColumn('employees_employees', 'closure_notes')) {
                $table->text('closure_notes')->nullable();
            }

            if (! Schema::hasColumn('employees_employees', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }

            if (! Schema::hasColumn('employees_employees', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users');
            }

            if (! Schema::hasColumn('employees_employees', 'reopen_reason')) {
                $table->text('reopen_reason')->nullable();
            }

            if (! Schema::hasColumn('employees_employees', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable();
            }

            if (! Schema::hasColumn('employees_employees', 'reopened_by')) {
                $table->foreignId('reopened_by')->nullable()->constrained('users');
            }
        });

        if (Schema::hasColumn('employees_employees', 'is_closed')) {
            $indexes = collect(Schema::getIndexes('employees_employees'))
                ->pluck('name')
                ->all();

            if (! in_array('employees_employees_is_closed_index', $indexes, true)) {
                Schema::table('employees_employees', function (Blueprint $table): void {
                    $table->index('is_closed');
                });
            }
        }
    }

    protected function ensureLeaveSubstituteFields(): void
    {
        foreach (['time_off_leaves', 'hr_leaves', 'leaves'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'substitute_employee_id')) {
                    $table->foreignId('substitute_employee_id')
                        ->nullable()
                        ->constrained('employees_employees')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'substitute_accepted_at')) {
                    $table->timestamp('substitute_accepted_at')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'substitute_declined_at')) {
                    $table->timestamp('substitute_declined_at')->nullable();
                }

                if (! Schema::hasColumn($tableName, 'substitute_notes')) {
                    $table->string('substitute_notes')->nullable();
                }
            });

            break;
        }
    }
}
