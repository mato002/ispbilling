<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('router_backups')) {
        Schema::create('router_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->default('export'); // export|backup
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('status', 16)->default('completed'); // pending|completed|failed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['router_id', 'created_at']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('router_backups');
    }
};
