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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('supplier_lead_time')->default(0)->comment('How long does it take from the time you place an order with your supplier until the items reach your warehouse');
            $table->unsignedInteger('day_stock_holdings')->default(0)->comment('Day Stock Holding is the number of days you need stock in amazon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
