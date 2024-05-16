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
            $table->unsignedInteger('wh_qty')->default(0)->comment('warehouse qty')->after('qty');
            $table->unsignedInteger('reserved_qty')->default(0)->after('wh_qty');
            $table->unsignedInteger('sellable_units')->default(0)->after('reserved_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_products', function (Blueprint $table) {
            $table->dropColumn(['wh_qty','reserved_qty','sellable_units']);
        });
    }
};
