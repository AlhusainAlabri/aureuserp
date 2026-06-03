<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

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

        Schema::table('employees_employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees_employees', 'is_closed')) {
                $indexes = collect(Schema::getIndexes('employees_employees'))
                    ->pluck('name')
                    ->all();

                if (! in_array('employees_employees_is_closed_index', $indexes, true)) {
                    $table->index('is_closed');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        Schema::table('employees_employees', function (Blueprint $table): void {
            foreach ([
                'reopened_by',
                'reopened_at',
                'reopen_reason',
                'closed_by',
                'closed_at',
                'closure_notes',
                'closure_reason',
                'is_closed',
            ] as $column) {
                if (Schema::hasColumn('employees_employees', $column)) {
                    if (in_array($column, ['closed_by', 'reopened_by'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
