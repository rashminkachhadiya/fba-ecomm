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
        //Create Prep Details Table
        if (!Schema::hasTable('fba_prep_details')) {
            Schema::create('fba_prep_details', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->length(20)->nullable(false);
                $table->bigInteger('fba_shipment_id')->unsigned()->nullable()->comment('id of fba_shipments table');
                $table->bigInteger('fba_shipment_item_id')->unsigned()->nullable()->comment('id of fba_shipment_items table');
                $table->integer('done_qty')->nullable();
                $table->integer('discrepancy_qty')->nullable();
                $table->integer('actual_done_qty')->nullable();
                $table->text('discrepancy_note')->nullable();
                $table->tinyInteger('status')->length(4)->default(0)->comment('0=Prep Pending, 1=Prep Progress, 2=Prep Completed');
                $table->timestamps();
            });
        }

        //Create Prep Notes Table
        if (!Schema::hasTable('fba_prep_notes')) {
            Schema::create('fba_prep_notes', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->length(20)->nullable(false);
                $table->string('asin',30)->unique()->nullable();
                $table->text('prep_note')->nullable();
                $table->text('warehouse_note')->nullable();
                $table->integer('asin_weight')->nullable();
                $table->string('module_name', 255)->nullable();
                $table->timestamps();
            });
        }

        //Create FBA Shipment Item Prep Details Table
        if (!Schema::hasTable('fba_shipment_item_prep_details')) {
            Schema::create('fba_shipment_item_prep_details', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('fba_shipment_item_id')->nullable()->comment('id of fba_shipment_items table');
                $table->bigInteger('fba_shipment_plan_id')->nullable()->comment('id of shipment_plans table');
                $table->bigInteger('fba_shipment_id')->unsigned()->nullable()->comment('id of fba_shipments table');
                $table->tinyInteger('prep_instruction')->nullable()->comment('0=Polybagging, 1=BubbleWrapping, 2=Taping, 3=BlackShrinkWrapping, 4=Labeling, 5=HangGarment, 6=SetCreation, 7=Boxing, 8=RemoveFromHanger, 9=Debundle, 10=SuffocationStickering, 11=CapSealing, 12=SetStickering, 13=BlankStickering, 14=NoPrep');
                $table->string('prep_instruction_value')->nullable()->comment('prep instruction fetch from amazon');
                $table->tinyInteger('prep_owner')->nullable()->comment('0=AMAZON, 1=SELLER');
                $table->timestamps();

                $table->foreign('fba_shipment_id')->references('id')->on('fba_shipments')->onDelete('cascade');
            });
        }

        //Create Prep Box Details Table
        if (!Schema::hasTable('fba_prep_box_details')) {
            Schema::create('fba_prep_box_details', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->length(20)->nullable(false);
                $table->bigInteger('fba_shipment_item_id')->unsigned()->nullable()->comment('id of fba_shipment_items table');
                $table->string('fba_shipment_id',50)->nullable()->comment('id of fba_shipments table');
                $table->bigInteger('box_number')->unsigned()->nullable();
                $table->integer('units')->nullable();
                $table->date('expiry_date')->nullable();
                $table->tinyInteger('is_printed_type')->nullable()->comment('1=2d box label, 2=3in1 box label');
                $table->integer('created_by')->nullable();
                $table->timestamps();
            });
        }

        //Create Prep Logs Table
        if (!Schema::hasTable('prep_logs')) {
            Schema::create('prep_logs', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->length(20)->nullable(false);
                $table->bigInteger('fba_shipment_id')->nullable()->comment('id of fba_shipments table');
                $table->bigInteger('fba_shipment_item_id')->nullable()->comment('id of fba_shipment_items table');
                $table->string('type',50)->nullable()->comment('1=>prep-listing, 2=>prep-detail');
                $table->string('field_type',50)->nullable();
                $table->string('title', 255)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        //Create Prep Note Logs Table
        if (!Schema::hasTable('prep_note_logs')) {
            Schema::create('prep_note_logs', function (Blueprint $table) {
                $table->bigInteger('id')->autoIncrement()->length(20)->nullable(false);
                $table->string('type',20)->nullable();
                $table->string('asin',30)->nullable();
                $table->string('title', 255)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Drop Prep Details Table
        Schema::dropIfExists('fba_prep_details');

        //Drop Prep Notes Table
        Schema::dropIfExists('fba_prep_notes');

        //Drop FBA Shipment Item Prep Details Table
        Schema::dropIfExists('fba_shipment_item_prep_details');

        //Drop FBA Prep Box Details Table
        Schema::dropIfExists('fba_prep_box_details');

        //Drop Prep Logs Table
        Schema::dropIfExists('prep_logs');

        //Drop Prep Note Logs Table
        Schema::dropIfExists('prep_note_logs');

    }
};
