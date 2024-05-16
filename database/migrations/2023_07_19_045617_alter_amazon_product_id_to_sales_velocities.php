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
        Schema::table('sales_velocities', function (Blueprint $table) {
            $table->unique('amazon_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_velocities', function (Blueprint $table) {
            $table->dropUnique('sales_velocities_amazon_product_id_unique');
        });
    }
};
