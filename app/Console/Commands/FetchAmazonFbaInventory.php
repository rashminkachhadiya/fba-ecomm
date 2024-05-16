<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Models\AmazonCronErrorLog;
use App\Models\AmazonCronLog;
use App\Models\AmazonProduct;
use App\Models\AmazonReportLog;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tops\AmazonSellingPartnerAPI\Api\ReportsApi20210630;
use Tops\AmazonSellingPartnerAPI\Helpers\FeedHelper;
use App\Services\CronCommonService;

class FetchAmazonFbaInventory extends Command
{
    use FeedHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-amazon-fba-inventory {store_id?}';

    protected $configArr = [];
    protected $reportRequestId = '';
    protected $reportData = [];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Amazon FBA Inventory Details';

    // Set cron data   
    protected $cron = [
        'hour' => '',
        'date' => '',
        'report_type' => 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA',
        'cron_title' => 'FBA_PRODUCT_UPDATE',
        'cron_name' => '',
        'store_id' => '',
        'fetch_report_log_id' => '',
        'report_source' => '1', //SP API
        'report_freq' => '2', //Daily
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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
        $storeId = $this->argument('store_id');

        if (empty($storeId)) {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fetch-amazon-fba-inventory', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'CRON_' . time();
            $this->updateAmazonFbaProduct($storeId);
        }
    }

