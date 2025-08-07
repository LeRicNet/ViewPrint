<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->enum('layer_type', ['base_volume', 'participant_volume', 'calculated', 'external']);
            $table->string('name');
            $table->integer('position')->default(0); // Rendering order (0 = bottom)
            $table->boolean('visible')->default(true);
            $table->integer('opacity')->default(75); // 0-100
            $table->json('configuration'); // Layer-specific settings
            $table->timestamps();

            // Indexes
            $table->index(['workspace_id', 'position'], 'idx_workspace_position');
            $table->index(['workspace_id', 'visible'], 'idx_workspace_visible');
        });

        // Add check constraint for opacity (MySQL 8.0.16+ supports check constraints)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE workspace_layers ADD CONSTRAINT chk_opacity CHECK (opacity >= 0 AND opacity <= 100)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop check constraint if not SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE workspace_layers DROP CONSTRAINT IF EXISTS chk_opacity');
        }

        Schema::dropIfExists('workspace_layers');
    }
};
