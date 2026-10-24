<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-paid SMS wallet + ledger + outbound message queue for multi-tenant ISPs.
 *
 * Domain "tenant" maps to the `i_s_p_s` table (each ISP workspace is a tenant).
 */
return new class extends Migration
{
    /** Physical tenant table in this platform. */
    private const TENANT_TABLE = 'i_s_p_s';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TENANT_TABLE, 'sms_balance')) {
            Schema::table(self::TENANT_TABLE, function (Blueprint $table) {
                $table->decimal('sms_balance', 16, 4)
                    ->default(0)
                    ->after('settings')
                    ->comment('Pre-paid SMS wallet balance (platform currency units)');
            });
        }

        if (! Schema::hasTable('sms_platform_ledgers')) {
            if (! Schema::hasTable('sms_platform_ledgers')) {
            Schema::create('sms_platform_ledgers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')
                    ->constrained(self::TENANT_TABLE)
                    ->cascadeOnDelete();
                $table->decimal('amount', 16, 4);
                $table->enum('type', ['topup', 'deduction']);
                $table->unsignedInteger('sms_count')->default(0);
                $table->string('description');
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
                $table->index(['tenant_id', 'type']);
            });
            }
        }

        if (! Schema::hasTable('sms_message_queues')) {
            if (! Schema::hasTable('sms_message_queues')) {
            Schema::create('sms_message_queues', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')
                    ->constrained(self::TENANT_TABLE)
                    ->cascadeOnDelete();
                $table->foreignId('customer_id')
                    ->constrained('customers')
                    ->cascadeOnDelete();
                $table->string('recipient_phone', 32);
                $table->text('message_body');
                $table->enum('channel', ['sms', 'email', 'both'])->default('sms');
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->decimal('cost_charged', 8, 4)->default(0.6000);
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['status', 'priority']);
                $table->index(['customer_id', 'created_at']);
            });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_message_queues');
        Schema::dropIfExists('sms_platform_ledgers');

        if (Schema::hasColumn(self::TENANT_TABLE, 'sms_balance')) {
            Schema::table(self::TENANT_TABLE, function (Blueprint $table) {
                $table->dropColumn('sms_balance');
            });
        }
    }
};
