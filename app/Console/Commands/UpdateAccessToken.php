<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\AmazonCronLog;
use Illuminate\Console\Command;
use App\Models\AmazonCronErrorLog;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Authentication;
use App\Services\CronCommonService;

class UpdateAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateAccessToken:amazon {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to update amazon token of stores';
    private $cron;
    private $command_data;
    protected CronCommonService $cronService;

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->command_data['module_name'] = "AMAZON_ACCESS_TOKEN";

        $this->cron['report_type'] = 'AMAZON_ACCESS_TOKEN';
        $this->cron['cron_title'] = 'AMAZON_ACCESS_TOKEN';
        $this->cron['report_source'] = '1'; //SP API
        $this->cron['report_freq'] = '2';
        $this->cronService = new CronCommonService();
    }

    /**
     * Execute the console command.
     *
     * @return int
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
                    Artisan::call('updateAccessToken:amazon', [
                        'store_id' => $store->id
                    ]);
                }
            }
        } else {
            $this->cron['hour'] = (int) date('H', time());
            $this->cron['date'] = date('Y-m-d');
            $this->cron['cron_name'] = 'CRON_'.time();
            $this->updateAccessToken($storeId);
        }

        return Command::SUCCESS;
    }

    private function updateAccessToken($storeId = null)
    {
        $module = 'Update store token';
        $submodule = 'updateAccessToken';
        if (!empty($storeId)) {
            $this->cron['store_id'] = $storeId = (int) trim($storeId);
            $this->cron['cron_name'] .=  '_' . $storeId;
            $this->cron['cron_param'] =  $storeId;

            $storeObj = Store::find($storeId);
            if (isset($storeObj->client_id)) {
                try{
                    $cronStartStop = [
                        'cron_type' => $this->cron['cron_title'],
                        'cron_name' => $this->cron['cron_name'],
                        'store_id' => $storeId,
                        'cron_param' => $this->cron['cron_param'],
                        'action' => 'start',
                    ];

                    $cronLog = AmazonCronLog::cronStartEndUpdate($cronStartStop);
                    $cronStartStop['id'] = $cronLog->id;

                    $amaznObj = new Authentication($storeObj->client_id, $storeObj->client_secret);
                
                    $amazData = $amaznObj->getAccessTokenFromRefreshToken('refresh_token', $storeObj->refresh_token);
                    // update access token
                    if (!empty($amazData) && isset($amazData->access_token)) {
                        $returnArr = [
                            'access_token' => $amazData->access_token
                        ];

                        foreach ($returnArr as $key => $val) {
                            $storeObj->$key = $val;
                        }
                        $storeObj->save();
                    }

                    // Log cron end
                    $cronLog->updateEndTime();
                }catch(\Exception $e) {
                    $err_message = $e->getMessage();
                    $logdata =  [
                        'store_id' => $this->cron['store_id'],
                        'batch_id' => null,
                        'module' => $module,
                        'submodule' => $submodule,
                        'error_content' => $err_message
                    ];
                    AmazonCronErrorLog::logError($logdata);
                }
            }     
        }
    }
}
