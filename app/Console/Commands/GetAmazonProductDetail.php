<?php

namespace App\Console\Commands;

use App\Models\AmazonCronErrorLog;
use App\Models\AmazonCronLog;
use App\Models\AmazonProduct;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\CatalogItemsApi20220401;
use Batch;
use Illuminate\Support\Facades\Log;
use App\Services\CronCommonService;

class GetAmazonProductDetail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-amazon-product-detail {store_id?}';

    protected $configArr = [];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Amazon Product Details';

    protected $cron = [
        // Set cron data
        'hour' => '',
        'date' => '',
        'cron_title' => 'GET_AMAZON_PRODUCT_DETAIL',
        'cron_name' => '',
        'store_id' => ''
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
        $this->cronService = new CronCommonService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->argument('store_id');
        if (empty($storeId)) {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:get-amazon-product-detail', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'CRON_' . time();
            $this->updateProductCatelog($storeId);
        }
    }

    private function updateProductCatelog($storeId)
    {
        // If store id is not num or zero
        if (!empty(trim($storeId)) && (int) trim($storeId) != 0) {
            // Set store id
            $this->cron['store_id'] = $storeId = (int) trim($storeId);

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

            $this->configArr = [
                'access_token' => $storeConfig->access_token,
                'marketplace_ids' => [$storeConfig->store_config->amazon_marketplace_id ?? ''],
                'access_key' => $storeConfig->aws_access_key_id,
                'secret_key' => $storeConfig->aws_secret_key,
                'region' => $storeConfig->store_config->amazon_aws_region,
                'host' => $storeConfig->store_config->aws_endpoint,
            ];
            // Call the product catelog item detail cron
            $this->invokeCatelogItemApi($storeId);

            // Log cron end
            $addedCron->updateEndTime();
        }
    }

    private function invokeCatelogItemApi($storeId)
    {
        $getProductsAsin = AmazonProduct::whereNotNull('asin')
                                ->where('is_product_detail_updated',0)
                                ->select('id','asin','title','is_hazmat','package_height','package_length','package_weight','package_width','is_oversize')
                                ->take(100)
                                ->get();
                  
        try {
            $amazonSpApi = new CatalogItemsApi20220401($this->configArr);

            if (count($getProductsAsin) > 0) {
                $updateDataArr = [];
                
                foreach ($getProductsAsin->chunk(20) as $getProductsAsin) 
                {
                    $asins = implode(',', array_column($getProductsAsin->toArray(), 'asin'));
                    $params = [
                        'marketplaceIds' => $this->configArr['marketplace_ids'][0],
                        'identifiers' => $asins,
                        'identifiersType' => 'ASIN',
                        'includedData' => 'attributes,images,dimensions',
                        'pageSize' => 20
                    ];
                    
                    $response = $amazonSpApi->searchCatalogItems($params);
                     
                    foreach ($getProductsAsin as $productData) {
                        $title = $productData->title;
                        $image = "";
                        $isHazmat = 0;
                       
                        $height = $productData->package_height ?? 0;
                        $length = $productData->package_length ?? 0;
                        $weight = $productData->package_weight ?? 0;
                        $width = $productData->package_width ?? 0;
                        $isOverSize = $productData->is_oversize ?? 0;
                        
                        $updateDataArr[$productData->asin] = ['asin' => $productData->asin];
                        
                        if (isset($response['errors'])) {
                            Log::info('response', $response['errors']);
                            if (isset($response['errors'][0]['code']) && $response['errors'][0]['code'] == 'Unauthorized') {
                                break;
                            }
                        } else {
                           
                            if (isset($response['items']) && !empty($response['items'])) {

                                foreach ($response['items'] as $item) {
                                    
                                    if (isset($updateDataArr[$item['asin']]) || isset($insertDataArr[$item['asin']])) 
                                    {
                                        $attributeSets = isset($item['attributes']['item_name']) ? $item['attributes']['item_name'] : [];
                                        if (isset($attributeSets[0]['value'])) {
                                            $title = preg_match('/"/', $attributeSets[0]['value']) ? str_replace('"', '', $attributeSets[0]['value']) : $attributeSets[0]['value'];
                                        }
                                        if(!empty($item['attributes']['hazmat']) && isset($item['attributes']['hazmat'])){
                                            foreach($response['attributes']['hazmat'] as $hazmatArray){
                                                if($hazmatArray['aspect'] == 'regulatory_packing_group'){
                                                    $isHazmat = 1;
                                                }
                                            }
                                        }
                                        $image = "";
                                        if (isset($item['images'][0]) && !empty($item['images'][0])) 
                                        {
                                            $imageSets = isset($item['images'][0]['images']) ? $item['images'][0]['images'] : [];
                                            if (!empty($imageSets)) {

                                                foreach ($imageSets as $imageKey => $imageSet) {
                                                    if ($imageSet['variant'] == 'MAIN' && ($imageSet['width'] == 75 || $imageSet['height'] == 75)) {
                                                        $image = isset($imageSet['link']) ? $imageSet['link'] : "";
                                                    } elseif ($imageKey == 0) {
                                                        $image = isset($imageSet['link']) ? $imageSet['link'] : "";
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if (isset($item['dimensions'][0]) && isset($item['dimensions'][0]['package'])) 
                                        {
                                            $package = !empty($item['dimensions'][0]['package']) ? $item['dimensions'][0]['package'] : [];
                                            $height = isset($package['height']) && isset($package['height']['value']) ? round($package['height']['value'], 3) : '0';
                                            $length = isset($package['length']) && isset($package['length']['value']) ? round($package['length']['value'], 3) : '0';
                                            $weight = isset($package['weight']) && isset($package['weight']['value']) ? round($package['weight']['value'], 3) : '0';
                                            $width = isset($package['width']) && isset($package['width']['value']) ? round($package['width']['value'], 3) : '0';

                                            if ($length > 18) {
                                                $isOverSize = 1;
                                            } elseif ($weight > 20) {
                                                $isOverSize = 1;
                                            } elseif ($width > 14) {
                                                $isOverSize = 1;
                                            } elseif ($height > 8) {
                                                $isOverSize = 1;
                                            } else {
                                                $isOverSize = 0;
                                            }
                                        }
                                        
                                        $updateDataArr[$item['asin']] = ['asin' => $item['asin'], 'title' => $title, 'main_image' => $image, 'is_hazmat' => $isHazmat, 'package_height' => $height, 'package_length' => $length, 'package_weight' => $weight, 'package_width' => $width, 'is_product_detail_updated' => '1','is_oversize' => $isOverSize];
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (!empty($updateDataArr)) {
                    Batch::update(new AmazonProduct, $updateDataArr, 'asin');
                }
            }
            
        } catch (\Exception $e) {
            Log::info($e->getMessage(), ['data']);
            // $message["Caught Exception"] = 'Something went wrong';
            $message["Caught Exception"] = $e->getMessage();
            $message["Response Status Code"] = $e->getCode();
            $message["File"] = $e->getFile();
            $message["Line"] = $e->getLine();

            $logdata =  [
                'store_id' => $this->cron['store_id'],
                'batch_id' => null,
                'module' => 'Get Amazon Product Detail Cron',
                'submodule' => $this->cron['cron_name'],
                'error_content' => serialize($message)
            ];

            AmazonCronErrorLog::logError($logdata);
        }
    }
}
