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
        Schema::create('shopify_order_items', function (Blueprint $table) {
            $table->id()->comment('Primary Key (Auto Increment)');
            $table->bigInteger('shopify_order_id')->unsigned()->comment('Primary key of shopify_orders table');
            $table->bigInteger('shopify_item_id')->unsigned()->nullable()->comment('The id of the shopify line item');
            $table->bigInteger('variant_id')->unsigned()->nullable()->comment('The shopify id of the product variant');
            $table->string('title', 500)->nullable()->comment('The title of the product');
            $table->unsignedInteger('quantity')->nullable()->comment('The number of items in the order');
            $table->unsignedDecimal('item_price', 12, 2)->nullable()->comment('The number of items in the order');
            $table->unsignedDecimal('item_total_price', 12, 2)->nullable()->comment('Selling price of total quantity of the order item');
            $table->unsignedDecimal('item_tax', 12, 2)->nullable()->comment('Item tax price of single quantity of the order item (derived)');
            $table->unsignedDecimal('item_total_tax', 12, 2)->nullable()->comment('Item tax price of total quantity of the order item');
            $table->unsignedDecimal('grams', 8, 2)->nullable()->comment('The weight of the item in grams');
            $table->string('sku', 50)->nullable()->comment('The seller SKU of the item');
            $table->string('variant_title', 500)->nullable()->comment('The title of the product variant');
            $table->string('vendor', 100)->nullable()->comment('The name of the supplier of the item');
            $table->string('fulfillment_service', 50)->nullable()->comment('Service provider handling fulfillment');
            $table->bigInteger('product_id')->unsigned()->nullable()->comment('Unique numeric identifier for the product in fulfillment');
            $table->enum('requires_shipping', ['0', '1'])->nullable()->comment('0 - No shipping required, 1 - Shipping required');
            $table->enum('is_taxable', ['0', '1'])->nullable()->comment('0 - Not taxable, 1 - Taxable');
            $table->enum('is_gift_card', ['0', '1'])->nullable()->comment('0 - Not a gift card, 1 - Gift card');
            $table->string('name', 250)->nullable()->comment('The name of the product variant');
            $table->string('variant_inventory_management', 50)->nullable()->comment('Name of the inventory management system');
            $table->mediumText('properties')->nullable()->comment('Custom information for an item stored in serialized form');
            $table->enum('product_exists', ['0', '1'])->nullable()->comment('0 - Product does not exist, 1 - Product exists');
            $table->unsignedInteger('fulfillable_quantity')->nullable()->comment('The quantity to fulfill');
            $table->unsignedDecimal('item_discount', 12, 2)->nullable()->comment('Selling price of single quantity of the order item (derived)');
            $table->unsignedDecimal('item_total_discount', 12, 2)->nullable()->comment('Total discount amount applied to this item');
            $table->enum('fulfillment_status', ['shipped', 'partial', 'unshipped'])->nullable()->comment('shipped - Shipped, partial - Partially shipped, unshipped - Not shipped');
            $table->mediumText('tax_lines')->nullable()->comment('List of tax applied to this item stored in serialized form');
            $table->enum('updated', ['0', '1'])->default('0')->comment('0 - Item not updated, 1 - Item updated');
            $table->timestamp('last_modified')->nullable(false)->useCurrent()->useCurrentOnUpdate()->default(\DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('Last modification timestamp');
            $table->timestamps();

            $table->foreign('shopify_order_id')->references('id')->on('shopify_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_order_items');
    }
};
