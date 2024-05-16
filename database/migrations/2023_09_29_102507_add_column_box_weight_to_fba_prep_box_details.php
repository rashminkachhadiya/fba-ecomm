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
            $table->decimal('box_weight', 8, 2)->default(0)->after('box_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fba_prep_box_details', function (Blueprint $table) {
            $table->dropColumn('box_weight');
        });
    }
};
