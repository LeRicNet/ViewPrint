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
        Schema::create('volumes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->json('dimensions'); // [x, y, z] voxel dimensions
            $table->json('voxel_size'); // [x, y, z] voxel size in mm
            $table->json('metadata'); // Additional NIfTI header data
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volumes');
    }
};
