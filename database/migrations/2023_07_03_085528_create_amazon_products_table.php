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
        Schema::create('amazon_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('sku',120)->nullable()->unique();
            $table->string('fnsku',120)->nullable();
            $table->char('asin',30)->nullable();
            $table->text('upc')->nullable();
            $table->string('title',500)->nullable();
            $table->text('description')->nullable();
            $table->double('price',14,2)->nullable();
            $table->integer('qty')->length(11)->nullable();
            $table->integer('afn_reserved_quantity')->length(11)->nullable();
            $table->integer('afn_unsellable_quantity')->length(11)->nullable();
            $table->integer('afn_inbound_working_quantity')->length(11)->nullable();
            $table->integer('afn_inbound_shipped_quantity')->length(11)->nullable();
            $table->integer('afn_inbound_receiving_quantity')->length(11)->nullable();
            $table->text('main_image')->nullable();
            $table->float('package_height')->nullable();
            $table->float('package_length')->nullable();
            $table->float('package_weight')->nullable();
            $table->float('package_width')->nullable();
            $table->integer('package_quantity')->length(11)->nullable();
            $table->double('item_height', 5, 2)->nullable();
            $table->double('item_length', 5, 2)->nullable();
            $table->double('item_width', 5, 2)->nullable();
            $table->decimal('item_weight', 5, 2)->nullable();
            $table->tinyInteger('is_hazmat')->default(0);
            $table->tinyInteger('if_fulfilled_by_amazon')->default(0);
            $table->tinyInteger('is_active')->default(0);
            $table->tinyInteger('is_product_detail_updated')->default(0);
            $table->timestamp('listing_created_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('title');
            $table->index('asin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_products');
    }
};
