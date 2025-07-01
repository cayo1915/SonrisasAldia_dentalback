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
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idsender')->unsigned()->nullable();
            $table->foreign('idsender')->references('id')->on('tbl_user');
            $table->string('message_title', 100);
            $table->text('message_body')->nullable();
            $table->text('data_json')->comment('recibe un json de la url y idurl si lo requiere');
            $table->dateTime('date_sent')->now();
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_notifications');
    }
};
