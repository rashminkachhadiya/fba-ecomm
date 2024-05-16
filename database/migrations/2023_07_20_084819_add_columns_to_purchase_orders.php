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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('shipping_id',100)->after('expected_delivery_date')->nullable();
            $table->string('shipping_company', 100)->after('shipping_id')->nullable();
            $table->date('shipping_date')->after('shipping_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_id');
            $table->dropColumn('shipping_company');
            $table->dropColumn('shipping_date');
        });
    }
};
