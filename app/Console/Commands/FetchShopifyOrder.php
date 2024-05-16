<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Models\AmazonCronLog;
use App\Models\FetchedReportLog;
use App\Models\OrderDetail;
use App\Models\ShopifyOrderDetail;
use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderItem;
use App\Models\Store;
use App\Models\StoreCredential;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\ShopifyApi\Shopify\GetOrders;
use App\Models\MarketplaceCron;
use App\Services\CronCommonService;
use App\Models\AmazonCronErrorLog;

class FetchShopifyOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetchorders-shopify {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $shopifyLimit    = '250';
    protected $endTime       = '';
    protected $previousHour = -1;
    protected $cron = [
        // Set cron data       
        'hour' => '',
        'date' => '',
        'report_type' => 'FETCH_SHOPIFY_ORDER',
        'cron_title' => 'FETCH_SHOPIFY_ORDER',
        'cron_name' => '',
        'store_id' => '',
        'fetch_report_log_id' => '',
        'report_source' => '3',//Shopify API    
        'report_freq' => '2',//Daily  
    ];
    protected CronCommonService $cronService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('GMT');
        $this->cronService = new CronCommonService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->endTime = Carbon::now()->addMinutes(25)->format('Y-m-d H:i:s');
        $storeId = $this->argument('store_id');
        if (empty($storeId)) {
            $stores = $this->cronService->getStoreIds($marketplace = "Shopify");
                
            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetchorders-shopify', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            // Keep error reporting until it's in debugging mode

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('serialize_precision', 4);

            // Important Variables
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'FETCH_SHOPIFY_ORDER_'.time();

            $this->fetchOrders($storeId);
        }
    }

    /*
      @Description  : Function to fetch the orders from Shopify
      @Author           : Sanjay Chabhadiya
      @Input            : 
      @Output           :
      @Date         : 23-03-2021
    */

    private function fetchOrders($storeId = null)
    {
        // If store id is not numm or zero
        if (!empty(trim($storeId)) && (int) trim($storeId) != 0) {
            // Set cron name
            $this->cron['cron_name'] .=  '_' . $storeId;
            
            $this->cron['cron_param'] =  $storeId;

            // Get store config for store id
            $storeConfig = Store::getStoreConfig($storeId);

            // If store config found
            if (!isset($storeConfig->id)) {
                return;
            }

             // Set cron data
             $cronStartStop = [
                'cron_type' => $this->cron['cron_title'],
                'cron_name' => $this->cron['cron_name'],
                'store_id' => $storeId,
                'cron_param' => $this->cron['cron_param'],
                'action' => 'start',
            ];

            // Log cron start
            $addedCron = AmazonCronLog::cronStartEndUpdate($cronStartStop);
            $cronStartStop['id'] = $addedCron->id;

            $config = [
                'shopify_store_address' => $storeConfig->store_config->store_url,
                'shopify_api_token' =>$storeConfig->refresh_token,
            ];

            if ($storeConfig->order_fetching_start_date != '') {
                $modTimeFrom = $storeConfig->order_fetching_start_date;
            } else {
                $modTimeFrom = Carbon::now()->subHours(config('constants.ORDER_FETCHING_LAST_HOURS'));
            }

            $latestOrderDatetime = ShopifyOrder::getLatestOrderDatetime($storeId);
            if (!empty($latestOrderDatetime)) {
                $modTimeFrom = $latestOrderDatetime;
            }

            $modTimeFrom    = date('Y-m-d H:i:s', strtotime($modTimeFrom . $this->previousHour . 'hours'));
            
            // $params = ['fulfillment_status' => 'any', 'status' => 'any','updated_at_min' =>  date('c', strtotime($modTimeFrom))];
            $params = ['fulfillment_status' => 'any', 'status' => 'any'];

            $orderObj = new GetOrders;
            $orderObj->init($config);
            
            $response = $orderObj->getOrderCount($params);

            if (isset($response->count) && $response->count > 0) {
                $pages = ceil($response->count / $this->shopifyLimit);

                // Setting up the first page
                $currentPage = 1;

                while ($currentPage <= $pages) {
                    // Get orders from Shopify
                    
                    // $response = $orderObj->getData(['updated_at_min' => $modTimeFrom, 'page' => $currentPage, 'limit' => $this->shopifyLimit,'fulfillment_status' => 'any','status' => 'any']);
                    $response = $orderObj->getData(['page' => $currentPage, 'limit' => $this->shopifyLimit,'fulfillment_status' => 'any','status' => 'any']);

                    // If error in response
                    if (isset($response->orders)) {
                        $orderList = json_decode(json_encode($response->orders), true);

                        $this->saveOrders($orderList, $storeConfig);

                        if (Carbon::now()->format('Y-m-d H:i:s') >= $this->endTime) {
                            break;
                        }
                    }

                    $currentPage ++;
                }
            }else {
                AmazonCronErrorLog::create([
                    'store_id' => $storeId,
                    'module' => 'SHOPIFY ORDER FETCH',
                    'submodule' => $this->cron['cron_name'],
                    'error_content' => $response->errors
                ]);
            }

            // Log cron end
            $addedCron->updateEndTime();
        }

    }


    /*
      @Description  : Function to insert order detials
      @Author       : Sanjay Chabhadiya
      @Input        : order array,store details
      @Output       : insert order.
      @Date         : 23-03-2021
     */

    private function saveOrders($orderList, $shopifyStore)
    {
        foreach ($orderList as $order) {
            $orderID = (string) $order['id'];
            
            if ($orderID != "") {
                $updateFields                                   = array();
                $updateFields['shopify_unique_id']              = ($order['id']) ? (string) $order['id'] : NULL;
                $updateFields['order_created_date']             = date('Y-m-d H:i:s', strtotime($order['created_at']));
                $updateFields['order_date']             = Carbon::createFromFormat('Y-m-d H:i:s', $updateFields['order_created_date'],  config('constants.TIMEZONE'));

                $updateFields['buyer_email']                    = ($order['email']) ? (string) $order['email'] : NULL;
                $updateFields['order_closed_date']              = ($order['closed_at']) ? date('Y-m-d H:i:s', strtotime($order['closed_at'])) : NULL;
                $updateFields['order_last_updated_date']        = ($order['updated_at']) ? date('Y-m-d H:i:s', strtotime($order['updated_at'])) : NULL;
                $updateFields['shop_number']                    = ($order['number']) ? (string) $order['number'] : NULL;
                $updateFields['order_note']                     = ($order['note']) ? (string) $order['note'] : NULL;
                $updateFields['order_token']                    = ($order['token']) ? (string) $order['token'] : NULL;
                $updateFields['is_test_order']                  = trim($order['test']) === 'true' ? "1" : "0";
                
                $updateFields['total_price']                    = ($order['total_price']) ? (double) $order['total_price'] : NULL;
                $updateFields['subtotal_price']                 = ($order['subtotal_price']) ? (double) $order['subtotal_price'] : NULL;
                $updateFields['total_weight']                   = ($order['total_weight']) ? (double) $order['total_weight'] : NULL;
                $updateFields['total_tax']                      = ($order['total_tax']) ? (double) $order['total_tax'] : NULL;
                $updateFields['is_taxes_included']              = trim($order['taxes_included']) === 'true' ? "1" : "0";
                $updateFields['order_currency']                 = ($order['currency']) ? (string) $order['currency'] : NULL;
                
                $updateFields['financial_status']               = ($order['financial_status']) ? (string) $order['financial_status'] : NULL;
                $updateFields['confirmed']                      = trim($order['confirmed']) === 'true' ? "1" : "0";
                $updateFields['total_discounts']                = ($order['total_discounts']) ? (double) $order['total_discounts'] : NULL;
                $updateFields['total_line_items_price']         = ($order['total_line_items_price']) ? (double) $order['total_line_items_price'] : NULL;
                $updateFields['cart_token']                     = ($order['cart_token']) ? (string) $order['cart_token'] : NULL;
                $updateFields['buyer_accepts_marketing']        = ($order['buyer_accepts_marketing']) ? (string) $order['buyer_accepts_marketing'] : NULL;
                $updateFields['order_name']                     = ($order['name']) ? (string) $order['name'] : NULL;
                $updateFields['referring_site']                 = ($order['referring_site']) ? (string) $order['referring_site'] : NULL;
                $updateFields['landing_site']                   = ($order['landing_site']) ? (string) $order['landing_site'] : NULL;
                $updateFields['order_cancelled_date']           = ($order['cancelled_at']) ? (string) $order['cancelled_at'] : NULL;
                $updateFields['cancel_reason']                  = ($order['cancel_reason']) ? (string) $order['cancel_reason'] : NULL;
                $updateFields['total_price_usd']                = (isset($order['total_price_usd'])) ? (double) $order['total_price_usd'] : NULL;
                
                $updateFields['checkout_token']                 = ($order['checkout_token']) ? (string) $order['checkout_token'] : NULL;
                $updateFields['reference']                      = ($order['reference']) ? (string) $order['reference'] : NULL;
                $updateFields['location_id']                    = ($order['location_id']) ? (string) $order['location_id'] : NULL;
                $updateFields['source_identifier']              = ($order['source_identifier']) ? (string) $order['source_identifier'] : NULL;
                $updateFields['source_url']                     = ($order['source_url']) ? (string) $order['source_url'] : NULL;
                $updateFields['order_processed_date']           = ($order['processed_at']) ? date('Y-m-d H:i:s', strtotime($order['processed_at'])) : NULL;
                $updateFields['device_id']                      = ($order['source_url']) ? (string) $order['device_id'] : NULL;
                $updateFields['browser_ip']                     = ($order['browser_ip']) ? (string) $order['browser_ip'] : NULL;
                $updateFields['landing_site_ref']               = ($order['landing_site_ref']) ? (string) $order['landing_site_ref'] : NULL;
                $updateFields['shopify_order_number']           = ($order['order_number']) ? (string) $order['order_number'] : NULL;
                
                $updateFields['processing_method']              = ($order['processing_method']) ? (string) $order['processing_method'] : NULL;
                $updateFields['checkout_id']                    = ($order['checkout_id']) ? (string) $order['checkout_id'] : NULL;
                $updateFields['source_name']                    = ($order['source_name']) ? (string) $order['source_name'] : NULL;
                $updateFields['fulfillment_status']             = ($order['fulfillment_status']) ? (string) $order['fulfillment_status'] : NULL;
                $updateFields['tags']                           = ($order['tags']) ? (string) $order['tags'] : NULL;
                $updateFields['contact_email']                  = ($order['contact_email']) ? (string) $order['contact_email'] : NULL;
                $updateFields['order_status_url']               = ($order['order_status_url']) ? (string) $order['order_status_url'] : NULL;
                
                $updateFields['payment_gateway_names']          = !empty($order['payment_gateway_names'][0]) ? (string) $order['payment_gateway_names'][0] : NULL;
                
                $updateFields['billing_address_first_name']     = isset($order['billing_address']['first_name']) ? (string) $order['billing_address']['first_name'] : NULL;
                $updateFields['billing_address_address1']       = isset($order['billing_address']['address1']) ? (string) $order['billing_address']['address1'] : NULL;
                $updateFields['billing_address_phone']          = isset($order['billing_address']['phone']) ? (string) $order['billing_address']['phone'] : NULL;
                $updateFields['billing_address_city']           = isset($order['billing_address']['city']) ? (string) $order['billing_address']['city'] : NULL;
                $updateFields['billing_address_zip']            = isset($order['billing_address']['zip']) ? (string) $order['billing_address']['zip'] : NULL;
                $updateFields['billing_address_province']       = isset($order['billing_address']['province']) ? (string) $order['billing_address']['province'] : NULL;
                $updateFields['billing_address_country']        = isset($order['billing_address']['country']) ? (string) $order['billing_address']['country'] : NULL;
                $updateFields['billing_address_last_name']      = isset($order['billing_address']['last_name']) ? (string) $order['billing_address']['last_name'] : NULL;
                $updateFields['billing_address_address2']       = isset($order['billing_address']['address2']) ? (string) $order['billing_address']['address2'] : NULL;
                $updateFields['billing_address_company']        = isset($order['billing_address']['company']) ? (string) $order['billing_address']['company'] : NULL;
                $updateFields['billing_address_latitude']       = isset($order['billing_address']['latitude']) ? (string) $order['billing_address']['latitude'] : NULL;
                $updateFields['billing_address_longitude']      = isset($order['billing_address']['longitude']) ? (string) $order['billing_address']['longitude'] : NULL;
                $updateFields['billing_address_name']           = isset($order['billing_address']['name']) ? (string) $order['billing_address']['name'] : NULL;
                $updateFields['billing_address_country_code']   = isset($order['billing_address']['country_code']) ? (string) $order['billing_address']['country_code'] : NULL;
                $updateFields['billing_address_province_code']  = isset($order['billing_address']['province_code']) ? (string) $order['billing_address']['province_code'] : NULL;
                
                $updateFields['shipping_address_first_name']    = isset($order['shipping_address']['first_name']) ? (string) $order['shipping_address']['first_name'] : NULL;
                $updateFields['shipping_address_address1']      = isset($order['shipping_address']['address1']) ? (string) $order['shipping_address']['address1'] : NULL;
                $updateFields['shipping_address_phone']         = isset($order['shipping_address']['phone']) ? (string) $order['shipping_address']['phone'] : NULL;
                $updateFields['shipping_address_city']          = isset($order['shipping_address']['city']) ? (string) $order['shipping_address']['city'] : NULL;
                $updateFields['shipping_address_zip']           = isset($order['shipping_address']['zip']) ? (string) $order['shipping_address']['zip'] : NULL;
                $updateFields['shipping_address_province']      = isset($order['shipping_address']['province']) ? (string) $order['shipping_address']['province'] : NULL;
                $updateFields['shipping_address_country']       = isset($order['shipping_address']['country']) ? (string) $order['shipping_address']['country'] : NULL;
                $updateFields['shipping_address_last_name']     = isset($order['shipping_address']['last_name']) ? (string) $order['shipping_address']['last_name'] : NULL;
                $updateFields['shipping_address_address2']      = isset($order['shipping_address']['address2']) ? (string) $order['shipping_address']['address2'] : NULL;
                $updateFields['shipping_address_company']       = isset($order['shipping_address']['company']) ? (string) $order['shipping_address']['company'] : NULL;
                $updateFields['shipping_address_latitude']      = isset($order['shipping_address']['latitude']) ? (string) $order['shipping_address']['latitude'] : NULL;
                $updateFields['shipping_address_longitude']     = isset($order['shipping_address']['longitude']) ? (string) $order['shipping_address']['longitude'] : NULL;
                $updateFields['shipping_address_name']          = isset($order['shipping_address']['name']) ? (string) $order['shipping_address']['name'] : NULL;
                $updateFields['shipping_address_country_code']  = isset($order['shipping_address']['country_code']) ? (string) $order['shipping_address']['country_code'] : NULL;
                $updateFields['shipping_address_province_code'] = isset($order['shipping_address']['province_code']) ? (string) $order['shipping_address']['province_code'] : NULL;
                
                $shippingLinesDetails                          = $order['shipping_lines'];
                
                foreach( $shippingLinesDetails as  $shippingLine) {
                    $updateFields['shipping_method_code']           = ($shippingLine['code']) ? (string) $shippingLine['code'] : NULL;
                    $updateFields['shipping_price']                 = ($shippingLine['price']) ? (double) $shippingLine['price'] : NULL;
                    $updateFields['shipping_service_title']         = ($shippingLine['title']) ? (string) $shippingLine['title'] : NULL;
                    $updateFields['shipping_currency']              = isset($shippingLine['price_set']['shop_money']['currency_code']) ? $shippingLine['price_set']['shop_money']['currency_code'] : NULL;
                }
                
                foreach ($shippingLinesDetails as $itemsTaxLines) {
                    $itemsTaxLines = $itemsTaxLines['tax_lines'];
                    $shippingTax = 0;
                    foreach ($itemsTaxLines as $taxLines) {
                        $shippingTax  += (($taxLines['price']) ? (double) $taxLines['price'] : 0);
                    }

                    $updateFields['total_shipping_tax']               = $shippingTax;
                }
                    
                $updateFields2 = array();

                if ($order['discount_codes']) {
                    $discountCodesDetails = $order['discount_codes'];
                    foreach ($discountCodesDetails as $discountCodes) {
                        $updateFields2['discount_codes'][] = array(
                            'code'   => ($discountCodes['code']) ? (string) $discountCodes['code'] : NULL,
                            'amount' => ($discountCodes['amount']) ? (double) $discountCodes['amount'] : NULL,
                            'type'   => ($discountCodes['type']) ? (string) $discountCodes['type'] : NULL,
                        );
                    }
                    $updateFields2['discount_codes'] = serialize($updateFields2['discount_codes']);
                } else {
                    $updateFields2['discount_codes'] = NULL;
                }

                if ($order['note_attributes']) {
                    $noteAttributesDetails = $order['note_attributes'];
                    foreach ($noteAttributesDetails as $noteAttributes) {
                        $updateFields2['note_attributes'][] = array(
                            'name'  => ($noteAttributes['name']) ? (string) $noteAttributes['name'] : NULL,
                            'value' => ($noteAttributes['value']) ? (string) $noteAttributes['value'] : NULL,
                        );
                    }
                    $updateFields2['note_attributes'] = serialize($updateFields2['note_attributes']);
                } else {
                    $updateFields2['note_attributes'] = NULL;
                }

                if ($order['tax_lines']) {
                    $taxLines = $order['tax_lines'];
                    foreach ($taxLines as $taxLinesDetails) {
                        $updateFields2['tax_lines'][] = array(
                            'title'  => ($taxLinesDetails['title']) ? (string) $taxLinesDetails['title'] : NULL,
                            'price' => ($taxLinesDetails['price']) ? (double) $taxLinesDetails['price'] : NULL,
                            'rate' => ($taxLinesDetails['rate']) ? (double) $taxLinesDetails['rate'] : NULL,
                        );
                    }
                    $updateFields2['tax_lines'] = serialize($updateFields2['tax_lines']);
                } else {
                    $updateFields2['tax_lines'] = NULL;
                }

                if ($order['shipping_lines']) {
                    $shippingLinesDetails = $order['shipping_lines'];

                    foreach ($shippingLinesDetails as $shippingLines) {
                        $id = (string) $shippingLines['id'];

                        $updateFields2['shipping_lines'][$id] = array(
                            'id'                               => ($shippingLines['id']) ? (string) $shippingLines['id'] : NULL,
                            'title'                            => ($shippingLines['title']) ? (string) $shippingLines['title'] : NULL,
                            'price'                            => ($shippingLines['price']) ? (double) $shippingLines['price'] : NULL,
                            'code'                             => ($shippingLines['code']) ? (string) $shippingLines['code'] : NULL,
                            'source'                           => ($shippingLines['source']) ? (string) $shippingLines['source'] : NULL,
                            'phone'                            => ($shippingLines['phone']) ? (string) $shippingLines['phone'] : NULL,
                            'requested_fulfillment_service_id' => ($shippingLines['requested_fulfillment_service_id']) ? (string) $shippingLines['requested_fulfillment_service_id'] : NULL,
                            'delivery_category'                => ($shippingLines['delivery_category']) ? (string) $shippingLines['delivery_category'] : NULL,
                            'carrier_identifier'               => ($shippingLines['carrier_identifier']) ? (string) $shippingLines['carrier_identifier'] : NULL,
                        );

                        $shippingLinesTaxLines = $shippingLines['tax_lines'];

                        foreach ($shippingLinesTaxLines as $shippingTaxLines)
                        {
                            $updateFields2['shipping_lines'][$id]['tax_lines'][] = $shippingTaxLines;
                        }
                    }
                    $updateFields2['shipping_lines'] = serialize($updateFields2['shipping_lines']);
                } else {
                    $updateFields2['shipping_lines'] = NULL;
                }

                if ($order['fulfillments']) {
                    $orderFulfillments = $order['fulfillments'];
                    foreach ($orderFulfillments as $fulfillments) {

                        $id = (string) $fulfillments['id'];

                        $updateFields2['fulfillments_details'][$id] = array(
                            'id'               => ($fulfillments['id']) ? (string) $fulfillments['id'] : NULL,
                            'order_id'         => ($fulfillments['order_id']) ? (double) $fulfillments['order_id'] : NULL,
                            'status'           => ($fulfillments['status']) ? (string) $fulfillments['status'] : NULL,
                            'created_at'       => ($fulfillments['created_at']) ? date('Y-m-d H:i:s', strtotime($fulfillments['created_at'])) : NULL,
                            'service'          => ($fulfillments['service']) ? (string) $fulfillments['service'] : NULL,
                            'updated_at'       => ($fulfillments['updated_at']) ? date('Y-m-d H:i:s', strtotime($fulfillments['updated_at'])) : NULL,
                            'tracking_company' => ($fulfillments['tracking_company']) ? (string) $fulfillments['tracking_company'] : NULL,
                            'shipment_status'  => ($fulfillments['shipment_status']) ? (string) $fulfillments['shipment_status'] : NULL,
                            'tracking_number'  => ($fulfillments['tracking_number']) ? (string) $fulfillments['tracking_number'] : NULL,
                            'tracking_url'     => ($fulfillments['tracking_url']) ? (string) $fulfillments['tracking_url'] : NULL,
                        );

                        $fulfillmentsTrackingNumbers = $fulfillments['tracking_numbers'];
                        foreach ($fulfillmentsTrackingNumbers as $trackingNumbers) {
                            $updateFields2['fulfillments_details'][$id]['tracking_numbers'][] = [
                                'tracking_numbers' => ($trackingNumbers) ? (string) $trackingNumbers : NULL,
                            ];
                        }

                        $fulfillmentsReceipt = $fulfillments['receipt'];

                        $updateFields2['fulfillments_details'][$id]['receipt'] = [
                            'testcase'      => isset($fulfillmentsReceipt['testcase']) ? (string) $fulfillmentsReceipt['testcase'] : NULL,
                            'authorization' => isset($fulfillmentsReceipt['authorization']) ? (string) $fulfillmentsReceipt['authorization'] : NULL,
                        ];


                        $fulfillmentsLineItems = $fulfillments['line_items'];
                        foreach ($fulfillmentsLineItems as $lineItemsFulfillments) {
                            $lineItemsFulfillmentsId = $lineItemsFulfillments['id'];
                            $updateFields2['fulfillments_details'][$id]['line_items'][$lineItemsFulfillmentsId][] = array(
                                'id'                           => ($lineItemsFulfillments['id']) ? (string) $lineItemsFulfillments['id'] : NULL,
                                'variant_id'                   => ($lineItemsFulfillments['variant_id']) ? (string) $lineItemsFulfillments['variant_id'] : NULL,
                                'title'                        => ($lineItemsFulfillments['title']) ? (string) $lineItemsFulfillments['title'] : NULL,
                                'quantity'                     => ($lineItemsFulfillments['quantity']) ? (string) $lineItemsFulfillments['quantity'] : NULL,
                                'price'                        => ($lineItemsFulfillments['price']) ? (double) $lineItemsFulfillments['price'] : NULL,
                                'grams'                        => ($lineItemsFulfillments['grams']) ? (string) $lineItemsFulfillments['grams'] : NULL,
                                'sku'                          => ($lineItemsFulfillments['sku']) ? (string) $lineItemsFulfillments['sku'] : NULL,
                                'variant_title'                => ($lineItemsFulfillments['variant_title']) ? (string) $lineItemsFulfillments['variant_title'] : NULL,
                                'vendor'                       => ($lineItemsFulfillments['vendor']) ? (string) $lineItemsFulfillments['vendor'] : NULL,
                                'fulfillment_service'          => ($lineItemsFulfillments['fulfillment_service']) ? (string) $lineItemsFulfillments['fulfillment_service'] : NULL,
                                'product_id'                   => ($lineItemsFulfillments['product_id']) ? (string) $lineItemsFulfillments['product_id'] : NULL,
                                'requires_shipping'            => ($lineItemsFulfillments['requires_shipping']) ? (string) $lineItemsFulfillments['requires_shipping'] : NULL,
                                'taxable'                      => ($lineItemsFulfillments['taxable']) ? (string) $lineItemsFulfillments['taxable'] : NULL,
                                'gift_card'                    => ($lineItemsFulfillments['gift_card']) ? (string) $lineItemsFulfillments['gift_card'] : NULL,
                                'name'                         => ($lineItemsFulfillments['name']) ? (string) $lineItemsFulfillments['name'] : NULL,
                                'variant_inventory_management' => ($lineItemsFulfillments['variant_inventory_management']) ? (string) $lineItemsFulfillments['variant_inventory_management'] : NULL,
                                'product_exists'               => ($lineItemsFulfillments['product_exists']) ? (string) $lineItemsFulfillments['product_exists'] : NULL,
                                'fulfillable_quantity'         => ($lineItemsFulfillments['fulfillable_quantity']) ? (string) $lineItemsFulfillments['fulfillable_quantity'] : NULL,
                                'total_discount'               => ($lineItemsFulfillments['total_discount']) ? (double) $lineItemsFulfillments['total_discount'] : NULL,
                                'fulfillment_status'           => ($lineItemsFulfillments['fulfillment_status']) ? (string) $lineItemsFulfillments['fulfillment_status'] : NULL,
                            );


                            $lineItemsProperties = $lineItemsFulfillments['properties'];
                            foreach ($lineItemsProperties as $properties) {
                                $updateFields2['fulfillments_details'][$id]['properties'][$lineItemsFulfillmentsId][] = [
                                    'name'  => ($properties['name']) ? (string) $properties['name'] : NULL,
                                    'value' => ($properties['value']) ? (string) $properties['value'] : NULL,
                                ];
                            }

                            $lineItemsTaxLines = $lineItemsFulfillments['tax_lines'];
                            foreach ($lineItemsTaxLines as $taxLines) {
                                $updateFields2['fulfillments_details'][$id]['tax_lines'][$lineItemsFulfillmentsId][] = [
                                    'title' => ($taxLines['title']) ? (string) $taxLines['title'] : NULL,
                                    'price' => ($taxLines['price']) ? (double) $taxLines['price'] : NULL,
                                    'rate'  => ($taxLines['rate']) ? (double) $taxLines['rate'] : NULL,
                                ];
                            }
                        }
                    }
                    $updateFields2['fulfillments_details'] = serialize($updateFields2['fulfillments_details']);
                } else {
                    $updateFields2['fulfillments_details'] = NULL;
                }

                if (!empty($order['client_details'])) {
                    $updateFields2['client_details'] = array(
                        'browser_ip'      => ($order['client_details']['browser_ip']) ? (string) $order['client_details']['browser_ip'] : NULL,
                        'accept_language' => ($order['client_details']['accept_language']) ? (string) $order['client_details']['accept_language'] : NULL,
                        'user_agent'      => ($order['client_details']['user_agent']) ? (string) $order['client_details']['user_agent'] : NULL,
                        'session_hash'    => ($order['client_details']['session_hash']) ? (string) $order['client_details']['session_hash'] : NULL,
                        'browser_width'   => ($order['client_details']['browser_width']) ? (string) $order['client_details']['browser_width'] : NULL,
                        'browser_height'  => ($order['client_details']['browser_height']) ? (string) $order['client_details']['browser_height'] : NULL,
                        );

                    $updateFields2['client_details'] = serialize($updateFields2['client_details']);
                } else {
                    $updateFields2['client_details'] = NULL;
                }

                if (isset($order['payment_details'])) {
                    $updateFields2['payment_details'] = array(
                        'credit_card_bin'     => ($order['payment_details']['credit_card_bin']) ? (string) $order['payment_details']['credit_card_bin'] : NULL,
                        'avs_result_code'     => ($order['payment_details']['avs_result_code']) ? (string) $order['payment_details']['avs_result_code'] : NULL,
                        'cvv_result_code'     => ($order['payment_details']['cvv_result_code']) ? (string) $order['payment_details']['cvv_result_code'] : NULL,
                        'credit_card_number'  => ($order['payment_details']['credit_card_number']) ? (string) $order['payment_details']['credit_card_number'] : NULL,
                        'credit_card_company' => ($order['payment_details']['credit_card_company']) ? (string) $order['payment_details']['credit_card_company'] : NULL,
                    );

                    $updateFields2['payment_details'] = serialize($updateFields2['payment_details']);
                } else {
                    $updateFields2['payment_details'] = NULL;
                }

                if (!empty($order['customer'])) {
                    $updateFields2['shopify_customer'] = array(
                        'id'                   => (isset($order['customer']['id'])) ? (string) $order['customer']['id'] : NULL,
                        'email'                => (isset($order['customer']['email'])) ? (string) $order['customer']['email'] : NULL,
                        'accepts_marketing'    => (isset($order['customer']['accepts_marketing'])) ? (string) $order['customer']['accepts_marketing'] : NULL,
                        'created_at'           => (isset($order['created_at'])) ? date('Y-m-d H:i:s', strtotime($order['created_at'])) : NULL,
                        'updated_at'           => (isset($order['updated_at'])) ? date('Y-m-d H:i:s', strtotime($order['updated_at'])) : NULL,
                        'first_name'           => (isset($order['customer']['first_name'])) ? (string) $order['customer']['first_name'] : NULL,
                        'last_name'            => (isset($order['customer']['last_name'])) ? (string) $order['customer']['last_name'] : NULL,
                        'orders_count'         => (isset($order['customer']['orders_count'])) ? (string) $order['customer']['orders_count'] : NULL,
                        'state'                => (isset($order['customer']['state'])) ? (string) $order['customer']['state'] : NULL,
                        'total_spent'          => (isset($order['customer']['total_spent'])) ? (string) $order['customer']['total_spent'] : NULL,
                        'last_order_id'        => (isset($order['customer']['last_order_id'])) ? (string) $order['customer']['last_order_id'] : NULL,
                        'note'                 => (isset($order['customer']['note'])) ? (string) $order['customer']['note'] : NULL,
                        'verified_email'       => (isset($order['customer']['verified_email'])) ? (string) $order['customer']['verified_email'] : NULL,
                        'multipass_identifier' => (isset($order['customer']['multipass_identifier'])) ? (string) $order['customer']['multipass_identifier'] : NULL,
                        'tax_exempt'           => (isset($order['customer']['tax_exempt'])) ? (string) $order['customer']['tax_exempt'] : NULL,
                        'tags'                 => (isset($order['customer']['tags'])) ? (string) $order['customer']['tags'] : NULL,
                        'last_order_name'      => (isset($order['customer']['last_order_name'])) ? (string) $order['customer']['last_order_name'] : NULL,
                        'id'                   => (isset($order['customer']['default_address']['id'])) ? (string) $order['customer']['default_address']['id'] : NULL,
                        'first_name'           => (isset($order['customer']['default_address']['first_name'])) ? (string) $order['customer']['default_address']['first_name'] : NULL,
                        'last_name'            => (isset($order['customer']['default_address']['last_name'])) ? (string) $order['customer']['default_address']['last_name'] : NULL,
                        'company'              => (isset($order['customer']['default_address']['company'])) ? (string) $order['customer']['default_address']['company'] : NULL,
                        'address1'             => (isset($order['customer']['default_address']['address1'])) ? (string) $order['customer']['default_address']['address1'] : NULL,
                        'address2'             => (isset($order['customer']['default_address']['address2'])) ? (string) $order['customer']['default_address']['address2'] : NULL,
                        'city'                 => (isset($order['customer']['default_address']['city'])) ? (string) $order['customer']['default_address']['city'] : NULL,
                        'province'             => (isset($order['customer']['default_address']['province'])) ? (string) $order['customer']['default_address']['province'] : NULL,
                        'country'              => (isset($order['customer']['default_address']['country'])) ? (string) $order['customer']['default_address']['country'] : NULL,
                        'zip'                  => (isset($order['customer']['default_address']['zip'])) ? (string) $order['customer']['default_address']['zip'] : NULL,
                        'phone'                => (isset($order['customer']['default_address']['phone'])) ? (string) $order['customer']['default_address']['phone'] : NULL,
                        'name'                 => (isset($order['customer']['default_address']['name'])) ? (string) $order['customer']['default_address']['name'] : NULL,
                        'province_code'        => (isset($order['customer']['default_address']['province_code'])) ? (string) $order['customer']['default_address']['province_code'] : NULL,
                        'country_code'         => (isset($order['customer']['default_address']['country_code'])) ? (string) $order['customer']['default_address']['country_code'] : NULL,
                        'country_name'         => (isset($order['customer']['default_address']['country_name'])) ? (string) $order['customer']['default_address']['country_name'] : NULL,
                        'default'              => (isset($order['customer']['default_address']['default'])) ? (string) $order['customer']['default_address']['default'] : NULL,
                    );

                    $updateFields2['shopify_customer'] = serialize($updateFields2['shopify_customer']);
                } else {
                    $updateFields2['shopify_customer'] = NULL;
                }

                if (!empty($order['refunds'])) {
                    $orderRefunds = $order['refunds'];
                    foreach ($orderRefunds as $refunds) {
                        $id = (string) $refunds['id'];
                        $updateFields2['refund_details'][$id] = [
                            'id'         => ($refunds['id']) ? (string) $refunds['id'] : NULL,
                            'order_id'   => ($refunds['order_id']) ? (string) $refunds['order_id'] : NULL,
                            'created_at' => ($refunds['created_at']) ? date('Y-m-d H:i:s', strtotime($refunds['created_at'])) : NULL,
                            'note'       => ($refunds['note']) ? (string) $refunds['note'] : NULL,
                            'restock'    => ($refunds['restock']) ? (string) $refunds['restock'] : NULL,
                            'user_id'    => ($refunds['user_id']) ? (string) $refunds['user_id'] : NULL,
                        ];

                        $lineItems = $refunds['refund_line_items'];
                        foreach ($lineItems as $refundLineItems) {
                            $refundItems = [
                                'id'                           => ($refundLineItems['id']) ? (string) $refundLineItems['id'] : NULL,
                                'quantity'                     => ($refundLineItems['quantity']) ? (string) $refundLineItems['quantity'] : NULL,
                                'line_item_id'                 => ($refundLineItems['line_item_id']) ? (string) $refundLineItems['line_item_id'] : NULL,
                                'id'                           => ($refundLineItems['line_item']['id']) ? (string) $refundLineItems['line_item']['id'] : NULL,
                                'variant_id'                   => ($refundLineItems['line_item']['variant_id']) ? (string) $refundLineItems['line_item']['variant_id'] : NULL,
                                'title'                        => ($refundLineItems['line_item']['title']) ? (string) $refundLineItems['line_item']['title'] : NULL,
                                'quantity'                     => ($refundLineItems['line_item']['quantity']) ? (string) $refundLineItems['line_item']['quantity'] : NULL,
                                'price'                        => ($refundLineItems['line_item']['price']) ? (string) $refundLineItems['line_item']['price'] : NULL,
                                'grams'                        => ($refundLineItems['line_item']['grams']) ? (string) $refundLineItems['line_item']['grams'] : NULL,
                                'sku'                          => ($refundLineItems['line_item']['sku']) ? (string) $refundLineItems['line_item']['sku'] : NULL,
                                'variant_title'                => ($refundLineItems['line_item']['variant_title']) ? (string) $refundLineItems['line_item']['variant_title'] : NULL,
                                'vendor'                       => ($refundLineItems['line_item']['vendor']) ? (string) $refundLineItems['line_item']['vendor'] : NULL,
                                'fulfillment_service'          => ($refundLineItems['line_item']['fulfillment_service']) ? (string) $refundLineItems['line_item']['fulfillment_service'] : NULL,
                                'product_id'                   => ($refundLineItems['line_item']['product_id']) ? (string) $refundLineItems['line_item']['product_id'] : NULL,
                                'requires_shipping'            => ($refundLineItems['line_item']['requires_shipping']) ? (string) $refundLineItems['line_item']['requires_shipping'] : NULL,
                                'taxable'                      => ($refundLineItems['line_item']['taxable']) ? (string) $refundLineItems['line_item']['taxable'] : NULL,
                                'gift_card'                    => ($refundLineItems['line_item']['gift_card']) ? (string) $refundLineItems['line_item']['gift_card'] : NULL,
                                'name'                         => ($refundLineItems['line_item']['name']) ? (string) $refundLineItems['line_item']['name'] : NULL,
                                'variant_inventory_management' => ($refundLineItems['line_item']['variant_inventory_management']) ? (string) $refundLineItems['line_item']['variant_inventory_management'] : NULL,
                                'product_exists'               => ($refundLineItems['line_item']['product_exists']) ? (string) $refundLineItems['line_item']['product_exists'] : NULL,
                                'fulfillable_quantity'         => ($refundLineItems['line_item']['fulfillable_quantity']) ? (string) $refundLineItems['line_item']['fulfillable_quantity'] : NULL,
                                'total_discount'               => ($refundLineItems['line_item']['total_discount']) ? (double) $refundLineItems['line_item']['total_discount'] : NULL,
                                'fulfillment_status'           => ($refundLineItems['line_item']['fulfillment_status']) ? (string) $refundLineItems['line_item']['fulfillment_status'] : NULL,
                            ];

                            foreach ($refundLineItems['line_item']['tax_lines'] as $taxLines) {
                                $refundItems['tax_lines'][] = [
                                    'title' => ($taxLines['title']) ? (string) $taxLines['title'] : NULL,
                                    'price' => ($taxLines['price']) ? (string) $taxLines['price'] : NULL,
                                    'rate'  => ($taxLines['rate']) ? (string) $taxLines['rate'] : NULL,
                                ];
                            }

                            foreach ($refundLineItems['line_item']['properties'] as $properties) {
                                $refundItems['properties'][] = [
                                    'name'  => ($properties['name']) ? (string) $properties['name'] : NULL,
                                    'value' => ($properties['value']) ? (string) $properties['value'] : NULL,
                                ];
                            }
                            $updateFields2['refund_details'] = $refundItems;
                        }
                    }
                   $updateFields2['refund_details'] = serialize($updateFields2['refund_details']);
                } else {
                    $updateFields2['refund_details'] = NULL;
                }

                $updateFields['updated']      = '0';

                DB::transaction(function () use($shopifyStore, $updateFields, $updateFields2, $orderID, $order) {
                    $orderDetail = ShopifyOrder::orderExists($shopifyStore->id, $orderID);

                    if (!empty($orderDetail->id)) {
                        if ($orderDetail->processed != '0') {
                            $updateFields['processed'] = '2';
                        }

                        $orderDetail->update($updateFields);

                        $shopifyId = $orderDetail->id;
                    } else {
                        $updateFields['store_id']      = $shopifyStore->id;
                        $updateFields['processed']     = '0';

                        $orderDetail = ShopifyOrder::create($updateFields);
                        $shopifyId = $orderDetail->id;
                    }

                    $orderDetails = ShopifyOrderDetail::orderDetailExists($shopifyId);

                    if (!empty($orderDetails->id)) {
                        $orderDetails->update($updateFields2);
                    } else {
                        $updateFields2['shopify_order_id'] = $shopifyId;

                        ShopifyOrderDetail::create($updateFields2);
                    }

                    $this->saveOrderItems($order["line_items"], $shopifyId);

                });
            }
        }
    }

    /*
      @Description  : Function to insert order items details
      @Author       : Sanjay Chabhadiya
      @Input        : order array,id
      @Output       : insert order items.
      @Date         : 23-03-2021
     */

    function saveOrderItems($orderItems, $shopifyId)
    {
        $orderCount = 0;
        foreach ($orderItems as $items) {
            $orderItemId = (string) $items['id'];

            if (!empty($orderItemId)) {
                $updateFields                     = [];
                
                $updateFields['shopify_item_id']  = (string) $items['id'];
                
                $updateFields['title']            =  $items['title'] ? (string)$items['title'] : NULL;
                $updateFields['variant_id']       =  $items['variant_id'] ? (string)$items['variant_id'] : NULL;
                $updateFields['quantity']         =  $items['quantity'] ? (string)$items['quantity'] : NULL;
                $updateFields['item_total_price'] =  $items['price'] ? (double)$items['price'] * $items['quantity'] : NULL;
                $updateFields['item_price']       =  ($items['quantity'] > 0) ? (double) $items['price'] : NULL;

                $itemsTaxLines = $items['tax_lines'];
                $itemTaxTotal = 0;
                foreach ($itemsTaxLines as $taxLines) {
                    $itemTaxTotal  += (($taxLines['price']) ? (double) $taxLines['price'] : 0);
                }

                $updateFields['item_total_tax']               = $itemTaxTotal;
                $updateFields['item_tax']                     = ($items['quantity'] > 0) ? (double)$itemTaxTotal / $items['quantity'] : NULL;
                
                $updateFields['grams']                        =  $items['grams'] ? (string)$items['grams'] : NULL;
                $updateFields['sku']                          =  $items['sku'] ? (string)$items['sku'] : NULL;
                $updateFields['variant_title']                =  $items['variant_title'] ? (string)$items['variant_title'] : NULL;
                $updateFields['vendor']                       =  $items['vendor'] ? (string)$items['vendor'] : NULL;
                $updateFields['fulfillment_service']          =  $items['fulfillment_service'] ? (string)$items['fulfillment_service'] : NULL;
                $updateFields['product_id']                   =  $items['product_id'] ? (string)$items['product_id'] : NULL;
                $updateFields['requires_shipping']            =  $items['requires_shipping'] ? (string)$items['requires_shipping'] : NULL;
                $updateFields['is_taxable']                   =  trim($items['taxable']) === 'true' ? "1" : "0";
                $updateFields['is_gift_card']                 =  trim($items['gift_card']) === 'true' ? "1" : "0";
                $updateFields['name']                         =  $items['name'] ? (string)$items['name'] : NULL;
                $updateFields['variant_inventory_management'] =  $items['variant_inventory_management'] ? (string)$items['variant_inventory_management'] : NULL;

                $itemsProperties = $items['properties'];
                if ($itemsProperties) { 
                    foreach ($itemsProperties as $itemsPropertiesDetails) {
                        $updateFields['properties'][] = array(
                            'name'  => ($itemsPropertiesDetails['name']) ? (string) $itemsPropertiesDetails['name'] : NULL,
                            'value' => ($itemsPropertiesDetails['value']) ? (string) $itemsPropertiesDetails['value'] : NULL,
                        );
                    }
                    $updateFields['properties'] = serialize($updateFields['properties']);
                } else {
                    $updateFields['properties'] = NULL;
                }

                $updateFields['product_exists']       =  trim($items['product_exists']) === 'true' ? "1" : "0";
                $updateFields['fulfillable_quantity'] =  $items['fulfillable_quantity'] ? (string)$items['fulfillable_quantity'] : NULL;
                $updateFields['item_total_discount']  = $items['total_discount'] ? (string)$items['total_discount'] : NULL;
                $updateFields['item_discount']        = ($items['quantity'] > 0) ? (double)$items['total_discount'] / $items['quantity'] : NULL;
                $updateFields['fulfillment_status']   = $items['fulfillment_status'] ? (string)$items['fulfillment_status'] : NULL;

                if ($items['tax_lines']) {
                    $taxLines = $items['tax_lines'];
                    foreach ($taxLines as $taxLinesDetails) {
                        $updateFields['tax_lines'][] = array(
                            'title'  => ($taxLinesDetails['title']) ? (string) $taxLinesDetails['title'] : NULL,
                            'price' => ($taxLinesDetails['price']) ? (double) $taxLinesDetails['price'] : NULL,
                            'rate' => ($taxLinesDetails['rate']) ? (double) $taxLinesDetails['rate'] : NULL,
                        );
                    }
                    $updateFields['tax_lines'] = serialize($updateFields['tax_lines']);
                } else {
                    $updateFields['tax_lines'] = NULL;
                }

                $itemExist = ShopifyOrderItem::orderItemExist($shopifyId, $updateFields['shopify_item_id']);

                $updateFields['updated'] = '0';

                if (!empty($itemExist->id)) {
                    ShopifyOrderItem::where('id', $itemExist->id)
                    ->update($updateFields);
                } else {
                    $updateFields['shopify_order_id']    = $shopifyId;

                    ShopifyOrderItem::create($updateFields);
                }

                $orderCount++; 
            }

           if ($orderItems && $orderCount > 0) {
                $updateFields = [];
                $updateFields['processed'] = '1';
                $updateFields['items_count'] = $orderCount;
                ShopifyOrder::where('id', $shopifyId)->update($updateFields);
           } 
        }
    }
}
