<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_message_queues', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_message_queues', 'campaign_id')) {
                $table->foreignId('campaign_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('communication_campaigns')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('sms_message_queues', 'recipient_email')) {
                $table->string('recipient_email')->nullable()->after('recipient_phone');
            }

            if (! Schema::hasColumn('sms_message_queues', 'gateway_response')) {
                $table->text('gateway_response')->nullable()->after('status');
            }

            if (! Schema::hasColumn('sms_message_queues', 'retry_count')) {
                $table->unsignedTinyInteger('retry_count')->default(0)->after('gateway_response');
            }

            if (! Schema::hasColumn('sms_message_queues', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('retry_count');
            }

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('sms_message_queues', function (Blueprint $table) {
            if (Schema::hasColumn('sms_message_queues', 'campaign_id')) {
                $table->dropConstrainedForeignId('campaign_id');
            }
            foreach (['recipient_email', 'gateway_response', 'retry_count', 'sent_at'] as $column) {
                if (Schema::hasColumn('sms_message_queues', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
