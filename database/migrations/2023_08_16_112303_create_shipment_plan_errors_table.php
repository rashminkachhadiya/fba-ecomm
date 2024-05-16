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
        Schema::create('shipment_plan_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fba_shipment_id')->comment('fba_shipments table ID');
            $table->string('sku')->nullable();
            $table->string('asin')->nullable();
            $table->string('error_code')->nullable()->comment('API error code');
            $table->string('reason')->nullable()->comment('API error reason');
            $table->text('error_description')->nullable()->comment('API error msg etc.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_plan_errors');
    }
};
