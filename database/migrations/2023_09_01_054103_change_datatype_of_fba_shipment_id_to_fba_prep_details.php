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
        Schema::table('fba_prep_details', function (Blueprint $table) {
            $table->string('fba_shipment_id')->change()->nullable()->comment('shipment_id of fba_shipments table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fba_prep_details', function (Blueprint $table) {
            $table->unsignedBigInteger('fba_shipment_id')->nullable()->change()->comment('id of fba_shipments table');
        });
    }
};
