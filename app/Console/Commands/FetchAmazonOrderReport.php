<?php

namespace App\Console\Commands;

use App\Traits\AmazonOrderReportTrait;
use App\Models\AmazonOrderReport;
use App\Models\AmazonProduct;
use App\Services\CronCommonService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\ReportsApi20210630;
use Tops\AmazonSellingPartnerAPI\Helpers\FeedHelper;
use Batch;

class FetchAmazonOrderReport extends Command
{
    use FeedHelper, AmazonOrderReportTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-amazon-order-report {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch order report from amazon';

    protected $cronService;
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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        if (empty($this->argument('store_id'))) {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetch-amazon-order-report', [
                        'store_id' => $store->id,
                    ]);
                }
            }
        } else {
            if(is_numeric($this->argument('store_id')))
            {
                $this->cronService->storeId = $this->argument('store_id');
                $this->fetchAmazonOrderReport();
            }
            return;
        }
    }

    public function fetchAmazonOrderReport()
    {
        // Set cron name & cron type
        $this->cronService->cronName = 'CRON_'.time().'_'.$this->cronService->storeId;
        $this->cronService->cronType = 'FETCH_AMAZON_ORDER_REPORT';
        
        // Get store config for store id
        $storeConfig = $this->cronService->getStore($this->cronService->storeId);

        // If store config found
        if (!isset($storeConfig->id)) {
            return;
        }
            
        // Log cron start
        $cronLog = $this->cronService->storeCronLogs();

        if(!$cronLog) return;

        // Set cron report type
        $this->cronService->reportType = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL';

        // Set report api config for authorize amazon sp api
        $this->cronService->setReportApiConfig($storeConfig);
            
        // Call the FBA Shipment List
        $this->invokeAmzonOrderReportApi();
            
        // Log cron end
        $this->cronService->updateCronLogs();
    }

    private function invokeAmzonOrderReportApi()
    {
        $latestOrderDatetime = AmazonOrderReport::getLatestRecord();

        if (!empty($latestOrderDatetime)) {
            $dateTime = Carbon::parse($latestOrderDatetime)->subHours(8);
        } else {
            $dateTime = Carbon::parse(Carbon::now()->subDays(30)->format('Y-m-d'));
        }

        $modTimeFrom = $dateTime->format('Y-m-d\TH:i:s');

        try {
            $amazonSpApi = new ReportsApi20210630($this->cronService->reportApiConfig);

            $amazonReportLog = $this->cronService->getAmazonReportLog();

            $inserted = !empty($amazonReportLog) ? $amazonReportLog->created_at->format(config('constants.INSERT_DATE_FORMAT')) : '';

            // Get Report Request Id
            if (!empty($amazonReportLog->id) && !empty($amazonReportLog->request_id)) 
            {
                // Now get amazan report list based on report request id
                $result = $amazonSpApi->getReport($amazonReportLog->request_id);

                // If success to get report id
                if (isset($result['processingStatus']) && $result['processingStatus'] == 'DONE') 
                {
                    // Function call to get report document api
                    $report_document = $amazonSpApi->getReportDocument($result['reportDocumentId']);

                    //Function call downloadDocument function for download report document
                    $response = $this->downloadFeedDocument_New($report_document);

                    // If success to get report data
                    if (!empty($response) && !isset($response['errorCode'])) {
                        // Function to save or update products
                        $this->saveData($response);

                        // For update amazon report log entry
                        $this->cronService->updateAmazonReportLog($amazonReportLog->request_id, 1);
                    } else {
                        $time = new \DateTime($inserted);
                        $time->add(new \DateInterval('PT' . 50 . 'M'));
                        $addedTime = $time->format(config('constants.INSERT_DATE_FORMAT'));
                        $currentTime = date('Y-m-d H:m:i');
                        if ($currentTime > $addedTime) {
                            // For update amazon report log entry
                            $this->cronService->updateAmazonReportLog($amazonReportLog->request_id, 2);
                        } else {
                            // Store amazon cron error log
                            $this->cronService->storeAmazonCronErrorLog($response);
                        }
                    }
                } else if (isset($result['processingStatus']) && ($result['processingStatus'] == 'CANCELLED' || $result['processingStatus'] == 'FATAL')) {
                    // For update amazon report log entry
                    $this->cronService->updateAmazonReportLog($amazonReportLog->request_id, 2);
                } elseif(isset($result['errors']) && !empty($result['errors'])) {
                    // For update amazon report log entry
                    $this->cronService->updateAmazonReportLog($amazonReportLog->request_id, 2);
                    
                    // Store amazon cron error log
                    $this->cronService->storeAmazonCronErrorLog(json_encode($result['errors']));
                }
            } else {
                $body = array(
                    'reportType' => $this->cronService->reportType,
                    'marketplaceIds' => $this->cronService->reportApiConfig['marketplace_ids'],
                    'dataStartTime' => $modTimeFrom,
                );

                $response = $amazonSpApi->createReport($body);
                $responseArr = json_decode(json_encode($response), true);

                if(isset($responseArr['reportId']) && !empty($responseArr['reportId']))
                {
                    $request_id = $responseArr['reportId'];
                    // Insert entry report log entry
                    if (!empty($request_id)) {
                        $this->cronService->storeAmazonReportLog($request_id);
                    }
                }
            }

        } catch (\Exception$e) {
            // Store error log
            $this->cronService->storeAmazonCronErrorLog($e->getMessage() . ' - ' . $e->getLine());
        }
    }
    
    private function saveData($reportData)
    {
        // If store id and report data not empty
        if (!empty($reportData)) 
        {
            $reportData = rtrim($reportData, "\n");
            $reportData = str_replace("\t\n", "\n", $reportData);

            $insertDataArr = $updateDataArr = $orderReportData = [];

            $excelColumnMapping = $this->cronService->getExcelColumnMapping($reportData);

            if (is_array($excelColumnMapping) && !empty($excelColumnMapping) && isset($excelColumnMapping['sku'], $excelColumnMapping['amazon-order-id'], $excelColumnMapping['purchase-date'])) 
            {
                $allSku = [];
                $amazonOrderId = [];
                foreach (explode("\n", $reportData) as $key => $row) 
                {
                    // IGNORE FIRST LINE OF REPORT WHICH CONTAINS TITLE OF THE FIELDS
                    if ($key != 0) 
                    {
                        $row = rtrim($row, "\t");
                        $row = explode("\t", $row);

                        $sku = trim($row[$excelColumnMapping['sku']]);
                        $sku = htmlspecialchars_decode($sku);
                        $allSku[] = $sku;

                        $PurchaseDate = isset($row[$excelColumnMapping['purchase-date']]) ? Carbon::parse($row[$excelColumnMapping['purchase-date']])->setTimezone('UTC')->format(config('constants.INSERT_DATE_FORMAT')) : null;
                        $LastUpdateDate = isset($row[$excelColumnMapping['last-updated-date']]) ? Carbon::parse($row[$excelColumnMapping['last-updated-date']])->setTimezone('UTC')->format(config('constants.INSERT_DATE_FORMAT')) : null;

                        //Convert to PST time
                        $OrderDate = isset($row[$excelColumnMapping['purchase-date']]) ? Carbon::parse($row[$excelColumnMapping['purchase-date']])->setTimezone(config('constants.INSERT_ORDER_DATE_TIMEZONE'))->format(config('constants.INSERT_DATE_FORMAT')) : null;

                        $fetchProductName = $this->cronService->isExistCol($row[$excelColumnMapping['product-name']],'trim');
                        $productName = preg_match('/"/', $fetchProductName) ? str_replace('"', '', $fetchProductName) : $fetchProductName;

                        $reportData = [
                            'purchase_date' => $PurchaseDate,
                            'last_updated_date' => $LastUpdateDate,
                            'order_date' => $OrderDate,
                            'product_name' => utf8_encode($productName),
                        ];

                        $createOrderReportData = $this->cronService->createOrderReportData($excelColumnMapping, $row, $this->excelColumnList(), $reportData);
                        $amazonOrderId[] =$createOrderReportData['amazon_order_id'];
                        array_push($orderReportData, $createOrderReportData);
                        
                    }
                }

                
                if(empty($allSku)) {
                    return;
                }
                
                $amazonProduct = $this->getAmazonProduct($allSku);
                
                
                if(empty($amazonProduct)) {
                    return;
                }
                
                $amazonProductId = array_column($amazonProduct, 'id');
                $alreadyExist = $this->existOrderReportData($amazonProductId, $amazonOrderId);
                
                if(!empty($orderReportData))
                {
                    foreach ($orderReportData as $value) {
                        if (isset($amazonProduct[$value['sku']])) {
                            $value['product_id'] = $amazonProduct[$value['sku']]['id'];
                            $value['store_id'] = $amazonProduct[$value['sku']]['store_id'];
                        }else{
                            $value['product_id'] = 0;
                            $value['store_id'] = 0;
                        }
                        
                        if(!empty($alreadyExist) && in_array($value['amazon_order_id'], array_column($alreadyExist, 'amazon_order_id')) && in_array($value['product_id'], array_column($alreadyExist, 'product_id')))
                        {
                            $value['id'] = $alreadyExist[array_search($value['amazon_order_id'], array_column($alreadyExist, 'amazon_order_id'))]['id'];
                            unset($value['amazon_order_id']);
                            array_push($updateDataArr, $value);
                        }else{
                            array_push($insertDataArr, $value);
                        }
                    }
                }

                if (!empty($insertDataArr)) {
                    Batch::insert(new AmazonOrderReport, array_keys($insertDataArr[0]), $insertDataArr, 500);
                }

                if (!empty($updateDataArr)) {
                    $chunkedUpdateDataArrArr = array_chunk($updateDataArr, config('constants.BATCH_UPDATE_LIMIT'));
                    foreach ($chunkedUpdateDataArrArr as $chunkedUpdateDataArr) {
                        Batch::update(new AmazonOrderReport, $chunkedUpdateDataArr, 'id');
                    }
                }
            }
        }
    }
}
