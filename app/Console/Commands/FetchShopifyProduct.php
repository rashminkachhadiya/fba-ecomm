<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Models\AmazonCronLog;
use App\Models\AmazonCronErrorLog;
use App\Models\FetchedReportLog;
use App\Models\MarketplaceCron;
use App\Models\ShopifyProduct;
use App\Models\ShopifyProductImage;
use App\Models\Store;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\ShopifyApi\Shopify\GetProductDetails;
use App\Services\CronCommonService;

class FetchShopifyProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetchproduct-shopify {store_id?} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $currency = '';

    protected $shopifyLimit    = '250';
    protected $locationLimit   = '50';
    protected $shopifyFlag     = 1;
    protected $endTime       = '';
    // Shopify API URLS
    protected $shopUrl             = '/admin/api/2022-01/shop.json';
    protected $cron = [
        // Set cron data       
        'hour' => '',
        'date' => '',
        'report_type' => 'FETCH_SHOPIFY_PRODUCT',
        'cron_title' => 'FETCH_SHOPIFY_PRODUCT',
        'cron_name' => '',
        'store_id' => '',
        'fetch_report_log_id' => '',
        'report_source' => '3',//Shopify API    
        'report_freq' => '2',//Daily  
    ];
    protected CronCommonService $cronService;

    public function __construct()
    {
        parent::__construct();
        $this->cronService = new CronCommonService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $storeId = $this->argument('store_id');

        if (empty($storeId)) {
            $stores = $this->cronService->getStoreIds($marketplace = "Shopify");
                
            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetchproduct-shopify', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('serialize_precision', 4);

            $this->endTime = Carbon::now()->addMinutes(config('constants.CRON_STOP_MINUTE'))->format('Y-m-d H:i:s');
            // Important Variables
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'FETCH_SHOPIFY_PRODUCT_' . time();
            $this->fetchProducts($storeId);
        }
    }

    private function fetchProducts($storeId = null)
    {
        // If store id is not numm or zero
        if (!empty(trim($storeId)) && (int) trim($storeId) != 0) {
            // Set store id
            $this->cron['store_id'] = $storeId = (int) trim($storeId);

            // Set cron name
            $this->cron['cron_name'] .=  '_' . $storeId;
            
            $this->cron['cron_param'] =  $storeId;

            // Get store config for store id
            $storeConfig = Store::getStoreConfig($storeId);

            $this->currency = $storeConfig->store_currency;
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

            //nesherr.myshopify.com
            // $this->shopifyFlag             = $storeConfig->shopify_flag;
            // $this->productToken            = $storeConfig->shopify_product_token;

            $credentials = [
                'shopify_store_address' => $storeConfig->store_config->store_url,
                'shopify_api_token' =>$storeConfig->refresh_token,
            ];

            $this->shopify = new GetProductDetails();
            $this->shopify->init($credentials);

            // Get total products available at store
            $productCount = $this->shopify->getProductCount();

            if (!empty($productCount->count)) {
                // Get total products count
                $loopCount     = ceil($productCount->count / $this->shopifyLimit);

                // Get the shoipfy product details
                $this->getShopifyProductDetails($loopCount);

                // Check for products without location id, Add their respective location_id
                $this->getProductLocations($storeId);
            } else {
                AmazonCronErrorLog::create([
                    'store_id' => $storeId,
                    'module' => 'SHOPIFY PRODUCT FETCH',
                    'submodule' => $this->cron['cron_name'],
                    'error_content' => $productCount->errors
                ]);
            }

            // Log cron end
            $addedCron->updateEndTime();
        }
    }

    /*
      @Description  : Function to get shopify product details
      @Author       : Sanjay Chabhadiya
      @Input        :
      @Output       :
      @Date         : 16-03-2021
     */
    private function getShopifyProductDetails($count) 
    {
        // if $this->shopifyFlag is greater than total page counts, make $this->shopifyFlag = count
        if($this->shopifyFlag > $count) {
            $this->shopifyFlag = $count;
        }

        // Set up the pagination in for loop and call the products
        for($i = $this->shopifyFlag; $i <= $count; $i++)
        {   
            sleep(1);
            $query = array(
                "limit" => $this->shopifyLimit,
                "page"  => $i,
                // "page_token" => $this->productToken
            );

            // Fetch shopify products
            $shopifyProducts = $this->shopify->getProducts($query);

            // $nextPageToken = $this->shopify->get_next_page_token();

            // StoreCredential::where('store_id', $this->cron['store_id'])
            // ->update([
            //     'shopify_product_token' => $nextPageToken
            // ]);

            // If you got products
            if (isset($shopifyProducts->products) && count($shopifyProducts->products)) {
                // Get all the unique ids
                $uniqueIds = array();

                foreach ($shopifyProducts->products as $value) {
                    $uniqueIds[] = (string) $value->id;
                }

                // Get the existing products
                $existingProductIds = ShopifyProduct::getExistingProducts([
                    'storeId' => $this->cron['store_id'],
                    'uniqueIds' => $uniqueIds
                ]);

                foreach ($shopifyProducts->products as $value) {
                    // Initialize the array that will either update or insert the product data
                    $insertArray = $updateArray = array();

                    // Check for variation flag
                    if (count($value->variants)) {
                        if (trim($value->variants[0]->title) == "Default Title" && count($value->variants) == 1) {
                            $variation = "0";
                        } else {
                            $variation = "2";
                        }
                    } else {
                        $variation = "0";
                    }

                    // If the product is existing in our database
                    if (isset($existingProductIds[$value->id])) {
                        $updateArray = array(
                            "unique_id"             => (string) $value->id,
                            "store_id"              => $this->cron['store_id'],
                            "currency"              => $this->currency,
                            "title"                 => isset($value->title) ? $value->title : NULL,
                            "description"           => isset($value->body_html) ? $value->body_html : NULL,
                            "quantity"              => isset($value->variants[0]->inventory_quantity) ? $value->variants[0]->inventory_quantity : NULL,
                            "old_inventory_quantity" => isset($value->variants[0]->old_inventory_quantity) ? $value->variants[0]->old_inventory_quantity : NULL,
                            "inventory_item_id"     => isset($value->variants[0]->inventory_item_id) ? $value->variants[0]->inventory_item_id : NULL,
                            "vendor"                => isset($value->vendor) ? $value->vendor : NULL,
                            "product_type"          => isset($value->product_type) ? $value->product_type : NULL,
                            "handle"                => isset($value->handle) ? $value->handle : NULL,
                            "published_scope"       => isset($value->published_scope) ? $value->published_scope : NULL,
                            "is_variation"          => $variation,
                            "template_suffix"       => isset($value->template_suffix) ? $value->template_suffix : NULL,
                            "tags"                  => isset($value->tags) ? $value->tags : NULL,
                            "main_image"            => isset($value->image) ? $value->image->src : NULL,
                            "shopify_created_at"    => $this->formatDate($value->created_at),
                            "shopify_updated_at"    => $this->formatDate($value->updated_at),
                            "shopify_published_at"  => $this->formatDate($value->published_at),
                            "last_modified"         => CommonHelper::getInsertedDateTime(),
                        );

                        // Update the data
                        ShopifyProduct::where('unique_id', $updateArray['unique_id'])->update($updateArray);

                        // update data in shopify variation products //
                        if (count($value->variants) > 0) {
                            $this->saveVariationProduct($value->variants, $existingProductIds[trim($value->id)]['id'], "update", $value->options, $value);
                        }

                        // If there's no variant
                        if (count($value->variants) == 1) {
                            $shopifyPriceUpdateArray = array();

                            if (trim($value->variants[0]->title) == "Default Title") {

                                $shopifyPriceUpdateArray = array(
                                    'sku'           => isset($value->variants[0]->sku) ? $value->variants[0]->sku : NULL,
                                    'price'         => isset($value->variants[0]->price) ? $value->variants[0]->price : NULL,
                                    'last_modified' => CommonHelper::getInsertedDateTime()
                                );

                                if (!empty($shopifyPriceUpdateArray)) {
                                    ShopifyProduct::where('sku', $shopifyPriceUpdateArray['sku'])->update($shopifyPriceUpdateArray);
                                }
                            }
                        }

                        // Update images
                        if (count($value->images) > 0) {
                            ShopifyProductImage::where('shopify_product_id', $existingProductIds[trim($value->id)]['id'])
                            ->delete();

                            $this->insertShopifyProductImage($value->images, $existingProductIds[trim($value->id)]['id']);
                        }
                    } else {
                        $insertArray[] = array(
                            "unique_id"         => $value->id,
                            "store_id"          => $this->cron['store_id'],
                            "currency"              => $this->currency,
                            "title"             => isset($value->title) ? $value->title : NULL,
                            "sku"               => CommonHelper::getSku(),
                            "description"       => isset($value->body_html) ? $value->body_html : NULL,
                            "vendor"            => isset($value->vendor) ? $value->vendor : NULL,
                            "product_type"      => isset($value->product_type) ? $value->product_type : NULL,
                            "handle"            => isset($value->handle) ? $value->handle : NULL,
                            "published_scope"   => isset($value->published_scope) ? $value->published_scope : NULL,
                            "is_variation"      => $variation,
                            "template_suffix"   => isset($value->template_suffix) ? $value->template_suffix : NULL,
                            "tags"              => isset($value->tags) ? $value->tags : NULL,
                            "main_image"        => isset($value->image) ? $value->image->src : NULL,
                            "is_posted_status"  => "1",
                            "shopify_created_at" => $this->formatDate($value->created_at),
                            "shopify_updated_at" => $this->formatDate($value->updated_at),
                            "shopify_published_at" => $this->formatDate($value->published_at),
                            'created_at' => CommonHelper::getInsertedDateTime(),
                        );

                        if (count($value->variants) == 1) {
                            if (trim($value->variants[0]->title) == "Default Title") {
                                if (isset($value->variants[0]->sku) && $value->variants[0]->sku != "") {
                                    $sku = $value->variants[0]->sku;
                                    $insertArray['0']['sku']   = $sku;
                                } 

                                $insertArray['0']['is_variation']          = "0";
                                $insertArray['0']['variant_unique_id']     = $value->variants[0]->id;
                              
                                $insertArray['0']['price']                 = isset($value->variants[0]->price) ? $value->variants[0]->price : NULL;
                                $insertArray['0']['quantity']              = isset($value->variants[0]->inventory_quantity) ? $value->variants[0]->inventory_quantity : NULL;
                                $insertArray['0']['grams']                 = isset($value->variants[0]->grams) ? $value->variants[0]->grams : NULL;
                                $insertArray['0']['weight']                = isset($value->variants[0]->weight) ? $value->variants[0]->weight : NULL;
                                $insertArray['0']['weight_unit']           = isset($value->variants[0]->weight_unit) ? $value->variants[0]->weight_unit : NULL;
                                $insertArray['0']['old_inventory_quantity'] = isset($value->variants[0]->old_inventory_quantity) ? $value->variants[0]->old_inventory_quantity : NULL;
                                $insertArray['0']['inventory_item_id']     = isset($value->variants[0]->inventory_item_id) ? $value->variants[0]->inventory_item_id : NULL;
                                $insertArray['0']['barcode']               = isset($value->variants[0]->barcode) ? $value->variants[0]->barcode : NULL;
                                $insertArray['0']['upc']                   = isset($value->variants[0]->barcode) ? $value->variants[0]->barcode : NULL;
                                $insertArray['0']['fulfillment_service']   = isset($value->variants[0]->fulfillment_service) ? $value->variants[0]->fulfillment_service : NULL;
                                $insertArray['0']['requires_shipping']     = isset($value->variants[0]->requires_shipping) ? $value->variants[0]->requires_shipping : NULL;
                            }
                        }

                        if (!empty($insertArray)) {
                            ShopifyProduct::insert($insertArray);
                            $createdId = DB::getPdo()->lastInsertId();
                        }

                        // insert data in shopify images //
                        if (count($value->images) > 0) {
                            $this->insertShopifyProductImage($value->images, $createdId);
                        }

                        // insert data in shopify variation products //
                        if (count($value->variants) > 0) {
                            if (count($value->variants) == 1 && trim($value->variants[0]->title) == "Default Title") {
                                continue;
                            } else {
                                $this->saveVariationProduct($value->variants, $createdId, "", $value->options, $value);
                            }
                        }
                    }
                }

                // $this->shopifyFlag = $i + 1;
                    
                // if ($this->shopifyFlag >= $count) {
                //     $this->shopifyFlag = 1;
                //     $storeCredential = [
                //         "shopify_flag" => $this->shopifyFlag,
                //         'shopify_product_token' => null,
                //     ];
                // } else {
                //     $storeCredential = [
                //         "shopify_flag" => $this->shopifyFlag
                //     ];
                // }

                // StoreCredential::where('store_id', $this->cron['store_id'])
                // ->update($storeCredential);
            }

            if (Carbon::now()->format('Y-m-d H:i:s') >= $this->endTime) {
                break;
            }
            // if (isset($shopifyProducts->products) && count($shopifyProducts->products) < $this->shopifyLimit) {

            //     StoreCredential::where('store_id', $this->cron['store_id'])
            //     ->update([
            //         "shopify_product_token" => null,
            //         'shopify_flag' => 1
            //     ]);

            //     break;
            // }
        }
    }

    /*
      @Description  : Function to save the variations
      @Author       : Sanjay Chabhadiya
      @Input        :
      @Output       :
      @Date         : 17-03-2021
    */
    public function saveVariationProduct($variationProduct, $createdId, $action = "", $options = [], $parentDetails = [])
    {
        $newVariationIds = array();

        $existingVariationIds = array();

        $existingVariationIds = ShopifyProduct::getExistingProductVariations($createdId);

        foreach ($variationProduct as $value) {
            $sku = "";

            if (isset($value->sku) && $value->sku != "") {
                $sku = $value->sku;
            } else {
                // Create a new SKU if SKU does not exists
                $sku = CommonHelper::getSku();
            }

            if (trim($value->title) == "Default Title") {
                // It does not have a variant
                ShopifyProduct::where('id', $createdId)
                ->update([
                    'inventory_management'  => isset($value->inventory_management) ? $value->inventory_management : NULL,
                ]);

            } else {
                // Get parent product type
                $parentProduct = ShopifyProduct::find($createdId);

                $newVariationIds[] = $value->id;

                $variationArray = array();

                $variationArray = array(
                    "unique_id"             => isset($value->id) ? $value->id : $value->unique_id,
                    "variant_unique_id"     => isset($value->id) ? $value->id : $value->variant_unique_id,
                    "store_id"              => $this->cron['store_id'],
                    "currency"              => $this->currency,
                    "parent_id"             => $createdId,
                    "sku"                   => $sku,
                    "title"                 => isset($value->title) ? $parentDetails->title.' - '.$value->title : NULL,
                    "price"                 => isset($value->price) ? $value->price : NULL,
                    "description"           => isset($value->body_html) ? $value->body_html : NULL,
                      "quantity"            => isset($value->inventory_quantity) ? $value->inventory_quantity : NULL,
                    "product_type"          => $parentProduct->product_type,
                    "is_variation"          => '1',
                    "options1"              => isset($value->option1) ? $options[0]->name.'||'.$value->option1 : NULL,
                    "options2"              => isset($value->option2) ? $options[1]->name.'||'.$value->option2 : NULL,
                    "options3"              => isset($value->option3) ? $options[2]->name.'||'.$value->option3 : NULL,
                    "position"              => isset($value->position) ? $value->position : NULL,
                    "grams"                 => isset($value->grams) ? $value->grams : NULL,
                    "taxable"               => isset($value->taxable) ? $value->taxable : NULL,
                    "weight"                => isset($value->weight) ? $value->weight : NULL,
                    "weight_unit"           => isset($value->weight_unit) ? $value->weight_unit : NULL,
                    "old_inventory_quantity" => isset($value->old_inventory_quantity) ? $value->old_inventory_quantity : NULL,
                    "inventory_item_id"     => isset($value->inventory_item_id) ? $value->inventory_item_id : NULL,
                    "barcode"               => isset($value->barcode) ? $value->barcode : NULL,
                    "upc"                   => isset($value->barcode) ? $value->barcode : NULL,
                    "compare_at_price"      => isset($value->compare_at_price) ? $value->compare_at_price : 0.00,
                    "fulfillment_service"   => isset($value->fulfillment_service) ? $value->fulfillment_service : NULL,
                    "inventory_management"  => isset($value->inventory_management) ? $value->inventory_management : NULL,
                    "requires_shipping"     => isset($value->requires_shipping) ? $value->requires_shipping : NULL,
                    "inventory_policy"      => isset($value->inventory_policy) ? $value->inventory_policy : NULL,
                    "shopify_created_at"    => isset($value->created_at) ? $this->formatDate($value->created_at) : $this->formatDate($value->shopify_created_at),
                    "shopify_updated_at"    => isset($value->updated_at) ? $this->formatDate($value->updated_at) : $this->formatDate($value->shopify_updated_at),
                    "handle"=>$parentDetails->handle,
                );

                if (isset($action) && $action == 'update') {
                    $variationArrays = array();

                    if (isset($existingVariationIds[trim($value->id)])) {
                        
                        unset($variationArray['sku']);

                        ShopifyProduct::where('unique_id', $variationArray['unique_id'])->update($variationArray);
                    } else {   // insert variation if any new ////
                        ShopifyProduct::create($variationArray);

                        $this->saveVariationProduct($variationArrays, $createdId, "", $options, $parentDetails);
                    }
                } else {
                    ShopifyProduct::create($variationArray);
                }
            }
        }

        ////////////

        $deletedIds = array_diff($existingVariationIds, $newVariationIds);

        if (!empty($deletedIds) && count($deletedIds) > 0) {
            $deleteProducts = array();

            foreach ($deletedIds as $deletedUniqueId) {
                $deleteProducts[] = $deletedUniqueId;
            }

            if (!empty($deleteProducts)) {
                ShopifyProduct::whereIn('unique_id', $deleteProducts)->delete();
            }
        }
    }

    /*
      @Description: Function to insert product images
      @Author:     Sanjay Chabhadiya
      @Input:
      @Output:
      @Date:        17-03-2021
     */
    public function insertShopifyProductImage($variationImages, $createdId) 
    {
        $imagesArray = array();
        $updateVariationImages = array();

        foreach ($variationImages as $image) {
            $product = ShopifyProduct::find($createdId);

            $mainImage = !empty($product->main_image) ? $product->main_image : null;

            // If image is not main image
            if ($mainImage != $image->src) 
            {
                $imagesArray[] = array(
                    "shopify_product_id"    => $createdId,
                    "position"              => $image->position,
                    "width"                 => $image->width,
                    "height"                => $image->height,
                    "image_url"             => $image->src,
                );
            }

            if (isset($image->variant_ids) && !empty($image->variant_ids))
            {
                foreach ($image->variant_ids as $variationId) 
                {
                    $updateVariationImages = array(
                        'variant_unique_id'     => $variationId,
                        'main_image'            => $image->src
                    );
                    ShopifyProduct::where('variant_unique_id', $variationId)->update($updateVariationImages);
                }
            }
        }

        if (!empty($imagesArray)) {
            ShopifyProductImage::insert($imagesArray);
        }
    }

    /*
      @Description: Function to get the product locations
      @Author:     Sanjay Chabhadiya
      @Input:
      @Output:
      @Date:        15-02-2021
     */
    public function getProductLocations($storeId)
    {   
        // Get store config for store id
        $storeConfig = Store::getStoreConfig($storeId);

        if (!empty($storeConfig)) {
            $productsToUpdate = ShopifyProduct::getProductLocations($storeId);

            // Do not send the location query for products more than 50
            $itemIdArrays = array_chunk($productsToUpdate, $this->locationLimit);

            foreach($itemIdArrays as $itemIdList) {
                $itemIds = array_column($itemIdList, 'inventory_item_id');

                $itemIds = implode(',', $itemIds);

                $response = $this->shopify->getProductLocation($itemIds);

                if (isset($response->inventory_levels)) {
                    $updateArray = array();

                    $inventoryLevels = $response->inventory_levels;

                    foreach($inventoryLevels as $levelDetail) {
                        $itemList = array_filter($itemIdList,function($value) use ($levelDetail){
                          return $value['inventory_item_id'] == $levelDetail->inventory_item_id;
                        }, ARRAY_FILTER_USE_BOTH);
                        if (!empty($itemList)) {
                            foreach ($itemList as $item) {
                                $updateArray[] = [
                                    'id'            => $item['id'],
                                    'location_id'   => $levelDetail->location_id
                                ];
                            }
                        }
                    }

                    ShopifyProduct::batchUpdate($updateArray, 'id');
                }
            }
        }
    }

    private function formatDate($date)
    {
        return Carbon::createFromTimestamp(strtotime($date))->format('Y-m-d H:i:s');
    }
}