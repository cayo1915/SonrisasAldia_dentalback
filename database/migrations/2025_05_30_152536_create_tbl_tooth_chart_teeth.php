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
        Schema::create('tbl_tooth_chart_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_history_id')->constrained('tbl_clinical_histories')->onDelete('cascade');
            $table->integer('tooth_number')->nullable(); // Copiado del modelo base
            $table->boolean('is_checked')->default(false); // Marcado por el doctor
            $table->text('observation')->nullable(); // Observación por diente, opcional
            $table->enum('quadrant', ['upper-right', 'upper-left', 'lower-left', 'lower-right'])->nullable(); // Copiado del modelo base
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_tooth_chart_teeth');
    }
};
