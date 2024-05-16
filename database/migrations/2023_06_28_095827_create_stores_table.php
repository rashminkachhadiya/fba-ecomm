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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('store_name',64)->comment('Name of the marketplace store');
            $table->foreignId('store_config_id')->constrained('store_configs')->onUpdate('cascade')->onDelete('cascade');
            $table->char('merchant_id', 14)->nullable()->comment('Seller Id - Merchant Id of the user to access the mws services of this marketplace');
            $table->text('refresh_token')->nullable()->comment('Seller-Developer Refresh Token');
            $table->text('access_token')->nullable()->comment('Seller-Developer Access Token');
            $table->string('client_id',100)->nullable()->comment('SP-API APP Client Identifier');
            $table->string('client_secret',100)->nullable()->comment('SP-API APP Client Secret');
            $table->string('aws_access_key_id',50)->nullable()->comment('AWSAccessKeyId of the user to access the aws services of this marketplace');
            $table->string('aws_secret_key',100)->nullable()->comment('AWS Secret Key of the user to access the aws services of this marketplace');
            $table->text('session_token')->nullable();
            $table->string('sts_access_key_id',50)->nullable();
            $table->string('sts_secret_key',100)->nullable();
            $table->string('role_arn',100)->nullable();
            $table->enum('status',[0,1])->default(1)->comment('1= Active, 0= In-active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
