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
        Schema::create('fba_shipment_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fba_shipment_id')->nullable()->comment('id of fba_shipments table');
            $table->bigInteger('fba_shipment_item_id')->nullable()->comment('id of fba_shipment_items table');
            $table->tinyInteger('type')->nullable()->comment('1=update qty, 2=put transport detail, 3=mark as shipped, 4=update shipment name');
            $table->tinyInteger('action_from')->nullable()->comment('1=From shipment, 2=From prep');
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_logs');
    }
};
