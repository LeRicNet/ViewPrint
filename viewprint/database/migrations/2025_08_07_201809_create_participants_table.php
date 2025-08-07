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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Anonymous participant code
            $table->string('group')->nullable(); // e.g., "expert", "novice"
            $table->json('metadata'); // Flexible participant attributes
            $table->timestamps();

            // Indexes
            $table->index('group');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
