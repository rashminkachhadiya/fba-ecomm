<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Models\AmazonCronErrorLog;
use App\Models\AmazonCronLog;
use App\Models\AmazonProduct;
use App\Models\AmazonReportLog;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Batch;
use Tops\AmazonSellingPartnerAPI\Api\ReportsApi20210630;
use Tops\AmazonSellingPartnerAPI\Helpers\FeedHelper;
use App\Services\CronCommonService;

class FetchAmazonProduct extends Command
{
    use FeedHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-amazon-product {store_id?}';

    protected $configArr = [];
    protected $reportRequestId = '';
    protected $reportData = [];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Amazon Product';

    protected $cron = [
        // Set cron data       
        'hour' => '',
        'date' => '',
        'report_type' => 'GET_MERCHANT_LISTINGS_ALL_DATA',
        'cron_title' => 'FETCH_AMAZON_PRODUCT',
        'cron_name' => '',
        'store_id' => '',
        'fetch_report_log_id' => '',
        'report_source' => '1', //SP API    
        'report_freq' => '2', //Daily  
    ];

    // Expedited shipping array
    protected $expeditedShipping = [
        'SECOND'  => 'second_day',
        'NEXT'    => 'next_day',
        'SAME'    => 'same_day',
        'N'       => 'not_available',
        'DEFAULT' => 'not_available',
    ];

    protected CronCommonService $cronService;

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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
        $storeId = $this->argument('store_id');

