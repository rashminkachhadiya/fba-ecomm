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
        Schema::create('shopify_order_details', function (Blueprint $table) {
            $table->id()->comment('Primary Key (Auto Increment)');
            $table->unsignedBigInteger('shopify_order_id')->comment('Primary key of shopify_orders table');
            $table->mediumText('discount_codes')->nullable()->comment('Applicable discount codes that can be applied to the order. all discount propties such as code , amount , type stored in serialized form');
            $table->mediumText('note_attributes')->nullable()->comment('Extra information that is added to the order. different attribute store in serialized form');
            $table->mediumText('tax_lines')->nullable()->comment('List of tax line objects, each of which details the taxes applicable to this shipping line.');
            $table->mediumText('shipping_lines')->nullable()->comment('Different shipping propties used stored in serialized form	');
            $table->mediumText('fulfillments_details')->nullable()->comment('All fullfillment detail stored in serialized form');
            $table->mediumText('refund_details')->nullable()->comment('All refund detail stored in serialized form');
            $table->mediumText('client_details')->nullable()->comment('All client details such as browser IP and screen width');
            $table->mediumText('payment_details')->nullable()->comment('All payment details stored in serialized form');
            $table->mediumText('shopify_customer')->nullable()->comment('All custom details stored in serialized form');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_order_details');
    }
};
