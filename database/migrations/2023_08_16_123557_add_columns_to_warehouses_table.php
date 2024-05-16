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
        if (Schema::hasColumn('warehouses', 'warehouse_name'))
        {            
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('warehouse_name');
            });
        }

        if (Schema::hasColumn('warehouses', 'address'))
        {            
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('name',50)->after('id');
            $table->string('address_1',180)->after('name');
            $table->string('address_2',60)->after('address_1')->nullable();
            $table->string('country',25)->after('address_2')->nullable();
            $table->string('city',30)->after('country');
            $table->string('state_or_province_code',50)->after('city');
            $table->char('country_code',5)->after('state_or_province_code');
            $table->string('postal_code',30)->after('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['name','address_1','address_2','country','city','state_or_province_code','country_code','postal_code']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('warehouse_name')->default('Sebago Foods')->after('id');
            $table->text('address')->nullable()->after('warehouse_name');
        });
    }
};
