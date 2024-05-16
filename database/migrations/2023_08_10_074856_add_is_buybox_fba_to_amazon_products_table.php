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
            $table->tinyInteger('is_buybox_fba')->default(1)->after('pack_of');
            $table->string('buybox_seller_id')->nullable()->after('pack_of');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_products', function (Blueprint $table) {
            $table->tinyInteger('is_buybox_fba')->default(1)->after('pack_of');
            $table->string('buybox_seller_id')->nullable()->after('pack_of');
        });
    }
};
