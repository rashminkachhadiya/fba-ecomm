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
            $table->unsignedInteger('pack_of')->default(1)->after('if_fulfilled_by_amazon');
            $table->unsignedInteger('case_pack')->default(1)->after('pack_of');
            $table->double('inbound_shipping_cost',10,2)->nullable()->after('case_pack');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_products', function (Blueprint $table) {
            $table->dropColumn(['pack_of','case_pack','inbound_shipping_cost']);
        });
    }
};
