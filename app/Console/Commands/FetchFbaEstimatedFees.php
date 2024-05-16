<?php

namespace App\Console\Commands;

use App\Models\AmazonProduct;
use App\Services\CronCommonService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\ReportsApi20210630;
use Tops\AmazonSellingPartnerAPI\Helpers\FeedHelper;
use Batch;

class FetchFbaEstimatedFees extends Command
{
    use FeedHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-fba-estimated-fees {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Estimated Fees from Amazon';

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
                    Artisan::call('app:fetch-fba-estimated-fees', [
                        'store_id' => $store->id,
                    ]);
                }
            }
        } else {
            if(is_numeric($this->argument('store_id')))
            {
                $this->cronService->storeId = $this->argument('store_id');
                $this->updateProductReferralFees();
            }
            return;
        }
    }

    public function updateProductReferralFees()
    {
        // Set cron name & cron type
        $this->cronService->cronName = 'CRON_'.time().'_'.$this->cronService->storeId;
        $this->cronService->cronType = 'FETCH_AMAZON_PRODUCT_REFERRAL_FEE';
        
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
        $this->cronService->reportType = 'GET_FBA_ESTIMATED_FBA_FEES_TXT_DATA';

        // Set report api config for authorize amazon sp api
        $this->cronService->setReportApiConfig($storeConfig);

        // Call the FBA Shipment List
        $this->fetchEstimatedFees();
            
        // Log cron end
        $this->cronService->updateCronLogs();
    }

    private function fetchEstimatedFees()
    {
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
                if (isset($result['processingStatus']) && $result['processingStatus'] == 'DONE') {
                    
                    // Function call to get report document api
                    $reportDocument = $amazonSpApi->getReportDocument($result['reportDocumentId']);

                    //Function call downloadDocument function for download report document
                    $response = $this->downloadFeedDocument_New($reportDocument);
                    
                    // If success to get report data
                    if (!empty($response) && !isset($response['errorCode'])) {
                        // Function to update products
                        $this->saveProducts($response);

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
                } else if(isset($result['processingStatus']) && ($result['processingStatus'] == 'CANCELLED' || $result['processingStatus'] == 'FATAL')) {
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
                    'marketplaceIds' => $this->cronService->reportApiConfig['marketplace_ids']
                );

                // Call create report api and get request report id
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
        } catch (\Exception $e) {
            // Store error log
            $this->cronService->storeAmazonCronErrorLog($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    private function saveProducts($reportData)
    {
        // If store id and report data not empty
        if (!empty($reportData)) 
        {
            $reportData = rtrim($reportData, "\n");
            $reportData = str_replace("\t\n", "\n", $reportData);
            
            $existingSkuList = AmazonProduct::fbaProductReferralFeeSku($this->cronService->storeId);

            $updateSku = $updateDataArr = [];
            
            $excelColumnMapping = $this->cronService->getExcelColumnMapping($reportData);

            if(is_array($excelColumnMapping) && !empty($excelColumnMapping) && isset($excelColumnMapping['sku'],$excelColumnMapping['estimated-fee-total'],$excelColumnMapping['estimated-referral-fee-per-unit']))
            {
                foreach(explode("\n", $reportData) as $key=>$row)
                {
                    if($key != 0) // IGNORE FIRST LINE OF REPORT WHICH CONTAINS TITLE OF THE FIELDS
                    {
                        $row = rtrim($row, "\t");
                        $row = explode("\t", $row);

                        $sku = trim($row[$excelColumnMapping['sku']]);
                        $sku = htmlspecialchars_decode($sku);
                        // $fnsku = trim($row[$excelColumnMapping['fnsku']]) ;

                        // $estimatedFeeTotal = preg_replace("/[^0-9\.]+/", "", trim($row[$excelColumnMapping['estimated-fee-total']])) ;
                        $estimatedReferralFeePerUnit = preg_replace("/[^0-9\.]+/", "", trim($row[$excelColumnMapping['estimated-referral-fee-per-unit']]));
                        $expectedFulfillmentFeePerUnit = preg_replace("/[^0-9\.]+/", "", trim($row[$excelColumnMapping['expected-fulfillment-fee-per-unit']]));

                        if(isset($existingSkuList[$sku]))
                        {
                            $updateSku = array(
                                'id'            => $existingSkuList[$sku]->id,
                                'referral_fees' => $estimatedReferralFeePerUnit != "" ? $estimatedReferralFeePerUnit : null,
                                'fba_fees' => $expectedFulfillmentFeePerUnit != "" ? $expectedFulfillmentFeePerUnit : null,
                            );
                            $updateDataArr[] = $updateSku;
                        }
                    }
                }
            }

            if (!empty($updateDataArr)) {
                Batch::update(new AmazonProduct, $updateDataArr, 'id');
            }
        }  
    }
}
