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
        // Create warehouse table
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->string('warehouse_name')->default('Sebago Foods');
            $table->text('address')->nullable();

            $table->integer('created_by')->nullable();

            $table->timestamps();
        });

        // Create shipment plans table
        Schema::create('shipment_plans', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_id')->length(11)->nullable()->unsigned()->comment('purchase_orders table ID');
            $table->bigInteger('store_id')->nullable()->unsigned()->comment('id of stores table')->default(1);
            $table->integer('warehouse_id')->nullable()->unsigned()->comment('id of warehouses table - ship from warehouse id');
            $table->string('destination_country')->default('CA');
            $table->string('plan_name')->nullable();
            $table->enum('box_content', config('constants.box_content'))->default('2D_BARCODE');
            $table->enum('prep_preference', config('constants.prep_preference'))->default('SELLER_LABEL');
            $table->enum('packing_details', config('constants.packing_details'))->default('Individual Pack');
            $table->enum('plan_status', config('constants.plan_status'))->default('Draft');
            $table->tinyInteger('status')->default(0)->comment('0=Draft, 1=Finalize, 2=Create ShipmentPlan Called, 3=Ready_for_create_shipment, 4=Shipment Created at amaozn, 5=Error, 6=Cancelled');
            $table->text('remark')->nullable();
            $table->tinyInteger('is_added_from_seller_central')->default(0)->comment('0=No, 1=Yes');
            $table->tinyInteger('is_plan')->default(0)->comment('0=No, 1=Yes');
            $table->dateTime('draft_shipment_plan_updated_at')->nullable();
            $table->integer('created_by')->nullable();

            $table->timestamps();
        });

        // Create shipment products table
        Schema::create('shipment_products', function (Blueprint $table) {
            $table->id();

            // Shipment Plan ID
            $table->bigInteger('shipment_plan_id')->length(11)->unsigned()->comment('Shipment Plan table ID');
            $table->foreign('shipment_plan_id')->references('id')->on('shipment_plans')->onDelete('cascade');

            // Amazon product ID
            $table->bigInteger('amazon_product_id')->length(11)->unsigned()->comment('Aamzon product table ID');
            $table->foreign('amazon_product_id')->references('id')->on('amazon_products')->onDelete('cascade');

            $table->string('title')->nullable();
            $table->string('asin')->nullable();
            $table->string('sku')->nullable();
            $table->integer('sellable_asin_qty');

            $table->unique(['shipment_plan_id', 'sku']);
            $table->unique(['shipment_plan_id', 'amazon_product_id']);

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        // Create FBA shipment table
        Schema::create('fba_shipments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('store_id')->unsigned()->nullable()->comment('ID of stores table');
            $table->bigInteger('fba_shipment_plan_id')->unsigned()->nullable()->comment('ID of shipment_plans table');
            $table->string('shipment_id')->nullable()->unique()->comment('Shipment Id from amazon');
            $table->string('shipment_name')->nullable()->comment('ShipmentName from amazon');
            $table->string('destination_fulfillment_center_id')->nullable()->comment('DestinationFulfillmentCenterId from amazon');
            $table->tinyInteger('shipment_status')->nullable()->comment('0=WORKING, 1=READY_TO_SHIP, 2=SHIPPED, 3=RECEIVING, 4=CANCELLED, 5=DELETED, 6=CLOSED, 7=ERROR, 8=IN_TRANSIT, 9=DELIVERED, 10=CHECKED_IN');
            $table->tinyInteger('is_update')->nullable()->comment('0=nothing, 1=update, 2=success, 3=error');
            $table->text('remark')->nullable()->comment('API response, error msg etc.');
            $table->tinyInteger('label_prep_type')->nullable()->comment('0=NO_LABEL, 1=SELLER_LABEL, 2=AMAZON_LABEL');
            $table->tinyInteger('are_cases_required')->nullable()->comment('0=No, 1=Yes');
            $table->tinyInteger('box_contents_source')->nullable()->comment('0=NONE, 1=FEED, 2=2D_BARCODE, 3=INTERACTIVE');
            $table->string('ship_from_addr_name')->nullable()->comment('Name from ShipFromAddress');
            $table->string('ship_from_addr_line1')->nullable()->comment('AddressLine1 from ShipFromAddress');
            $table->string('ship_from_addr_district_county')->nullable()->comment('DistrictOrCounty from ShipFromAddress');
            $table->string('ship_from_addr_city')->nullable()->comment('City from ShipFromAddress');
            $table->string('ship_from_addr_state_province_code')->nullable()->comment('StateOrProvinceCode from ShipFromAddress');
            $table->string('ship_from_addr_country_code')->nullable()->comment('CountryCode from ShipFromAddress');
            $table->string('ship_from_addr_postal_code')->nullable()->comment(' PostalCode from ShipFromAddress');
            $table->string('ship_to_addr_name')->nullable()->comment('Name from ShipToAddress');
            $table->string('ship_to_addr_line1')->nullable()->comment('AddressLine1 from ShipToAddress');
            $table->string('ship_to_addr_line2')->nullable()->comment('AddressLine2 from ShipToAddress');
            $table->string('ship_to_addr_district_county')->nullable()->comment('DistrictOrCounty from ShipToAddress');
            $table->string('ship_to_addr_city')->nullable()->comment('City from ShipToAddress');
            $table->string('ship_to_addr_state_province_code')->nullable()->comment('StateOrProvinceCode from ShipToAddress');
            $table->string('ship_to_addr_country_code')->nullable()->comment('CountryCode from ShipToAddress');
            $table->string('ship_to_addr_postal_code')->nullable()->comment('PostalCode from ShipToAddress');
            $table->integer('est_box_content_fee_total_unit')->nullable()->comment('EstimatedBoxContentsFee -> TotalUnits');
            $table->decimal('est_box_content_fee_per_unit', 12, 4)->nullable()->comment('EstimatedBoxContentsFee -> FeePerUnit -> Value');
            $table->tinyInteger('est_box_content_fee_currency_code')->nullable()->comment('0=USD, 1=GBP, 2=CAD, EstimatedBoxContentsFee -> FeePerUnit -> CurrencyCode');
            $table->decimal('est_box_content_total_fee', 12, 4)->nullable()->comment('EstimatedBoxContentsFee -> TotalFee -> Value');
            $table->tinyInteger('est_box_content_total_fee_currency_code')->nullable()->comment('0=USD, 1=GBP, 2=CAD, EstimatedBoxContentsFee -> TotalFee -> CurrencyCode');
            $table->tinyInteger('is_items_fetched')->nullable()->comment('0=pending, 1=processed, 2=processed but items need to updated');
            $table->tinyInteger('shipment_created_from')->default(1)->comment('1=our system, 2=from amazon');
            $table->tinyInteger('is_approved')->nullable()->comment('0=No, 1=Yes');
            $table->tinyInteger('is_pallet_label_printed')->nullable()->comment('0=No, 1=Yes');
            $table->integer('no_pallet_label')->nullable()->comment();
            $table->tinyInteger('prep_status')->default(0)->comment('0 => Prep Pending, 1 => Prep Progress, 2 => Prep Completed');
            $table->tinyInteger('has_transport_detail')->default(0)->comment('0=no, 1=yes');
            $table->bigInteger('shipping_schedule_id')->nullable()->comment('id of shipping_schedules table');
            $table->bigInteger('added_by_shipping_schedule')->nullable()->comment('Who associated shipment to shipping_schedule');
            $table->boolean('is_shipment_appointed')->default(false);
            $table->tinyInteger('is_shipment_id_expired')->default(0)->comment('0=No, 1=Yes');
            $table->boolean('is_receiving_item_fetched')->default(0)->comment('0=no, 1=yes');
            $table->timestamps();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
        });

        // Create FBA shipment items table
        Schema::create('fba_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('store_id')->unsigned()->nullable()->comment('ID of stores table');
            $table->bigInteger('fba_shipment_plan_id')->unsigned()->nullable()->comment('ID of shipment_plans table');
            $table->bigInteger('fba_shipment_id')->unsigned()->nullable()->comment('ID of fba_shipments table');
            $table->foreign('fba_shipment_id')->references('id')->on('fba_shipments')->onDelete('cascade');
            $table->bigInteger('fba_shipment_product_id')->nullable()->comment('ID of shipment_products table');
            $table->string('shipment_id')->nullable()->comment('ShipmentId from amazon');
            $table->string('seller_sku')->nullable()->comment('SellerSKU from amazon');
            $table->string('fulfillment_network_sku')->nullable()->comment('FulfillmentNetworkSKU from amazon');
            $table->integer('quantity')->nullable()->comment('Quantity to be shipped');
            $table->integer('quantity_shipped')->nullable()->comment('QuantityShipped from amazon');
            $table->integer('quantity_received')->nullable()->comment('QuantityReceived from amazon');
            $table->integer('quantity_in_case')->nullable()->comment('QuantityInCase from amazon');
            $table->date('release_date')->nullable()->comment('ReleaseDate from amazon');
            $table->tinyInteger('skus_prepped')->default('0')->comment('0 => Prep Pending, 1 => Prep Progress, 2 => Prep Completed');
            $table->tinyInteger('is_quantity_updated')->default(0)->comment('0=Not updated, 1=Updated');
            $table->integer('original_quantity_shipped')->nullable();
            $table->text('response')->nullable();
            $table->tinyInteger('is_validated')->default('0')->comment('0 => Not Validated, 1 => Validated');
            $table->timestamps();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop warehouse table
        Schema::dropIfExists('warehouses');

        // Drop shipment plans table
        Schema::dropIfExists('shipment_plans');

        // Drop shipment products table
        Schema::dropIfExists('shipment_products');

        // Drop fba shipment table
        Schema::dropIfExists('fba_shipments');

        // Drop fba shipment items table
        Schema::dropIfExists('fba_shipment_items');
    }
};
