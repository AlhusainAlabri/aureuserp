<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchases_orders')) {
            return;
        }

        Schema::table('purchases_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchases_orders', 'request_type')) {
                $table->enum('request_type', [
                    'standard_purchase',
                    'device_request',
                    'technical_support',
                    'office_supplies',
                    'maintenance',
                    'other',
                ])->default('standard_purchase')->after('id');
            }

            if (! Schema::hasColumn('purchases_orders', 'urgency')) {
                $table->enum('urgency', ['low', 'normal', 'high', 'critical'])->default('normal');
            }

            if (! Schema::hasColumn('purchases_orders', 'requesting_department_id')) {
                $table->unsignedBigInteger('requesting_department_id')->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'beneficiary_department_id')) {
                $table->unsignedBigInteger('beneficiary_department_id')->nullable()->after('requesting_department_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('beneficiary_department_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'meeting_id')) {
                $table->unsignedBigInteger('meeting_id')->nullable()->after('project_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'quotation_path')) {
                $table->string('quotation_path')->nullable()->after('meeting_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_uploaded')) {
                $table->boolean('receipt_uploaded')->default(false)->after('quotation_path');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('receipt_uploaded');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_uploaded_at')) {
                $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_path');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_reminder_sent_at')) {
                $table->timestamp('receipt_reminder_sent_at')->nullable()->after('receipt_uploaded_at');
            }

            if (! Schema::hasColumn('purchases_orders', 'payment_voucher_path')) {
                $table->string('payment_voucher_path')->nullable()->after('receipt_reminder_sent_at');
            }

            if (! Schema::hasColumn('purchases_orders', 'payment_voucher_uploaded_at')) {
                $table->timestamp('payment_voucher_uploaded_at')->nullable()->after('payment_voucher_path');
            }

            if (! Schema::hasColumn('purchases_orders', 'amount_paid')) {
                $table->decimal('amount_paid', 16, 4)->default(0)->after('payment_voucher_uploaded_at');
            }

            if (! Schema::hasColumn('purchases_orders', 'amount_remaining')) {
                $table->decimal('amount_remaining', 16, 4)->default(0)->after('amount_paid');
            }
        });

        if (! Schema::hasTable('purchases_order_payments')) {
            Schema::create('purchases_order_payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->decimal('amount', 16, 4);
                $table->timestamp('paid_at');
                $table->string('voucher_path')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->unsignedBigInteger('account_move_id')->nullable();
                $table->timestamps();

                $table->foreign('order_id')
                    ->references('id')
                    ->on('purchases_orders')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases_order_payments');

        if (! Schema::hasTable('purchases_orders')) {
            return;
        }

        Schema::table('purchases_orders', function (Blueprint $table): void {
            foreach ([
                'request_type',
                'urgency',
                'requesting_department_id',
                'beneficiary_department_id',
                'project_id',
                'meeting_id',
                'quotation_path',
                'receipt_uploaded',
                'receipt_path',
                'receipt_uploaded_at',
                'receipt_reminder_sent_at',
                'payment_voucher_path',
                'payment_voucher_uploaded_at',
                'amount_paid',
                'amount_remaining',
            ] as $column) {
                if (Schema::hasColumn('purchases_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
