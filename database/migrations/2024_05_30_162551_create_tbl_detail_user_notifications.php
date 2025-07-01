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
        Schema::create('tbl_detail_user_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idreceiver')->unsigned();
            $table->foreign('idreceiver')->references('id')->on('tbl_user');
            $table->bigInteger('idnotification')->unsigned();
            $table->foreign('idnotification')->references('id')->on('tbl_notifications');
            $table->dateTime('date_seen')->nullable();
            $table->enum('delivery_status', ['pending', 'sent', 'viewed', 'failed'])->default('pending');
            $table->enum('app_view', ['mobile', 'web'])->default('mobile');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_detail_user_notifications');
    }
};
