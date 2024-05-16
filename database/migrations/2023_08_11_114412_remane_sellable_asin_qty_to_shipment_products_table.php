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
        if (Schema::hasColumn('shipment_products', 'sellable_asin_qty'))
        {
            Schema::table('shipment_products', function (Blueprint $table) {
                $table->renameColumn('sellable_asin_qty','sellable_unit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_products', function (Blueprint $table) {
            $table->renameColumn('sellable_unit', 'sellable_asin_qty');
        });
    }
};
