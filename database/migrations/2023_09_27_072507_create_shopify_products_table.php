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
        Schema::create('shopify_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('store_id')->unsigned()->nullable();
            $table->bigInteger('product_id')->unsigned()->nullable();
            $table->bigInteger('parent_id')->nullable()->comment('id of itself table');
            $table->bigInteger('unique_id')->nullable()->unique()->comment('The unique numeric identifier for the product.');
            $table->bigInteger('variant_unique_id')->nullable()->comment('Unique if of normal product.');
            $table->string('sku',120)->nullable()->comment('SKU of the product from the Shopify Marketplace');
            $table->text('upc')->nullable()->comment('Universal Product Code');
            $table->string('title',500)->nullable()->comment('The name of the product.');
            $table->text('description')->nullable()->comment('The description of the product, complete with HTML formatting.');
            $table->double('price',14,2)->nullable()->comment('price of product');
            $table->string('currency',10)->nullable();
            $table->double('shipping_price',12,2)->nullable()->comment('Shipping price for the product');
            $table->integer('quantity')->length(11)->unsigned()->nullable()->comment('quantity of product');
            $table->text('main_image')->nullable()->comment('Main image of the product');
            $table->string('vendor',100)->nullable()->comment('The name of the vendor of the product.');
            $table->string('product_type',50)->nullable()->comment('A categorization that a product can be tagged with, commonly used for filtering and searching.');
            $table->string('handle')->nullable()->comment('A human-friendly unique string for the Product automatically generated from its title.');
            $table->enum('published_scope', ['global', 'web'])->default('global')->comment('web: The product is not published to Point of Sale. global: The product is published to Point of Sale.');
            $table->enum('is_variation', ['0', '1', '2'])->default('0')->comment('0-Normal product, 1-variation product, 2-parent product');
            $table->string('options1',100)->nullable()->comment('It means variation only.');
            $table->string('options2',100)->nullable()->comment('It means variation only.');
            $table->string('options3',100)->nullable()->comment('It means variation only.');
            $table->integer('position')->nullable()->unsigned()->comment('The order of the product variant in the list of product variants.');
            $table->integer('grams')->nullable()->unsigned()->comment('The weight of the product variant in grams.');
            $table->string('taxable',20)->nullable();
            $table->double('weight',8,2)->nullable()->comment('The weight of the product variant in the unit system specified with weight_unit.');
            $table->string('weight_unit',20)->nullable()->comment('The weight_unit can be either "g", "kg, "oz", or "lb".');
            $table->integer('old_inventory_quantity')->nullable()->unsigned()->comment('The weight of the product variant in grams.');
            $table->string('inventory_item_id',25)->nullable()->comment('Inventory_item_id.');
            $table->string('location_id',20)->nullable()->comment('Location id at shopify.');
            $table->char('barcode', 13)->nullable()->comment('The barcode, UPC or ISBN number for the product.');
            $table->double('compare_at_price',12,0)->nullable()->unsigned()->comment('The competitors price for the same item.');
            $table->string('fulfillment_service',50)->nullable()->comment('The service which is handling fulfillment. Valid values are: manual, gift_card, or the handle of a FulfillmentService.');
            $table->string('inventory_management',50)->nullable()->comment('Specifies whether or not Shopify tracks the number of items in stock for this product variant.');
            $table->integer('requires_shipping')->nullable()->unsigned()->comment('Specifies whether or not a customer needs to provide a shipping address when placing an order for this product variant.');
            $table->string('template_suffix',100)->nullable()->comment('The suffix of the liquid template being used. By default, the original template is called product.liquid, without any suffix. Any additional templates will be: product.suffix.liquid.');
            $table->string('tags',200)->nullable()->comment('A categorization that a product can be tagged with, commonly used for filtering and searching.');
            $table->enum('product_tier', ['Tier A', 'Tier B', 'Tier C', 'Tier D'])->nullable();
            $table->string('inventory_policy',100)->nullable()->comment('Specifies whether or not customers are allowed to place an order for a product variant when its out of stock.');
            $table->enum('is_posted_status', ['0', '1'])->default('0')->comment('0-Not posted, 1-Posted');
            $table->enum('is_updated_in_product_master', ['0', '1'])->default('0')->comment('0-NO, 1-YES');
            $table->dateTime('shopify_created_at')->nullable()->comment('The date and time when the product was created. The API returns this value in ISO 8601 format.');
            $table->dateTime('shopify_updated_at')->nullable()->comment('The date and time when the product was last modified.');
            $table->dateTime('shopify_published_at')->nullable()->comment('The date and time when the product was published to the Online Store channel.');
            $table->enum('is_enabled', ['0', '1'])->default('1')->comment('0-Product is disabled, 1- Product is enabled');
            $table->enum('is_brand_process', ['0', '1'])->default('0')->comment('0-pending,1-success,2-error');
            $table->enum('is_safety_quantity_ignored', ['0', '1'])->nullable()->comment('0- safety qty not ignored, 1- Safety qty ignored');
            $table->dateTime('last_qty_posted_date')->nullable()->comment('Last posted qty date time');
            $table->integer('last_qty_posted')->nullable()->unsigned()->comment('Last qty posted');
            $table->tinyInteger('is_merge')->default('0')->comment('0 = not merged, 1= merged');
            $table->timestamp('last_modified')->nullable(false)->useCurrent()->useCurrentOnUpdate()->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
            $table->index('title');
            $table->index('sku');
            $table->index('parent_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_products');
    }
};