    public function updateAmazonFbaProduct($storeId)
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
                'report_type' => $this->cron['report_type'],
            ];

            // Call the FBA Shipment List
            $this->fetchFbaProducts($storeId);

            // Log cron end
            $addedCron->updateEndTime();
        }
    }

    private function fetchFbaProducts($storeId = null)
    {
        // If store id is not numm or zero
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
                    $reportDocument = $amazonSpApi->getReportDocument($result['reportDocumentId']);

                    //Function call downloadDocument function for download report document
                    $response = $this->downloadFeedDocument_New($reportDocument);

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
                } elseif (isset($result['processingStatus']) && ($result['processingStatus'] == 'CANCELLED' || $result['processingStatus'] == 'FATAL')) {

                    AmazonReportLog::where('request_id', $amazonReportLog->request_id)
                        ->update([
                            "is_processed" => "2"
                        ]);
                } elseif (isset($result['errors']) && !empty($result['errors'])) {
                    AmazonReportLog::where('request_id', $amazonReportLog->request_id)
                        ->update([
                            "is_processed" => "2"
                        ]);

                    $this->sendErrorLog($result);
                }
            } else {
                //Check if already requested from other source
                $bodyReports = array(
                    'reportTypes' => $this->cron['report_type'],
                    'marketplaceIds' => $this->configArr['marketplace_ids'],
                    'pageSize' => 1
                );

                $response = $amazonSpApi->getReports($bodyReports);

                if (!empty($response) && isset($response['errors'])) {
                    Log::info('response', $response);
                    return;
                }

                $responseArr = json_decode(json_encode($response), true);
                $requestId = '';
                if (isset($responseArr['reports']) && count($responseArr['reports']) > 0) {
                    $last_request_time = date('Y-m-d H:i', strtotime($responseArr['reports'][0]['createdTime']));
                    $current_time = date('Y-m-d H:i', strtotime("-40 minutes", time()));

                    if ($last_request_time >= $current_time) {
                        $requestId = $responseArr['reports'][0]['reportId'];
                    }
                }

                if (empty($requestId)) {
                    $body = array(
                        'reportType' => $this->cron['report_type'],
                        'marketplaceIds' => $this->configArr['marketplace_ids'],
                    );

                    $response = $amazonSpApi->createReport($body);
                    $responseArr = json_decode(json_encode($response), true);

                    $requestId = $responseArr['reportId'];
                }

                $data = [
                    'report_type'       => $this->cron['report_type'],
                    'request_id'        => $requestId,
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

        // If store id and report data not empty
        if (!empty($reportData)) {
            $reportData = rtrim($reportData, "\n");

            $reportData = str_replace("\t\n", "\n", $reportData);
            
            $existingSkuList = AmazonProduct::fbaProductExistingSku($storeId);

            $updateSku = $updateArchivedSku = array();
            $excelColumnMapping = $this->getExcelColumnMapping($reportData);

            if (is_array($excelColumnMapping) && !empty($excelColumnMapping) && isset($excelColumnMapping['sku'], $excelColumnMapping['afn-listing-exists'], $excelColumnMapping['afn-total-quantity'], $excelColumnMapping['afn-warehouse-quantity'])) {
                foreach (explode("\n", $reportData) as $key => $row) {
                    if ($key != 0) // IGNORE FIRST LINE OF REPORT WHICH CONTAINS TITLE OF THE FIELDS
                    {
                        $row = rtrim($row, "\t");
                        $row = explode("\t", $row);
                        $sku = trim($row[$excelColumnMapping['sku']]);
                        $sku = htmlspecialchars_decode($sku);
                        $fnsku = trim($row[$excelColumnMapping['fnsku']]);
                        $fbaWarehouseQty = trim($row[$excelColumnMapping['afn-fulfillable-quantity']]);
                        $reservedFcTransfers = isset($existingSkuList[$sku]) && !empty($existingSkuList[$sku]) ? $existingSkuList[$sku]->reserved_fc_transfers : 0;
                        $reservedFcProcessing = isset($existingSkuList[$sku]) && !empty($existingSkuList[$sku]) ? $existingSkuList[$sku]->reserved_fc_processing : 0;
                        $fbaReserveQty = $reservedFcTransfers + $reservedFcProcessing;
                        $afnInboundWorkingQuantity = isset($row[$excelColumnMapping['afn-inbound-working-quantity']]) ? trim($row[$excelColumnMapping['afn-inbound-working-quantity']]) : null;
                        $afnInboundShippedQuantity = isset($row[$excelColumnMapping['afn-inbound-shipped-quantity']]) ? trim($row[$excelColumnMapping['afn-inbound-shipped-quantity']]) : null;
                        $afnInboundReceivingQuantity = isset($row[$excelColumnMapping['afn-inbound-receiving-quantity']]) ? trim($row[$excelColumnMapping['afn-inbound-receiving-quantity']]) : null;

                        if (isset($existingSkuList[$sku])) {
                            $updateSku = array(
                                'id'            => $existingSkuList[$sku]->id,
                                'fnsku'         => $fnsku,
                                'qty'           => $fbaWarehouseQty,
                                'afn_reserved_quantity' => $fbaReserveQty,
                                'afn_inbound_working_quantity' => $afnInboundWorkingQuantity,
                                'afn_inbound_shipped_quantity' => $afnInboundShippedQuantity,
                                'afn_inbound_receiving_quantity' => $afnInboundReceivingQuantity
                            );

                            $existingSkuList[$sku]->update($updateSku);

                            unset($existingSkuList[$sku]);
                        }
                    }
                }
                
                if (!empty($existingSkuList)) {
                    foreach ($existingSkuList as $row) {
                        $updateArchivedSku[] = array('id' => $row->id);
                    }
                }
                
                if (!empty($updateArchivedSku)) {
                    AmazonProduct::whereIn('id', $updateArchivedSku)
                        ->update([
                            'qty' => 0,
                            'afn_reserved_quantity' => 0,
                            'afn_unsellable_quantity' => 0,
                            'afn_inbound_working_quantity' => 0,
                            'afn_inbound_shipped_quantity' => 0,
                            'afn_inbound_receiving_quantity' => 0
                        ]);
                }
            }
        }
    }

    public function getExcelColumnMapping($reportData)
    {
        $excelColumnMapping = array();

        $reportDataExp = explode("\n", $reportData);

        if (!empty($reportDataExp)) {
            $reportDataExp = $reportDataExp[0];

            $reportDataExp = rtrim($reportDataExp, "\t");

            $excelColumns = explode("\t", $reportDataExp);

            foreach ($excelColumns as $key => $value) {
                $excelColumns[$key] = trim($value);
            }

            $excelColumnMapping = array_flip($excelColumns);
        }

        return $excelColumnMapping;
    }

    public function sendErrorLog($error = null)
    {
        $logdata =  [
            'store_id' => $this->cron['store_id'],
            'batch_id' => null,
            'module' => 'Amazon FBA Inventory details',
            'submodule' => $this->cron['cron_name'],
            'error_content' => serialize($error)
        ];
        AmazonCronErrorLog::logError($logdata);
    }
}
