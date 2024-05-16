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
        Schema::create('amazon_order_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('store_id')->nullable()->index();
            $table->string('amazon_order_id', 50)->nullable()->index();
            $table->string('merchant_order_id', 50)->nullable();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->dateTime('purchase_date')->nullable()->index();
            $table->dateTime('last_updated_date')->nullable()->index();
            $table->dateTime('order_date')->nullable()->index();
            $table->string('order_status', 50)->nullable()->index();
            $table->string('fulfillment_channel', 50)->nullable();
            $table->string('sales_channel', 50)->nullable();
            $table->string('order_channel', 50)->nullable();
            $table->string('ship_service_level', 50)->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku', 20)->nullable();
            $table->string('asin', 20)->nullable();
            $table->string('item_status', 50)->nullable();
            $table->integer('quantity')->nullable();
            $table->string('currency', 50)->nullable();
            $table->double('item_price', 12, 3)->nullable();
            $table->double('item_tax', 12, 3)->nullable();
            $table->double('shipping_price', 12, 3)->nullable();
            $table->double('shipping_tax', 12, 3)->nullable();
            $table->double('gift_wrap_price', 12, 3)->nullable();
            $table->double('gift_wrap_tax', 12, 3)->nullable();
            $table->double('item_promotion_discount', 12, 3)->nullable();
            $table->double('ship_promotion_discount', 12, 3)->nullable();
            $table->string('ship_city', 50)->nullable();
            $table->string('ship_state', 50)->nullable();
            $table->string('ship_postal_code', 50)->nullable();
            $table->string('ship_country', 50)->nullable();
            $table->string('promotion_ids', 50)->nullable();
            $table->string('is_business_order', 50)->nullable();
            $table->string('purchase_order_number', 50)->nullable();
            $table->string('price_designation', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_order_reports');
    }
};
