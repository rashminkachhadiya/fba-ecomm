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
        Schema::table('amazon_products', function (Blueprint $table) {
            $table->double('buybox_price', 14, 2)->nullable()->after('price');
            $table->double('buybox_referral_fees', 14, 2)->nullable()->after('referral_fees');
            $table->tinyInteger('is_buybox_fetch')->default(0)->after('is_product_detail_updated');
            $table->timestamp('last_byubox_price_updated')->nullable()->after('listing_created_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_products', function (Blueprint $table) {
            $table->dropColumn('buybox_price');
            $table->dropColumn('buybox_referral_fees');
            $table->dropColumn('is_buybox_fetch');
            $table->dropColumn('last_byubox_price_updated');
        });
    }
};
