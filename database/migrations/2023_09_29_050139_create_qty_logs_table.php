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
        Schema::create('qty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amazon_product_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('previous_qty')->default(0);
            $table->unsignedInteger('updated_qty')->default(0);
            $table->string('comment')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qty_logs');
    }
};
