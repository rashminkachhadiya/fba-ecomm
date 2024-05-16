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
            $table->tinyInteger('box_type')->default(0)->comment('0 = Regular Box, 1 = Multi Sku Box')->after('box_number');
            $table->decimal('box_width', 8, 2)->default(0)->after('box_type');
            $table->decimal('box_height', 8, 2)->default(0)->after('box_width');
            $table->decimal('box_length', 8, 2)->default(0)->after('box_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fba_prep_box_details', function (Blueprint $table) {
            $table->dropColumn(['box_type','box_width','box_height','box_length']);
        });
    }
};
