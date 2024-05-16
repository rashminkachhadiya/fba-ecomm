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
        Schema::create('amazon_cron_error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->default(NULL)->references('id')->on('stores');
            $table->unsignedBigInteger('batch_id')->nullable()->index()->comment('Batch id which uniquely identifies one iteration');
            $table->string('module',150)->nullable()->comment('Name of the module where error occured');
            $table->string('submodule',150)->nullable()->comment('Name of the submodule where error occured');
            $table->text("error_content")->nullable()->comment('error description or content in serialize format');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_cron_error_logs');
    }
};
