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

        Schema::create('tbl_reservation', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total')->nullable();
            $table->bigInteger('idpatient')->unsigned();
            $table->foreign('idpatient')->references('id')->on('tbl_user');
            $table->bigInteger('iddoctor')->unsigned();
            $table->foreign('iddoctor')->references('id')->on('tbl_user');
            $table->bigInteger('idhorary')->unsigned();
            $table->foreign('idhorary')->references('id')->on('tbl_horary');
            $table->enum('type_modality', ['in_person', 'virtual'])->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_attended')->default(false);
            $table->boolean('is_rescheduled')->default(false);
            $table->timestamp('rescheduled_at')->nullable()->comment('Fecha y hora en que fue reprogramada la reserva');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_reservation');
    }
};
