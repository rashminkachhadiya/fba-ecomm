<?php

namespace App\Console\Commands;

use App\Models\AmazonProduct;
use App\Services\CronCommonService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\ProductFeesApi;
use Tops\AmazonSellingPartnerAPI\Api\ProductPricingApi;
use Batch;
use Illuminate\Support\Facades\Log;

class FetchProductBuyboxPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-product-buybox-price {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get product buybox price & fba fees for all products';

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
        if (empty($this->argument('store_id'))) {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetch-product-buybox-price', [
                        'store_id' => $store->id,
                    ]);
                }
            }
        } else {
            if(is_numeric($this->argument('store_id')))
            {
                $this->cronService->storeId = $this->argument('store_id');

                $this->updateProductAnalyzerBySku();
            }
            return;
        }
    }

    private function updateProductAnalyzerBySku()
    {
        // Set cron name & cron type
        $this->cronService->cronName = 'CRON_'.time().'_'.$this->cronService->storeId;
        $this->cronService->cronType = 'UPDATE_PRODUCT_ANALYZER_BY_SKU';
        
        // Get store config for store id
        $storeConfig = $this->cronService->getStore($this->cronService->storeId);

        // If store config found
        if (!isset($storeConfig->id)) {
            return;
        }
        
        // Log cron start
        $cronLog = $this->cronService->storeCronLogs();

        if(!$cronLog) return;

        // Set report api config for authorize amazon sp api
        $this->cronService->setReportApiConfig($storeConfig);
        // Call the FBA Shipment List
        $this->invokeLiveDataApiBySku();

        // Log cron end
        $this->cronService->updateCronLogs();
    }

    private function invokeLiveDataApiBySku()
    {
        $amazonProducts = AmazonProduct::where('is_buybox_fetch', 0)
                                    ->where('store_id', $this->cronService->storeId)
                                    ->orderBy('id', 'asc')
                                    ->select('id','buybox_price','sku','price')
                                    ->take(400)
                                    ->get();

        try {
            $amazonSpApi = new ProductPricingApi($this->cronService->reportApiConfig);

            $unauthorized = false;
            if (count($amazonProducts) > 0) 
            {
                $updatePriceFeeArr = [];
                
                foreach ($amazonProducts->chunk(20) as $productAnalyzer)
                {
                    $amazonProductsArr = [];
                    $fetchBuyboxPriceParams = [];
                    $fetchReferralFeesSellingPriceParams = [];
                    $chunkAmazonProductsArr = [];
                    
                    foreach ($productAnalyzer as $value) 
                    {
                        $fetchBuyboxPriceParams[] = [
                            'MarketplaceId' => $this->cronService->reportApiConfig['marketplace_ids'][0],
                            'ItemCondition' => 'New',
                            'sku' => $value->sku
                        ];

                        $chunkAmazonProductsArr[$value->sku] = [
                            'id' => $value->id, 
                            'sku' => $value->sku, 
                            'is_buybox_fetch' => 1, 
                            'buybox_price' => $value->buybox_price
                        ];

                        if($value->price > 0)
                        {
                            $fetchReferralFeesSellingPriceParams[] = [
                                'MarketplaceId' => $this->cronService->reportApiConfig['marketplace_ids'][0],
                                'ItemCondition' => 'New',
                                'IdType' => 'SellerSKU',
                                'IdValue' => $value->sku,
                                'Amount' => $value->price,
                                'CurrencyCode' => "CAD"
                            ];
                        }
                    }
                    
                    $response = $amazonSpApi->getListingOffersBatch($fetchBuyboxPriceParams);
                  
                    if (!empty($response) && isset($response['responses']) && !empty($response['responses'])) 
                    {
                        $fetchReferralFeesBuyboxPriceParams = [];
                        foreach ($response['responses'] as $key => $resp) 
                        {
                            $bodyResponse = $resp['body'];

                            if (isset($bodyResponse['payload'])) 
                            {
                                $payload = $bodyResponse['payload'];
                                $buyboxPrice = 0;
                                $sellerID = null;

                                if (isset($payload['Summary']['BuyBoxPrices'])) 
                                {
                                    $buyboxPricesArray = $payload['Summary']['BuyBoxPrices'][0];

                                    if(is_numeric($buyboxPricesArray['LandedPrice']["Amount"]))
                                    {
                                        $buyboxPrice = $buyboxPricesArray['LandedPrice']["Amount"];
                                    }
                                }
                                
                                if ($buyboxPrice == 0 && !empty($payload['Summary']['LowestPrices'])) {
                                    $buyboxPricesArray = $payload['Summary']['LowestPrices'][0];

                                    if(is_numeric($buyboxPricesArray['LandedPrice']["Amount"]))
                                    {
                                        $buyboxPrice = $buyboxPricesArray['LandedPrice']["Amount"];
                                    }
                                }

                                if (!empty($payload) && !empty($payload['Offers']))
                                {
                                    foreach ($payload['Offers'] as $offer) 
                                    {
                                        $totalPrice = $offer['ListingPrice']['Amount'] + $offer['Shipping']['Amount'];
                                        if ($offer['IsBuyBoxWinner'] == 1) {
                                            $buyboxPrice = $totalPrice;
                                            $sellerID = $offer['SellerId'];
                                            break;
                                        } else {
                                            if (empty($buyboxPrice) || $buyboxPrice > $totalPrice) {
                                                $buyboxPrice = !empty($buyboxPrice) ? $buyboxPrice : $totalPrice;
                                                $sellerID = null;
                                            }
                                        }
                                    }
                                }
                                
                                $chunkAmazonProductsArr[$resp['request']['SellerSKU']]['buybox_price'] = $buyboxPrice;

                                $chunkAmazonProductsArr[$resp['request']['SellerSKU']]['buybox_seller_id'] = $sellerID;

                                if (isset($resp['request']) && isset($resp['request']['SellerSKU']))
                                {
                                    if($buyboxPrice > 0)
                                    {
                                        $idType = "SellerSKU";
                                        $idValue = $resp['request']['SellerSKU'];

                                        $fetchReferralFeesBuyboxPriceParams[] = [
                                            'MarketplaceId' => $this->cronService->reportApiConfig['marketplace_ids'][0],
                                            'ItemCondition' => 'New',
                                            'IdType' => $idType,
                                            'IdValue' => $idValue,
                                            'Amount' => round($buyboxPrice, 2),
                                            'CurrencyCode' => "CAD"
                                        ];
                                    }
                                }
                            } else if (isset($bodyResponse['errors'])) {
                                if (isset($bodyResponse['errors'][0]['code']) && $bodyResponse['errors'][0]['code'] == 'Unauthorized') {
                                    $unauthorized = true;
                                    break;
                                }

                                if (isset($bodyResponse['errors'][0]['code'])) {
                                    $chunkAmazonProductsArr[$resp['request']['SellerSKU']]['buybox_price'] = 0;
                                }
                            }else {
                                $chunkAmazonProductsArr[$resp['request']['SellerSKU']]['buybox_price'] = 0;
                            }
                        }

                        // call referral fees and fba fees api
                        $referralFeesBuyboxPrice = [];
                        if (!empty($fetchReferralFeesBuyboxPriceParams)) {
                            $referralFeesBuyboxPrice = $this->fetchProductFeesApi($fetchReferralFeesBuyboxPriceParams);
                        }
                        
                        foreach ($chunkAmazonProductsArr as $buyboxPriceValue)
                        {
                            $buyboxRefferalFees = 0;
                            $buyboxFbaFees = 0;

                            if (!empty($referralFeesBuyboxPrice))
                            {
                                foreach ($referralFeesBuyboxPrice as $referral)
                                {
                                    if ($buyboxPriceValue['sku'] == $referral['IdValue'])
                                    {
                                        $buyboxRefferalFees = $referral['ReferralFee'];
                                        $buyboxFbaFees = $referral['FBAFees'];
                                        break;
                                    }
                                    
                                }
                            }

                            $amazonProductsArr[] = [
                                'id' => $buyboxPriceValue['id'],
                                'sku' => $buyboxPriceValue['sku'],
                                'is_buybox_fetch' => $buyboxPriceValue['is_buybox_fetch'],
                                'buybox_price' => $buyboxPriceValue['buybox_price'],
                                'buybox_referral_fees' => $buyboxRefferalFees, 
                                'fba_fees' => $buyboxFbaFees,
                                'buybox_seller_id' => isset($buyboxPriceValue['buybox_seller_id']) ? $buyboxPriceValue['buybox_seller_id'] : null,
                            ];
                        }
                    }
                    // call referral fees and fba fees api for selling price
                    $referralFeesSellingPrice = [];
                    if (!empty($fetchReferralFeesSellingPriceParams)) {
                        $referralFeesSellingPrice = $this->fetchProductFeesApi($fetchReferralFeesSellingPriceParams);
                    }

                    foreach ($amazonProductsArr as $updateAmazonProductsArrValue)
                    {
                        $sellingPriceRefferalFees = 0;
                        if (!empty($referralFeesSellingPrice))
                        {
                            foreach ($referralFeesSellingPrice as $referralFeesSellingPriceValue)
                            {
                                if ($updateAmazonProductsArrValue['sku'] == $referralFeesSellingPriceValue['IdValue'])
                                {
                                    $sellingPriceRefferalFees = $referralFeesSellingPriceValue['ReferralFee'];
                                    break;
                                }
                            }
                        }

                        $updatePriceFeeArr[] = [
                            'id' => $updateAmazonProductsArrValue['id'],
                            'sku' => $updateAmazonProductsArrValue['sku'],
                            'is_buybox_fetch' => $updateAmazonProductsArrValue['is_buybox_fetch'],
                            'buybox_price' => $updateAmazonProductsArrValue['buybox_price'],
                            'buybox_referral_fees' => $updateAmazonProductsArrValue['buybox_referral_fees'],
                            'fba_fees' => $updateAmazonProductsArrValue['fba_fees'],
                            'referral_fees' => $sellingPriceRefferalFees,
                            'buybox_seller_id' => isset($updateAmazonProductsArrValue['buybox_seller_id']) ? $updateAmazonProductsArrValue['buybox_seller_id'] : null,
                        ];
                    }

                    if (!empty($updatePriceFeeArr) && count($updatePriceFeeArr) >= config('constants.BATCH_UPDATE_LIMIT')) {
                        Batch::update(new AmazonProduct, $updatePriceFeeArr, 'id');
                        unset($updatePriceFeeArr);
                        $updatePriceFeeArr = [];
                    }
                }
                
                if (!empty($updatePriceFeeArr)) {

                    Batch::update(new AmazonProduct, $updatePriceFeeArr, 'id');
                }
            }

            if (!$unauthorized) {
                $updatedSheets = AmazonProduct::select('id')->where('is_buybox_fetch', 0)->count();

                if ($updatedSheets <= 0) {
                    
                    AmazonProduct::select('id')->where('is_buybox_fetch', '1')->chunkById(config('constants.BATCH_UPDATE_LIMIT'), function ($rows) {
                        if (!empty($rows)) {
                            $keep_up = [];
                            foreach ($rows as $row) {
                                $keep_up[] = array("id" => $row->id, "is_buybox_fetch" => 0);
                            }
                            Batch::update(new AmazonProduct, $keep_up, 'id');
                        }
                    });
                }
            }
        } catch (\Exception $e) {
            $this->cronService->storeAmazonCronErrorLog($e->getMessage());
        }
    }

    private function fetchProductFeesApi(array $params) : array
    {
        $feeResult = [];
        try {
            $amazonSpApi = new ProductFeesApi($this->cronService->reportApiConfig);
            $response = $amazonSpApi->getMyFeesEstimates($params);
            
            if (!empty($response)) 
            {
                foreach ($response as $key => $resp) 
                {
                    $referralFees = 0;
                    $fbaFees = 0;

                    if (isset($resp['Status']) && $resp['Status'] == 'Success') 
                    {
                        if (isset($resp['FeesEstimate']) && !empty($resp['FeesEstimate']))
                        {
                            if (isset($resp['FeesEstimate']['FeeDetailList']) && !empty($resp['FeesEstimate']['FeeDetailList']))
                            {
                                foreach ($resp['FeesEstimate']['FeeDetailList'] as $FeeDetail)
                                {
                                    if ($FeeDetail['FeeType'] == 'ReferralFee') {
                                        $referralFees = isset($FeeDetail['FeeAmount']['Amount']) ? $FeeDetail['FeeAmount']['Amount'] : 0;
                                    }

                                    if ($FeeDetail['FeeType'] == 'FBAFees') {
                                        $fbaFees = isset($FeeDetail['FeeAmount']['Amount']) ? $FeeDetail['FeeAmount']['Amount'] : 0;
                                    }
                                }
                            }
                        }
                    }

                    $feeResult[$key] = [
                        'ReferralFee' => $referralFees,
                        'FBAFees' => $fbaFees,
                        'IdValue' => (isset($resp['FeesEstimateIdentifier']['IdValue']) ? $resp['FeesEstimateIdentifier']['IdValue'] : ''),
                        'IdType' => (isset($resp['FeesEstimateIdentifier']['IdType']) ? $resp['FeesEstimateIdentifier']['IdType'] : '')
                    ];
                }
            }

            sleep(2);
        } catch (\Exception $e) {
            $this->cronService->storeAmazonCronErrorLog($e->getMessage());
        }
        
        return $feeResult;
    }
}
