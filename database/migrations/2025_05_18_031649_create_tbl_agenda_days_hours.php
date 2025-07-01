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
        Schema::create('tbl_agenda_days_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idagenda'); // FK a tbl_agenda
            $table->unsignedBigInteger('idday');    // FK a tbl_day
            $table->time('start_hour');
            $table->time('end_hour');
            $table->foreign('idagenda')->references('id')->on('tbl_agenda')->onDelete('cascade');
            $table->foreign('idday')->references('id')->on('tbl_day')->onDelete('cascade');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_agenda_days_hours');
    }
};
