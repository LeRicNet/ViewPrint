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
        Schema::create('calculation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_layer_id')->constrained('workspace_layers');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->integer('progress')->default(0); // 0-100
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index(['workspace_layer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculation_jobs');
    }
};
