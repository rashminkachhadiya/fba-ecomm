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
        Schema::create('po_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->onUpdate('cascade')->onDelete('cascade')->comment('PO ID from purchase_orders table');
            $table->foreignId('po_item_id')->constrained('purchase_order_items')->onUpdate('cascade')->onDelete('cascade')->comment('PO Item ID from purchase_order_items table');
            $table->enum('reason', array_column(config('constants.discrepancy_reason'),'title'))->comment('Reason for discrepancy');
            $table->text('discrepancy_note')->nullable()->comment('Discrepancy Note');
            $table->integer('discrepancy_count')->comment('count');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_discrepancies');
    }
};
