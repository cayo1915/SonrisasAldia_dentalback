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
        Schema::create('tbl_agenda', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->integer('duration_hour')->nullable();
            $table->integer('wait_time_hour')->nullable();
            $table->time('break_start', 0)->nullable();
            $table->time('break_end', 0)->nullable();
            $table->text('modality')->nullable()->comment('[1]:Presencial, [2]:Virtual, [1,2]:Ambos');
            $table->bigInteger('iddoctor')->unsigned();
            $table->foreign('iddoctor')->references('id')->on('tbl_user');
            $table->enum('agenda_type', ['availability', 'unavailability'])->default('availability');
            $table->boolean('full_day')->default(false);
            $table->date('start_date_block')->nullable();
            $table->date('end_date_block')->nullable();
            $table->time('start_hour_block')->nullable();
            $table->time('end_hour_block')->nullable();
            $table->string('color_agenda', 20)->nullable();
            $table->string('comment')->nullable();
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_agenda');
    }
};
