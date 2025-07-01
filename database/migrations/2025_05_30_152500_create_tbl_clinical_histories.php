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
        Schema::create('tbl_clinical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_model_id')->constrained('tbl_tooth_models')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('tbl_user');
            $table->foreignId('patient_id')->constrained('tbl_user');
            $table->foreignId('reservation_id')->constrained('tbl_reservation');
            $table->date('register_date')->nullable();
            $table->string('history_number')->nullable();
            $table->string('document_number', 15)->nullable();
            $table->text('medical_condition')->nullable();
            $table->text('medications')->nullable();
            $table->text('allergies')->nullable();
            $table->text('observation')->nullable();
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_clinical_histories');
    }
};
