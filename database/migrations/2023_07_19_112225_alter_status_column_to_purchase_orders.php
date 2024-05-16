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
        if(!empty(config('constants.po_status')))
        {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->enum('status', array_column(config('constants.po_status'),'title'))->default('Draft')->comment('PO Order status')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', ['Draft','Ready','Sent','Dispatch','Arrived','Receiving','Closed','Cancelled'])->default('Draft')->comment('PO Order status')->change();
        });
    }
};
