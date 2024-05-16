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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onUpdate('cascade')->onDelete('cascade')->comment('Supplier ID from suppliers table');
            // $table->foreignId('supplier_contact_id')->constrained('supplier_contacts')->onUpdate('cascade')->onDelete('cascade')->comment('Contact ID from supplier_contacts table')->nullable();
            $table->string('po_number',120)->nullable();
            $table->date('po_order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', ['Draft','Ready','Sent','Dispatch','Arrived','Receiving','Closed','Cancelled'])->default('Draft')->comment('PO Order status');
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
        Schema::dropIfExists('purchase_orders');
    }
};
