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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->onUpdate('cascade')->onDelete('cascade')->comment('PO ID from purchase_orders table');
            $table->foreignId('supplier_product_id')->constrained('supplier_products')->onUpdate('cascade')->onDelete('cascade')->comment('Supplier ID from Supplier Products table');
            $table->foreignId('supplier_id')->constrained('suppliers')->onUpdate('cascade')->onDelete('cascade')->comment('Supplier ID from suppliers table');
            $table->foreignId('product_id')->constrained('amazon_products')->onUpdate('cascade')->onDelete('cascade')->comment('Product ID from amazon_products table');
            $table->integer('order_qty')->default(0);
            $table->double('total_product_cost', 14,2)->default(0);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
