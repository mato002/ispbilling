<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('isp_id')->nullable()->constrained('i_s_p_s')->onDelete('cascade');
            $table->enum('role', ['super_admin', 'isp_admin', 'isp_staff'])->default('isp_staff');
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['isp_id']);
            $table->dropColumn(['isp_id', 'role', 'is_active']);
        });
    }
};
