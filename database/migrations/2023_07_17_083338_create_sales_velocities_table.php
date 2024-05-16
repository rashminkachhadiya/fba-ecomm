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
        Schema::create('sales_velocities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amazon_product_id')->nullable()->default(NULL)->references('id')->on('amazon_products');
            $table->double('ros_2', 14, 2)->comment('Rate of sale in 2 days')->nullable();
            $table->double('ros_7', 14, 2)->comment('Rate of sale in 7 days')->nullable();
            $table->double('ros_30', 14, 2)->comment('Rate of sale in 30 days')->nullable();
            $table->integer('total_units_sold_2')->nullable();
            $table->integer('total_units_sold_7')->nullable();
            $table->integer('total_units_sold_30')->nullable();
            $table->integer('suggested_quantity')->comment('Suggested quantity that should be ordered. Calculated using a formula')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_velocities');
    }
};
