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
        Schema::create('multi_skus_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id', 150);
            $table->unsignedBigInteger('fba_shipment_item_id')->comment('id of fba shipment items table');
            $table->string('seller_sku', 150);
            $table->integer('sellable_units')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_skus_boxes');
    }
};
