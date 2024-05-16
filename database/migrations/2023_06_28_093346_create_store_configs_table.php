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
        Schema::create('store_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('store_type',['Amazon US','Amazon CA','Shopify'])->comment('Type of the store');
            $table->enum('store_country', ['US', 'CA'])->comment('Store country code');
            $table->string('store_url', 30)->comment('Main URL of the Marketplace');
            $table->char('amazon_marketplace_id', 14)->nullable()->comment('MarketplaceId of respective Amazon Marketplace');
            $table->string('amazon_aws_region', 20)->nullable()->comment('Aws Region we will use the the SQS service for this marketplace');
            $table->string('aws_endpoint', 150)->comment('Aws Region we will use the the SQS service for this marketplace');
            $table->char('store_currency', 3)->comment('Each eBay site maps to a unique eBay global ID.');
            $table->string('store_timezone', 20)->comment('Default timezone of the store');
            // $table->enum('status',[0,1])->default(1)->comment('1 = Active, 0 = Inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_configs');
    }
};
