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
        Schema::create('amazon_cron_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->default(NULL)->references('id')->on('stores');
            $table->string('cron_name', 100)->nullable();
            $table->string('cron_type', 100)->nullable();
            $table->string('cron_param', 100)->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_cron_logs');
    }
};
