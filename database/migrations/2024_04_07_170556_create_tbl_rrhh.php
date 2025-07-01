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
        Schema::create('tbl_rrhh', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('surname', 45);
            $table->string('n_document', 15);
            $table->date('birth_date')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 150)->unique();
            $table->text('photo')->nullable();
            $table->string('speciality', 150);
            $table->bigInteger('idcharge')->unsigned()->nullable();
            $table->foreign('idcharge')->references('id')->on('tbl_charge');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_rrhh');
    }
};
