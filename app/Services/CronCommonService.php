<?php

namespace App\Services;

use App\Helpers\CommonHelper;
use App\Models\AmazonCronErrorLog;
use App\Models\AmazonCronLog;
use App\Models\AmazonReportLog;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CronCommonService extends BaseService
{
    public array $reportApiConfig;
    /**
     * Get the Active Store List
     */
    public function getStoreIds($store_marketplace = 'Amazon') : Collection
    {
        return Store::select('id')->where('store_marketplace',$store_marketplace)->active()->get();
    }

    /**
     * Get the Active Store with particular Store Config
     * @param int $storeId
     */
    public function getStore(int $storeId) : Store
    {
        return Store::whereId($storeId)->active()
                ->with(['store_config' => function($query) {
                    $query->select('id','store_type','amazon_marketplace_id','store_currency','amazon_aws_region','aws_endpoint');
                }])
                ->first();
    }

    public function getCronReportConfigs() : array
    {
        return [
            ...($this->getCronConfigs()),
            'report_type' => 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL',
            'fetch_report_log_id' => '',
            'report_source' => '1', //SP API
            'report_freq' => '2', //Daily
        ];
    }

    public function getCronConfigs() : array
    {
        return [
            // 'hour' => $this->getHour(),
            // 'date' => $this->getDate(),
            'cron_title' => 'FETCH AMAZON ORDER REPORT',
            'cron_name' => $this->cronName,
            'store_id' => $this->storeId,
        ];
    }

    /**
     * Store Cron Logs in Amazon Cron Logs Table
     */
    public function storeCronLogs() : bool
    {
        $cronStartStop = [
            'cron_type' => $this->cronType,
            'cron_name' => $this->cronName,
            'store_id' => ($this->storeId) ?? NULL,
            'cron_param' => NULL,
            'start_time' => Carbon::now()
        ];

        $cronLogId = AmazonCronLog::create($cronStartStop);

        if($cronLogId)
        {
            $this->cronLogId = $cronLogId->id;
            return true;
        }
        
        return false;
    }

    /**
     * Update Cron Logs in Amazon Cron Logs Table
     */
    public function updateCronLogs() : bool
    {
        $cronLogId = $this->cronLogId;

        if($cronLogId === 0)
        {
            return false;
        }

        return AmazonCronLog::whereId($cronLogId)->update(['end_time' => Carbon::now()]);
    }

    /**
     * Get store & report type wise amazon report log
     */
    public function getAmazonReportLog() : AmazonReportLog | null
    {
        return AmazonReportLog::where('store_id', $this->storeId)
                            ->where('is_processed', '0')
                            ->where('report_type', $this->reportType)
                            ->first();
    }

    /**
     * Store Amazon Report Log Data in Amazon Report Log Table
     * @param string $request_id
     */
    public function storeAmazonReportLog(string $request_id) : bool
    {
        $amazonReportLog = [
            'report_type' => $this->reportType,
            'request_id' => $request_id,
            'store_id' => $this->storeId,
            'is_processed' => 0,
            'requested_date' => CommonHelper::getInsertedDateTime(),
            'processed_date' => CommonHelper::getInsertedDateTime(),
            'cut_off_time' => 3000,
        ];

        $storeAmazonReportLog = AmazonReportLog::create($amazonReportLog);
        // Insert entry report log entry
        if ($storeAmazonReportLog) 
        {
            return true;
        }

        return false;
    }

    /**
     * For send amazon sp api request configuration data
     * @param Store $store
     */
    public function setReportApiConfig(Store $store) : void
    {
        $reportApiConfigArr = [
            'access_token' => $store->access_token,
            'marketplace_ids' => [$store->store_config->amazon_marketplace_id ?? ''],
            'access_key' => $store->aws_access_key_id,
            'secret_key' => $store->aws_secret_key,
            'region' => $store->store_config->amazon_aws_region,
            'host' => $store->store_config->aws_endpoint,
        ];

        if(empty($this->reportType))
        {
            $this->reportApiConfig = $reportApiConfigArr;
        }else{
            $this->reportApiConfig = [
                'report_type' => $this->reportType,
                ...$reportApiConfigArr
            ];
        }
    }

    /**
     * Store amazon cron error logs
     * @param string $error
     */
    public function storeAmazonCronErrorLog(string $error = null) : bool
    {
        $amazonCronErrorLog = AmazonCronErrorLog::create([
            'store_id' => $this->storeId,
            'module' => $this->cronType,
            'submodule' => $this->cronName,
            'error_content' => $error,
        ]);

        if($amazonCronErrorLog) {
            return true;
        }

        return false;
    }

    /**
     * Update amazon report log data
     * @param int $reportLogRequestId
     * @param int $isProcessed
     */
    public function updateAmazonReportLog(int $reportLogRequestId, int $isProcessed) : bool
    {
        return AmazonReportLog::where('request_id', $reportLogRequestId)
                            ->update([
                                "is_processed" => $isProcessed,
                            ]);
    }

    /**
     * Excel column mapping and convert it into array
     * @param string $reportData
     */
    public function getExcelColumnMapping(string $reportData) : array
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

    /**
     * For check column is exist or not with it's type
     * @param string $column
     * @param string $colType
     */
    public function isExistCol(string $column, string $colType = null) : string | int
    {
        switch ($colType) {
            case 'trim':
                return isset($column) ? trim($column) : null;
                break;

            case 'int':
                return (isset($column) && trim($column)) ? trim($column) : 0;
                break;
            
            default:
                return isset($column) ? $column : null;
                break;
        }
    }

    /** 
     * Create order report data for insert or update in amazon order reports
     * @param array $fields
     * @param array $row
     * @param array $excelFields
     * @param array $extraFields
     */
    public function createOrderReportData(array $fields, array $row, array $excelFields, array $extraFields = []) : array
    {
        $orderReportData = [];
        $fieldArr = [];
        $getExcelCols = $excelFields;
        foreach ($fields as $key => $value) {
            if(array_key_exists($key, $getExcelCols))
            {
                if($getExcelCols[$key][0] == 'string')
                {
                    $generateFields = [$getExcelCols[$key][1] => $this->isExistCol($row[$value], 'trim')];
                }

                if($getExcelCols[$key][0] == 'int')
                {
                    $generateFields = [$getExcelCols[$key][1] => $this->isExistCol($row[$value], 'int')];
                }
            }

            $fieldArr = [...$fieldArr, ...$generateFields];
        }

        if(!empty($fieldArr))
        {
            $orderReportData = [...$fieldArr, ...$extraFields];
        }
        return $orderReportData;
    }
}