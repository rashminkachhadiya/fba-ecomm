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
            $table->unsignedInteger('threshold_qty')->default(0)->after('suggested_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_velocities', function (Blueprint $table) {
            $table->dropColumn('threshold_qty');
        });
    }
};
