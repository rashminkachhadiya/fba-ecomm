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
        Schema::create('shopify_product_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shopify_product_id')->unsigned()->nullable()->comment('Primary key of shopify_products table');
            $table->integer('position')->unsigned()->nullable();
            $table->double('width',8,2)->nullable();
            $table->double('height',8,2)->nullable();
            $table->string('image_url',250)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_product_images');
    }
};
