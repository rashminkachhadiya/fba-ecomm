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
        Schema::create('fba_shipment_transport_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fba_shipment_id')->unsigned()->comment('fba_shipments table ID');
            $table->enum('shipping_method', ['SP', 'LTL']);
            $table->tinyInteger('shipping_carrier')->default(1)->comment('1=Amazon-Partnered Carrier 0=Non Amazon-Partnered Carrier');
            $table->string('other_shipping_carrier')->nullable();
            $table->string('pro_number')->nullable();
            $table->string('tracking_id')->nullable();
            $table->date('freight_ready_date')->nullable();
            $table->integer('seller_declared_value')->nullable();
            $table->integer('number_boxes')->nullable();
            $table->enum('transport_status', ['WORKING', 'ESTIMATING', 'ESTIMATED','ERROR_ON_ESTIMATING','CONFIRMING','CONFIRMED','ERROR_ON_CONFIRMING','VOIDING','VOIDED','ERROR_IN_VOIDING','ERROR'])->nullable();
            $table->string('error_code')->nullable()->comment('API error code');
            $table->text('error_msg')->nullable()->comment('API error msg.');
            $table->text('error_description')->nullable()->comment('API error msg description.');
            $table->tinyInteger('is_added_from')->default(0)->comment('0=our system, 1=getting from amazon');
            $table->double('estimate_shipping_cost',14,2)->nullable();
            $table->string('shipping_currency')->nullable();
            $table->dateTime('confirm_deadline')->nullable();
            $table->dateTime('void_cost_deadline')->nullable();
            $table->tinyInteger('system_status')->default(0)->comment('0=Save, 1=Put on amazon, 2=Estimated, 3=Confirmed, 4=Voided');
            
            $table->timestamps();

            $table->foreign('fba_shipment_id')->references('id')->on('fba_shipments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_transport_details');
    }
};
