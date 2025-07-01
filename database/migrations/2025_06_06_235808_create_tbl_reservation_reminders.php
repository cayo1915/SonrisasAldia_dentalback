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
        Schema::create('tbl_reservation_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('tbl_reservation')->onDelete('cascade');
            $table->timestamp('reminder_time');
            $table->boolean('sent')->default(false);
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_reservation_reminders');
    }
};
