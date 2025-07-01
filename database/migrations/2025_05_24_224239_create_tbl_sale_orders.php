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
        Schema::create('tbl_sale_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->decimal('global_discount', 16, 4);
            $table->decimal('total_amount', 16, 4);
            $table->foreignId('patient_id')->constrained('tbl_user')->onDelete('cascade');
            $table->morphs('order');
            $table->string('detail')->nullable();
            $table->string('extra_details')->nullable();
            $table->string('payment_status', 15)->default('Pendiente');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_sale_orders');
    }
};
