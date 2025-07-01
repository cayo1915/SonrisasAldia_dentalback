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
        Schema::create('tbl_charge', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('description')->nullable();
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
        Schema::dropIfExists('tbl_charge');
    }
};
