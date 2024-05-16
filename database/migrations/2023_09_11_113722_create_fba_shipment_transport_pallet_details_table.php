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
        Schema::create('fba_shipment_transport_pallet_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fba_shipment_id')->unsigned()->comment('fba_shipments table ID');
            $table->integer('pallet_height')->default(72);
            $table->integer('pallet_weight')->default(0);
            $table->integer('number_of_pallet')->default(0);
            $table->integer('pallet_total_weight')->nullable();
            $table->tinyInteger('is_stackable')->default(0)->comment('1=Yes 0=No');
            $table->integer('package_length')->default(25);
            $table->integer('package_width')->default(8);
            $table->integer('package_height')->default(14);
            $table->integer('package_weight')->default(0);
            $table->integer('number_of_package')->default(0);
            $table->integer('package_total_weight')->nullable();
            $table->tinyInteger('is_pallet')->nullable()->comment('1=Yes 0=No');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('fba_shipment_id')->references('id')->on('fba_shipments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_transport_pallet_details');
    }
};
