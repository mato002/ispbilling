<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
            }
        }

        if (! Schema::hasColumn('payments', 'platform_fee_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('platform_fee_amount', 14, 2)->nullable()->after('amount');
            });
        }
        if (! Schema::hasColumn('payments', 'platform_fee_rate')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('platform_fee_rate', 8, 4)->nullable()->after('platform_fee_amount');
            });
        }

        if (! Schema::hasTable('isp_payouts')) {
            if (! Schema::hasTable('isp_payouts')) {
            Schema::create('isp_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 8)->default('KES');
                $table->string('status', 32)->default('pending');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'status']);
            });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_payouts');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_amount', 'platform_fee_rate']);
        });

        Schema::dropIfExists('platform_settings');
    }
};
