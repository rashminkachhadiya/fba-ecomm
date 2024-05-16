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
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->double('buybox_price',10,2)->default(0)->after('threshold_qty');
            $table->double('selling_price',10,2)->default(0)->after('buybox_price');
            $table->double('referral_fees',10,2)->default(0)->after('selling_price');
            $table->double('buybox_referral_fees',10,2)->default(0)->after('referral_fees');
            $table->double('fba_fees',10,2)->default(0)->after('buybox_referral_fees');
            $table->double('buybox_price_profit',10,2)->default(0)->after('fba_fees');
            $table->double('buybox_price_margin',5,2)->default(0)->after('buybox_price_profit');
            $table->double('selling_price_profit',10,2)->default(0)->after('buybox_price_margin');
            $table->double('selling_price_margin',5,2)->default(0)->after('selling_price_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->dropColumn(['buybox_price','selling_price','referral_fees','buybox_referral_fees','fba_fees','buybox_price_profit','buybox_price_margin','selling_price_profit','selling_price_margin']);
        });
    }
};
