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

    public function down(): void
    {
        foreach (['time_off_leaves', 'hr_leaves', 'leaves'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['substitute_notes', 'substitute_declined_at', 'substitute_accepted_at', 'substitute_employee_id'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        if ($column === 'substitute_employee_id') {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });

            break;
        }
    }
};