        if (empty($storeId)) {
            $stores = $this->cronService->getStoreIds();
                
            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetch-amazon-product', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'CRON_' . time();
            $this->updateAmazonProduct($storeId);
        }
    }

    public function updateAmazonProduct($storeId)
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

            // $this->currency = $storeConfig->store_config->store_currency;

            $this->configArr = [
                'access_token' => $storeConfig->access_token,
                'marketplace_ids' => [$storeConfig->store_config->amazon_marketplace_id ?? ''],
                'access_key' => $storeConfig->aws_access_key_id,
                'secret_key' => $storeConfig->aws_secret_key,
                'region' => $storeConfig->store_config->amazon_aws_region,
                'host' => $storeConfig->store_config->aws_endpoint,
                'report_type' => $this->cron['report_type'],
            ];

            // Call the FBA Shipment List
            $this->fetchProducts($storeId);

            // Log cron end
            $addedCron->updateEndTime();
        }
    }

    private function fetchProducts($storeId = null)
    {
        if (!empty($storeId)) {

            $amazonSpApi = new ReportsApi20210630($this->configArr);

            $amazonReportLog = AmazonReportLog::getAmazonReportLog($storeId, $this->cron['report_type']);

            $inserted = !empty($amazonReportLog) ? $amazonReportLog->created_at->format(config('constants.INSERT_DATE_FORMAT')) : '';

            // Get Report Request Id
            if (!empty($amazonReportLog->id) && !empty($amazonReportLog->request_id)) {
                // Now get amazan report list based on report request id
                $result = $amazonSpApi->getReport($amazonReportLog->request_id);

                // If success to get report id
                if (isset($result['processingStatus']) && $result['processingStatus'] == 'DONE') {

                    // Function call to get report document api
                    $report_document = $amazonSpApi->getReportDocument($result['reportDocumentId']);

                    //Function call downloadDocument function for download report document
                    $response = $this->downloadFeedDocument_New($report_document);

                    // If success to get report data
                    if (!empty($response) && !isset($response['errorCode'])) {
                        // Function to update products
                        $this->reportRequestId = $amazonReportLog->request_id;
                        $this->saveProducts($response);

                        AmazonReportLog::where('id', $amazonReportLog->id)
                            ->update([
                                "is_processed" => "1"
                            ]);
                    } else {
                        $time = new \DateTime($inserted);
                        $time->add(new \DateInterval('PT' . 50 . 'M'));
                        $addedTime = $time->format(config('constants.INSERT_DATE_FORMAT'));
                        $currentTime = date('Y-m-d H:m:i');
                        if ($currentTime > $addedTime) {

                            AmazonReportLog::where('request_id', $amazonReportLog->request_id)
                                ->update([
                                    "is_processed" => "2"
                                ]);
                        } else {
                            // Check for error
                            $this->sendErrorLog($response);
                        }
                    }
                } else if (isset($result['processingStatus']) && ($result['processingStatus'] == 'CANCELLED' || $result['processingStatus'] == 'FATAL')) {

                    AmazonReportLog::where('request_id', $amazonReportLog->request_id)
                        ->update([
                            "is_processed" => "2"
                        ]);
                    
                } elseif(isset($result['errors']) && !empty($result['errors'])) {
                    AmazonReportLog::where('request_id', $amazonReportLog->request_id)
                        ->update([
                            "is_processed" => "2"
                        ]);

                    $this->sendErrorLog($result);
                }
            } else {
                // Call request report api and get request report id
                $body = array(
                    'reportType' => $this->cron['report_type'],
                    'marketplaceIds' => $this->configArr['marketplace_ids'],
                );

                // Call create report api and get request report id
                $response = $amazonSpApi->createReport($body);
                $responseArr = json_decode(json_encode($response), true);

                $data = [
                    'report_type'       => $this->cron['report_type'],
                    'request_id'        => isset($responseArr['reportId']) ? trim($responseArr['reportId']) : null,
                    'store_id'          => $storeId,
                    'is_processed'      => 0,
                    'requested_date'    => CommonHelper::getInsertedDateTime(),
                    'processed_date'    => CommonHelper::getInsertedDateTime(),
                    'cut_off_time'      => 3000,
                ];

                // Insert entry report log entry 
                if (!empty($data['request_id'])) {
                    AmazonReportLog::create($data);
                }
            }
        }
    }

    private function saveProducts($reportData)
    {
        $this->reportData = $reportData;
        $storeId = $this->argument('store_id');
        // $userId = $this->argument('user');

        // If store id and report data not empty
        if (!empty($storeId) && !empty($reportData)) {
            // triming data
            $reportData = rtrim($reportData, "\n");

            // replace tab with new line in report data
            $reportData = str_replace("\t\n", "\n", $reportData);

            // Get existing SKU from db for store id
            $existingSkuList = AmazonProduct::existingList([
                'storeId' => $storeId,
                'fieldName' => 'sku',
            ]);

            // Insert, update and inactive array to be updated
            $insertSku = $updateSku = $inactiveSku = [];
            $excelColumnMapping = $this->getExcelColumnMapping($reportData);
            // If excel columns more than 0
            if (!empty($excelColumnMapping)) {
                // If ASIN exists in column or not

                // Manilulating data from report
                foreach (explode("\n", $reportData) as $key => $row) {
                    if (0 != $key) {

                        // IGNORE FIRST LINE OF REPORT WHICH CONTAINS TITLE OF THE FIELDS
                        $row = rtrim($row, "\t");
                        $row = explode("\t", $row);

                        // Set all product data received from report
                        $sku = isset($excelColumnMapping['seller-sku']) ? trim($row[$excelColumnMapping['seller-sku']]) : '';
                        $asin = isset($excelColumnMapping['asin1']) ? trim($row[$excelColumnMapping['asin1']]) : '';
                        $title = isset($excelColumnMapping['item-name']) ? trim($row[$excelColumnMapping['item-name']]) : '';
                        $price = (isset($excelColumnMapping['price']) && !empty(trim($row[$excelColumnMapping['price']]))) ? trim($row[$excelColumnMapping['price']]) : 0;
                        $qty = isset($excelColumnMapping['quantity']) ? trim($row[$excelColumnMapping['quantity']]) : 0;
                        $productStatus = isset($excelColumnMapping['status']) ? trim($row[$excelColumnMapping['status']]) : '';
                        
                        $listingCreatedDate = isset($excelColumnMapping['open-date']) ? trim($row[$excelColumnMapping['open-date']]) : null;
                        $conditionNotes = isset($excelColumnMapping['item-note']) ? trim($row[$excelColumnMapping['item-note']]) : '';
                        $ifFulfilledByAmazon = isset($excelColumnMapping['fulfillment-channel']) ? trim($row[$excelColumnMapping['fulfillment-channel']]) : '';
                        $ifExpeditedShippingAvailable = isset($excelColumnMapping['expedited-shipping']) ? trim($row[$excelColumnMapping['expedited-shipping']]) : '';
                        $ifShipsInternationally = isset($excelColumnMapping['will-ship-internationally']) ? trim($row[$excelColumnMapping['will-ship-internationally']]) : '';

                        $itemDescription     = isset($row[1]) ? trim($row[1]) : ''; // item-description FROM 

                        // Title
                        $title = ('' != $title ? $title : null);

                        // Qty
                        $qty = ('' != $qty ? $qty : null);
                        // Notes
                        $conditionNotes = ('' != $conditionNotes ? $conditionNotes : null);

                        // created date
                        $listingCreatedDate = !empty($listingCreatedDate) ? date(config('constants.INSERT_DATE_FORMAT'), strtotime($listingCreatedDate)) : null;

                        // shipping available
                        $ifExpeditedShippingAvailable = ('' != $ifExpeditedShippingAvailable && isset($this->expeditedShipping[strtoupper($ifExpeditedShippingAvailable)]) ? $this->expeditedShipping[strtoupper($ifExpeditedShippingAvailable)] : $this->expeditedShipping['DEFAULT']);
                        $ifShipsInternationally = ('2' == $ifShipsInternationally ? '1' : '0');

                        if ($productStatus == 'Inactive') {
                            $isActive = 0;
                        } else if ($productStatus == 'Active') {
                            $isActive = 1;
                        } else {
                            $isActive = 2; // For Incomplete status
                        }

                        $upc = null;
                        if ($excelColumnMapping['product-id-type'] && trim($row[$excelColumnMapping['product-id-type']]) == '3') {
                            $upc = isset($excelColumnMapping['product-id']) ? trim($row[$excelColumnMapping['product-id']]) : null;
                        }

                        if (isset($existingSkuList[$storeId][$sku])) {

                            $updateDataArray = [
                                'id'            => $existingSkuList[$storeId][$sku]->id,
                                'sku'           => $sku,
                                'asin'          => $asin,
                                'title'         => utf8_encode($title),
                                'price'         => $price,
                                'is_active' => $isActive,
                                'upc' => $upc,
                                'deleted_at' => null,
                            ];

                            // If it is merchant fulfilment product, NOT FBA
                            if (strtoupper($ifFulfilledByAmazon) == 'DEFAULT') {
                                $updateDataArray['qty'] = $qty;
                                $updateDataArray['if_fulfilled_by_amazon'] = '0';
                            } else {
                                $updateDataArray['if_fulfilled_by_amazon'] = '1';
                                $updateDataArray['qty'] = $existingSkuList[$storeId][$sku]->qty;
                            }

                            // Push into update SKU
                            $updateSku[] = $updateDataArray;

                            AmazonProduct::where('id', $updateDataArray['id'])
                                ->update($updateDataArray);

                            $updateDataArray = [];
                            $updateSku = [];
                            // remove from master SKU
                            unset($existingSkuList[$storeId][$sku]);
                        } else {
                            $ifFulfilledByAmazon = (strtoupper($ifFulfilledByAmazon) == 'DEFAULT' ? '0' : '1');

                            $insertSku[] = [
                                'store_id'                        => $storeId,
                                'sku'                             => $sku,
                                'asin'                            => $asin,
                                'title'                           => utf8_encode($title),
                                'description'                     => utf8_encode($itemDescription),
                                'price'                           => $price,
                                'qty'                             => $qty,
                                'listing_created_date'            => $listingCreatedDate,
                                'if_fulfilled_by_amazon'          => $ifFulfilledByAmazon,
                                'is_active'                       => $isActive,
                                'upc' => $upc,
                                'created_at'                      => CommonHelper::getInsertedDateTime(),
                                'deleted_at' => null,
                            ];
                        }
                    }
                }
                // Mark other SKU as inactive array
                if (!empty($existingSkuList)) {
                    foreach ($existingSkuList as $clientData) {
                        if (!empty($clientData)) {
                            foreach ($clientData as $storeData) {
                                if (!empty($storeData)) {
                                    foreach ($storeData as $skuData) {
                                        $inactiveSku[] = $skuData->id;
                                    }
                                }
                            }
                        }
                    }
                }

                // Insert new SKU
                if (!empty($insertSku)) {
                    $batchSize = 500; // insert 500 (default), 100 minimum rows in one query
                    Batch::insert(new AmazonProduct, array_keys($insertSku[0]), $insertSku, $batchSize);
                }

                // Mark inactive which are not exists in report
                if (!empty($inactiveSku)) {
                    AmazonProduct::whereIn('id', $inactiveSku)->where('created_at', '<', Carbon::now()->subHours(2))->whereNULL('deleted_at')
                        ->update(['deleted_at' => CommonHelper::getInsertedDateTime()]);
                }
            }
            // END LOG ENTRY OF CRON                       

        }
    }

    /*@Description  : Function to get column mapping detail when importing product excel
    @Author         : Sanjay Chabhadiya
    @Input          :
    @Output         :
    @Date           : 09-03-2021
     */

    function getExcelColumnMapping($reportData)
    {
        $excelColumnMapping = [];

        $reportData = explode("\n", $reportData);

        if (!empty($reportData)) {
            $reportData = $reportData[0];

            $reportData = rtrim($reportData, "\t");

            $excelColumns = explode("\t", $reportData);

            $excelColumnMapping = array_flip($excelColumns);
        }

        ////////////////////////////

        $excelColumnMapping = $this->excelColumnLanguageMapping($excelColumnMapping);

        return $excelColumnMapping;
    }

    /*
    @Description    : Function to manage languages of columns
    @Author         : Sanjay Chabhadiya
    @Input          : 
    @Output         : 
    @Date           : 09-03-2021
    */
    function excelColumnLanguageMapping($excelColumnMapping)
    {
        $columnLanguagesMapping = array(
            "Nome dell'articolo" => "item-name",
            "Título del producto" => "item-name",
            "SKU venditore" => "seller-sku",
            "SKU del vendedor"  => "seller-sku",
            "Prezzo" => "price",
            "Precio" => "price",
            "Quantità" => "quantity",
            "Cantidad" => "quantity",
            "Data di creazione" => "open-date",
            "Note sull'articolo" => "item-note",
            "Condizione dell'articolo" => "item-condition",
            "ASIN 1" => "asin1",
            "Spedizione internazionale" => "will-ship-internationally",
            "Spedizione Express" => "expedited-shipping",
            "Canale di gestione" => "fulfillment-channel",
        );

        foreach ($excelColumnMapping as $columnName => $columnNumber) {
            if (isset($columnLanguagesMapping[$columnName])) {
                $excelColumnMapping[$columnLanguagesMapping[$columnName]] = $columnNumber;

                unset($excelColumnMapping[$columnName]);
            } else {
                $columnName = utf8_encode($columnName);

                if (isset($columnLanguagesMapping[$columnName])) {
                    $excelColumnMapping[$columnLanguagesMapping[$columnName]] = $columnNumber;

                    unset($excelColumnMapping[utf8_decode($columnName)]);
                }
            }
        }

        return $excelColumnMapping;
    }

    /*@Description  : Function to send error log to update in db
    @Author         : Sanjay Chabhadiya
    @Input          :
    @Output         :
    @Date           : 09-03-2021
     */

    function sendErrorLog($error = null)
    {
        $logdata =  [
            'store_id' => $this->cron['store_id'],
            'batch_id' => null,
            'module' => 'Amazon Product Report List Cron',
            'submodule' => $this->cron['cron_name'],
            'error_content' => serialize($error)
        ];
        AmazonCronErrorLog::logError($logdata);
    }
}
