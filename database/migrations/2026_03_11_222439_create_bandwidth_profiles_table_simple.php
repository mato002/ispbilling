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
        if (Schema::hasTable('bandwidth_profiles')) {
            return;
        }

        Schema::create('bandwidth_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('isp_id');
            $table->string('name'); // Profile name (e.g., 'Basic', 'Standard', 'Premium')

            // Speed limits (in Kbps)
            $table->integer('download_limit');
            $table->integer('upload_limit');

            // Burst settings
            $table->integer('download_burst_limit')->nullable();
            $table->integer('upload_burst_limit')->nullable();
            $table->integer('download_burst_threshold')->nullable();
            $table->integer('upload_burst_threshold')->nullable();
            $table->integer('download_burst_time')->nullable();
            $table->integer('upload_burst_time')->nullable();

            // Queue settings
            $table->integer('priority')->default(8);
            $table->integer('limit_at')->nullable();
            $table->integer('max_limit')->nullable();
            $table->integer('burst_limit')->nullable();
            $table->integer('burst_threshold')->nullable();
            $table->integer('burst_time')->nullable();
            $table->string('target')->nullable();

            // Hierarchical profiles (simplified)
            $table->unsignedBigInteger('parent_profile_id')->nullable();
            $table->string('queue_type')->default('queue-simple'); // queue-simple, queue-tree

            // Advanced rate limiting
            $table->integer('rate_limit_rx')->nullable();
            $table->integer('rate_limit_tx')->nullable();
            $table->integer('rate_limit_rx_min')->nullable();
            $table->integer('rate_limit_tx_min')->nullable();
            $table->integer('rate_limit_rx_max')->nullable();
            $table->integer('rate_limit_tx_max')->nullable();

            // Metadata
            $table->text('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config_file')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['isp_id', 'is_active']);
            $table->unique(['isp_id', 'name'], 'unique_isp_profile_name');
        });

        // Add foreign keys after table creation
        Schema::table('bandwidth_profiles', function (Blueprint $table) {
            $table->foreign('isp_id')->references('id')->on('i_s_p_s')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandwidth_profiles');
    }
};
