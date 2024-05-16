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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->double('unit_price',14,2)->default(0)->after('product_id');
            $table->double('total_price',14,2)->default(0)->after('order_qty');
            $table->integer('received_qty')->default(0)->after('total_price');
            $table->double('received_price',14,2)->default(0)->after('received_qty');
            $table->integer('difference_qty')->default(0)->after('received_price');
            $table->double('difference_price',14,2)->default(0)->after('difference_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
            $table->dropColumn('total_price');
            $table->dropColumn('received_qty');
            $table->dropColumn('received_price');
            $table->dropColumn('difference_qty');
            $table->dropColumn('difference_price');
        });
    }
};
