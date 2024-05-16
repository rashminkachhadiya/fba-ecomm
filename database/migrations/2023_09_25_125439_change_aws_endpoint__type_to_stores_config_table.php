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
        Schema::table('store_configs', function (Blueprint $table) {
            $table->string('aws_endpoint', 150)->change()->nullable()->comment('Aws Region we will use the the SQS service for this marketplace');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_configs', function (Blueprint $table) {
            $table->string('aws_endpoint', 150)->change()->nullable()->comment('Aws Region we will use the the SQS service for this marketplace');
        });
    }
};
