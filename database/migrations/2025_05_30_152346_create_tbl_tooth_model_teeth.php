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
        Schema::create('tbl_tooth_model_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_model_id')->constrained('tbl_tooth_models')->onDelete('cascade');
            $table->integer('tooth_number')->nullable(); // 11–48
            $table->enum('quadrant', [
                'upper-right',
                'upper-left',
                'lower-left',
                'lower-right',
            ]);
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_tooth_model_teeth');
    }
};
