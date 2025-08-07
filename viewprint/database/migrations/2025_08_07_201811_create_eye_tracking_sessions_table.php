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
        Schema::create('eye_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained();
            $table->foreignId('volume_id')->constrained();
            $table->integer('duration_ms'); // Total viewing time
            $table->string('raw_data_path'); // Path to CSV/raw data
            $table->string('processed_data_path')->nullable(); // Path to processed NIfTI
            $table->json('metadata'); // Session-specific data
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Indexes
            $table->index(['participant_id', 'volume_id'], 'idx_participant_volume');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eye_tracking_sessions');
    }
};
