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
        Schema::create('tbl_horary', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idday')->unsigned();
            $table->foreign('idday')->references('id')->on('tbl_day');
            $table->time('start');
            $table->time('end');
            $table->integer('duration');
            $table->integer('wait_time');
            $table->bigInteger('idagenda')->unsigned();
            $table->foreign('idagenda')->references('id')->on('tbl_agenda');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_horary');
    }
};
