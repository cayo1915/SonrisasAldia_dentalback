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
        Schema::create('tbl_detail_menu_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_menu_items')->unsigned();
            $table->foreign('id_menu_items')->references('id')->on('tbl_menu_items');
            $table->bigInteger('id_submenu_items')->unsigned()->nullable();
            $table->foreign('id_submenu_items')->references('id')->on('tbl_submenu_items');
            $table->bigInteger('id_submenu_items2')->unsigned()->nullable();
            $table->foreign('id_submenu_items2')->references('id')->on('tbl_submenu_items');
            $table->bigInteger('id_submenu_items3')->unsigned()->nullable();
            $table->foreign('id_submenu_items3')->references('id')->on('tbl_submenu_items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_detail_menu_items');
    }
};
