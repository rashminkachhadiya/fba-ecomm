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
        Schema::table('shipment_plans', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('shipment_products', function (Blueprint $table) {
            $table->softDeletes();
        });

        if(Schema::hasColumn('amazon_products', 'pack_of'))
        {
            Schema::table('amazon_products', function (Blueprint $table) {
                $table->bigInteger('pack_of')->default(1)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_plans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('shipment_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        if(Schema::hasColumn('amazon_products', 'pack_of'))
        {
            Schema::table('amazon_products', function (Blueprint $table) {
                $table->unsignedInteger('pack_of')->default(1)->change();
            });
        }
    }
};
