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
        Schema::create('tbl_user_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('tbl_user')->onDelete('cascade');
            $table->enum('type', ['preset', 'personalized']); // 1 = preset, 2 = personalizado
            $table->json('preset_hours')->nullable();
            $table->unsignedInteger('custom_hours_before')->nullable(); //ingresa un número sin signo
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user_reminder_settings');
    }
};
