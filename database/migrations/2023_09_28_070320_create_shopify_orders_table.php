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
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->id()->comment('Primary Key (Auto Increment)');
            $table->bigInteger('shopify_unique_id')->nullable()->comment('The unique numeric identifier for the Shopify order, different from order_number');
            $table->unsignedBigInteger('store_id')->comment('ID of the store from the stores');
            $table->unsignedInteger('items_count')->default(0)->comment('Total number of items in the order. Count only, not quantity');
            $table->string('buyer_email', 150)->nullable()->comment('The Shopify customer email address');
            $table->dateTime('order_date')->comment('Date and time of order based on project timestamp');
            $table->dateTime('order_created_date')->nullable()->comment('Date and time when the order was created in Shopify');
            $table->dateTime('order_last_updated_date')->nullable()->comment('Date and time when the order was last modified');
            $table->dateTime('order_processed_date')->nullable()->comment('Date and time when the order was processed');
            $table->dateTime('order_cancelled_date')->nullable()->comment('Date and time when the order was cancelled');
            $table->dateTime('order_closed_date')->nullable()->comment('Date and time when the Shopify order was closed');
            $table->unsignedInteger('shop_number')->nullable()->comment('Numerical identifier unique to the shop');
            $table->string('order_note', 1000)->nullable()->comment('Text of an optional note that a shop owner can attach to the order');
            $table->string('order_token', 50)->nullable()->comment('Unique token identifier for a particular Shopify order');
            $table->enum('is_test_order', ['0', '1'])->nullable()->comment('0 - No test order, 1 - Test order');
            $table->unsignedDecimal('total_price', 12, 2)->nullable()->comment('Sum of all prices of items in the order, including taxes and discounts');
            $table->unsignedDecimal('subtotal_price', 12, 2)->nullable()->comment('Price of the order before shipping and taxes');
            $table->unsignedDecimal('total_weight', 8, 2)->nullable()->comment('Sum of all weights of line items in grams');
            $table->unsignedDecimal('total_tax', 12, 2)->nullable()->comment('Sum of all taxes applied to the order');
            $table->enum('is_taxes_included', ['0', '1'])->nullable()->comment('0 - Taxes not included, 1 - Taxes included in order subtotal');
            $table->string('order_currency', 3)->nullable()->comment('Three-letter code (ISO 4217) for the currency used for payment');
            $table->enum('financial_status', ['authorized', 'pending', 'paid', 'refunded', 'partially_paid', 'partially_refunded', 'voided'])->nullable()->comment('authorized - Only authorized orders, pending - Only pending orders, paid - Only paid orders, refunded - Show only refunded orders, voided - Show only voided orders , partial_refund show only partial refund order , partial_paid order show for partial paid order');
            $table->enum('confirmed', ['0', '1'])->default('0')->comment('0 - Not confirmed, 1 - Confirmed');
            $table->unsignedDecimal('total_discounts', 12, 2)->nullable()->comment('Total amount of discounts applied to the order');
            $table->unsignedDecimal('total_line_items_price', 12, 2)->nullable()->comment('Sum of prices of all items in the order');
            $table->string('cart_token', 50)->nullable()->comment('Unique identifier for a cart attached to the order');
            $table->enum('buyer_accepts_marketing', ['0', '1'])->nullable()->comment('Indicates whether or not the person who placed the order would like to receive email updates from the shop. This is set when checking the "I want to receive occasional emails about new products, promotions and other news" checkbox during checkout. Valid values are 0 and 1');
            $table->string('order_name', 50)->nullable()->comment('Customer\'s order name as a number');
            $table->string('referring_site', 250)->nullable()->comment('Website that the customer clicked on to come to the Shopify shop');
            $table->string('landing_site', 250)->nullable()->comment('Reason why the order was cancelled');
            $table->enum('cancel_reason', ['customer', 'fraud', 'inventory', 'other', 'declined'])->nullable()->comment('The reason why the order was cancelled. customer: The customer changed or cancelled the order. , fraud: The order was fraudulent., inventory: Items in the order were not in inventory., other: The order was cancelled for a reason not in the list');
            $table->unsignedDecimal('total_price_usd', 12, 2)->nullable();
            $table->string('checkout_token', 100)->nullable()->comment('Checkout token');
            $table->string('reference', 100)->nullable();
            $table->string('location_id', 100)->nullable();
            $table->string('source_identifier', 100)->nullable();
            $table->string('source_url', 100)->nullable();
            $table->string('device_id', 250)->nullable();
            $table->string('browser_ip', 50)->nullable()->comment('IP address of the browser used by the customer');
            $table->string('landing_site_ref', 50)->nullable();
            $table->string('shopify_order_number', 50)->nullable()->comment('Unique numeric identifier for the order used by the shop owner and customer');
            $table->string('payment_gateway_names', 50)->nullable()->comment('List of all payment gateways used for the order (serialized)');
            $table->enum('processing_method', ['checkout', 'direct', 'manual', 'offsite', 'express'])->nullable()->comment('Type of payment processing method');
            $table->string('checkout_id', 50)->nullable();
            $table->string('source_name', 250)->nullable();
            $table->enum('fulfillment_status', ['shipped', 'partial', 'unshipped', 'fulfilled', 'restocked'])->nullable()->comment('shipped - Orders that have been shipped, partial - Partially shipped orders, unshipped - Orders that have not yet been shipped, fulfilled = Every line item in the order has been fulfilled., restocked : Every line item in the order has been restocked and the order canceled.');
            $table->string('tags', 250)->nullable()->comment('Tags for filtering and searching');
            $table->string('contact_email', 150)->nullable()->comment('Contact email');
            $table->string('order_status_url', 250)->nullable()->comment('URL pointing to the order status web page');
            $table->string('billing_address_first_name', 50)->nullable()->comment('First name of the person associated with the payment method');
            $table->string('billing_address_last_name', 50)->nullable()->comment('Last name of the person associated with the payment method');
            $table->string('billing_address_company', 50)->nullable()->comment('Company of the person associated with the billing address');
            $table->string('billing_address_address1', 250)->nullable()->comment('Street address of the billing address');
            $table->string('billing_address_address2', 50)->nullable()->comment('Street address (line 2) of the billing address');
            $table->string('billing_address_phone', 15)->nullable()->comment('Phone number at the billing address');
            $table->string('billing_address_city', 50)->nullable()->comment('City of the billing address');
            $table->string('billing_address_zip', 15)->nullable()->comment('ZIP or postal code of the billing address');
            $table->char('billing_address_province_code', 2)->nullable()->comment('Two-letter abbreviation of the state or province of the billing address');
            $table->string('billing_address_province', 50)->nullable()->comment('Name of the state or province of the billing address');
            $table->char('billing_address_country_code', 2)->nullable()->comment('Two-letter code for the country of the billing address');
            $table->string('billing_address_country', 50)->nullable()->comment('Name of the country of the billing address');
            $table->string('billing_address_latitude', 10)->nullable()->comment('Latitude of the billing address');
            $table->string('billing_address_longitude', 10)->nullable()->comment('Longitude of the billing address');
            $table->string('billing_address_name', 100)->nullable()->comment('Full name of the person associated with the payment method');
            $table->string('shipping_address_first_name', 50)->nullable()->comment('First name of the person associated with the shipping address');
            $table->string('shipping_address_last_name', 50)->nullable()->comment('Last name of the person associated with the shipping address');
            $table->string('shipping_address_name', 100)->nullable()->comment('Name of the shipping address');
            $table->string('shipping_address_company', 50)->nullable()->comment('Company name of the shipping address');
            $table->string('shipping_address_address1', 250)->nullable()->comment('Street address of the shipping address');
            $table->string('shipping_address_address2', 250)->nullable()->comment('Shipping address (line 2)');
            $table->string('shipping_address_phone', 15)->nullable()->comment('Phone number at the shipping address');
            $table->string('shipping_address_city', 50)->nullable()->comment('City of the shipping address');
            $table->string('shipping_address_zip', 50)->nullable()->comment('ZIP code of the shipping address');
            $table->string('shipping_address_province_code', 50)->nullable()->comment('Province code of the shipping address');
            $table->string('shipping_address_province', 50)->nullable()->comment('Province of the shipping address');
            $table->char('shipping_address_country_code', 2)->nullable()->comment('Country code of the shipping address');
            $table->string('shipping_address_country', 50)->nullable()->comment('Country of the shipping address');
            $table->string('shipping_address_latitude', 10)->nullable()->comment('Latitude of the shipping address');
            $table->string('shipping_address_longitude', 10)->nullable()->comment('Longitude of the shipping address');
            $table->string('shipping_method_code', 50)->nullable()->comment('Code of the shipping method used in Shopify');
            $table->unsignedDecimal('shipping_price', 12, 2)->nullable()->comment('Price of shipping for this order');
            $table->string('shipping_currency', 3)->nullable()->comment('Shipping currency');
            $table->string('shipping_service_title', 50)->nullable()->comment('Title of the shipping service');
            $table->unsignedDecimal('total_shipping_tax', 12, 2)->nullable()->comment('Total shipping tax paid for the order');
            $table->enum('processed', ['0', '1', '2'])->default('0')->comment('0 - Order not processed, 1 - Order processed, 2 - Order processed with updates');
            $table->enum('updated', ['0', '1'])->default('0')->comment('0 - Order not updated, 1 - Order updated');
            $table->timestamp('last_modified')->nullable(false)->useCurrent()->useCurrentOnUpdate()->default(\DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('Last modification timestamp');
            $table->timestamps();
            $table->index('shopify_unique_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_orders');
    }
};
