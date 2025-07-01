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
        Schema::create('tbl_submenu_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('link')->nullable();
            $table->text('icon')->nullable();
            $table->boolean('submenu')->default(false);
            $table->boolean('showSubRoute')->default(false);
            $table->text('idsrole');
            $table->enum('status', ['inactive', 'active', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_submenu_items');
    }
};
