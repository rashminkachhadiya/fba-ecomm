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
        Schema::create('amazon_report_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('store_id')->nullable();
            $table->string('report_type')->nullable();
            $table->bigInteger('request_id')->nullable();
            $table->tinyInteger('is_processed')->nullable();
            $table->datetime('requested_date')->nullable();
            $table->datetime('processed_date')->nullable();
            $table->integer('cut_off_time')->default(3000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_report_logs');
    }
};
