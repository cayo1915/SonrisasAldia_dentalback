<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_country_time_zone', function (Blueprint $table) {
            $table->id();
            $table->string('description', 30);
            $table->string('time_zone');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_country_time_zone');
    }
};
