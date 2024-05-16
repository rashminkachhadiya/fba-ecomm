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
        Schema::table('fba_prep_box_details', function (Blueprint $table) {
            $table->string('sku', 30)->after('fba_shipment_item_id');
            $table->text('main_image')->after('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fba_prep_box_details', function (Blueprint $table) {
            $table->dropColumn(['sku','main_image']);
        });
    }
};
