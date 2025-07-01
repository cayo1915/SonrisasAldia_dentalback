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
        Schema::create('tbl_user', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idrrhh')->unsigned();
            $table->foreign('idrrhh')->references('id')->on('tbl_rrhh');
            $table->bigInteger('idrole')->unsigned();
            $table->foreign('idrole')->references('id')->on('tbl_role');
            $table->string('n_document', 15);
            $table->string('email', 60);
            $table->text('password');
            $table->text('encrypted_password');
            $table->text('token_epn')->nullable();
            $table->enum('status_notification_push', ['inactive', 'active'])->default('inactive');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user');
    }
};
