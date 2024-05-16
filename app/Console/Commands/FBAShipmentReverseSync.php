<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Models\FbaShipment as FbaShipmentModel;
use App\Services\CronCommonService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\FbaShipment;

class FBAShipmentReverseSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fba-shipment-reverse-sync {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to fetch fba shipment from amazon marketplace';

    protected CronCommonService $cronService;
    protected $insertDateFormat;
    protected $endTime;
    protected $fbaShipmentStatus;
    protected $fbaPrepType;
    protected $fbaBoxContent;
    
    public function __construct()
    {
        parent::__construct();
        $this->cronService = new CronCommonService();
        $this->insertDateFormat = config('constants.insert_date_format');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (empty($this->argument('store_id')))
        {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0) {
                foreach ($stores as $store) {
                    Artisan::call('app:fba-shipment-reverse-sync', [
                        'store_id' => $store->id,
                    ]);
                }
            }
        } else {
            if(is_numeric($this->argument('store_id')))
            {
                $this->cronService->storeId = $this->argument('store_id');
                $this->fetchFbaShipment();
            }
            return;
        }
    }

    protected function fetchFbaShipment()
    {
        $this->fbaShipmentStatus = array_flip(config('constants.fba_shipment_status'));
        $this->fbaPrepType = array_flip(config('constants.label_prep_type'));
        $this->fbaBoxContent = array_flip(config('constants.box_content_source'));

        // Set cron name & cron type
        $this->cronService->cronName = 'CRON_'.time().'_'.$this->cronService->storeId;
        $this->cronService->cronType = 'FBA_FETCH_SHIPMENT';

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
        
        $this->endTime = Carbon::now()->addMinutes(config('constants.CRON_STOP_MINUTE'))->format($this->insertDateFormat);

        try
        {
            $latestOrderDatetime = FbaShipmentModel::getLatestRecord();

            if (!empty($latestOrderDatetime))
            {
                $dateTime = Carbon::parse($latestOrderDatetime)->timezone('UTC')->subMinutes(10);
            } else {
                $dateTime = Carbon::parse(Carbon::now()->subDays(30)->format('Y-m-d'));
            }

            $lastUpdatedAfter = $dateTime->format('Y-m-d\TH:i:s');
            
            $lastUpdatedBefore = Carbon::now()->timezone('UTC')->format('Y-m-d\TH:i:s');

            //https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#getshipments
            $fbaShipmentApi = new FbaShipment($this->cronService->reportApiConfig);

            $body = [
                'QueryType' => 'DATE_RANGE',
                'ShipmentStatusList' => implode(',', config('constants.fba_shipment_status')),
                'LastUpdatedAfter' => $lastUpdatedAfter,
                'LastUpdatedBefore' => $lastUpdatedBefore,
                'MarketplaceId' => $this->cronService->reportApiConfig['marketplace_ids'][0],
            ];

            do
            {
                sleep(1);
                if( !empty( $body['NextToken'] ) )
                {
                    $body['QueryType'] = 'NEXT_TOKEN';
                }
                $fbaRes = $fbaShipmentApi->getShipments($body);

                if( isset( $fbaRes->errors ) )
                {
                    $error = $fbaRes->errors[0]->message ?? "Error in getting FBA Shipments";
                    // Store error log
                    $this->cronService->storeAmazonCronErrorLog($error);
                    break;
                }

                if( !empty( $fbaRes['payload']['ShipmentData'] ) )
                {
                    $this->saveFBAShipments($fbaRes['payload']['ShipmentData']);
                } else {
                    FbaShipmentModel::where('shipment_status', '!=', null)->orderby('id', 'DESC')->first()->update(['updated_at' => CommonHelper::getInsertedDateTime()]);
                }
            } while( (Carbon::now()->format(config('constants.INSERT_DATE_FORMAT')) < $this->endTime ) && ( $body['NextToken'] = $fbaRes['payload']['NextToken'] ?? NULL ) );
        } catch(\Exception $e) {
            // Store error log
            $this->cronService->storeAmazonCronErrorLog($e->getMessage() . ' - ' . $e->getLine());
        }

        // Log cron end
        $this->cronService->updateCronLogs();
    }

    public function saveFBAShipments($shipmentListArr=[])
    {
        foreach( $shipmentListArr as $fba_shipment )
        {
            $fbaShipAmzObj = FbaShipmentModel::firstOrNew([
                'store_id' => $this->cronService->storeId,
                'shipment_id' => $fba_shipment['ShipmentId'],
            ]);

            $fbaShipAmzObj->shipment_name = $fba_shipment['ShipmentName'];
            $fbaShipAmzObj->destination_fulfillment_center_id = $fba_shipment['DestinationFulfillmentCenterId'];
            $fbaShipAmzObj->shipment_status = $this->fbaShipmentStatus[ $fba_shipment['ShipmentStatus'] ] ?? NULL;
            $fbaShipAmzObj->label_prep_type = $this->fbaPrepType[ $fba_shipment['LabelPrepType'] ] ?? NULL;
            $fbaShipAmzObj->are_cases_required = $fba_shipment['AreCasesRequired'] ?? 0;
            $fbaShipAmzObj->box_contents_source = $this->fbaBoxContent[ $fba_shipment['BoxContentsSource'] ] ?? NULL;

            if(!empty( $fba_shipment['ShipFromAddress']))
            {
                $ShipFromAddress = $fba_shipment['ShipFromAddress'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_name = $ShipFromAddress['Name'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_line1 = $ShipFromAddress['AddressLine1'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_district_county = $ShipFromAddress['DistrictOrCounty'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_city = $ShipFromAddress['City'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_state_province_code = $ShipFromAddress['StateOrProvinceCode'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_country_code = $ShipFromAddress['CountryCode'] ?? NULL;
                $fbaShipAmzObj->ship_from_addr_postal_code = $ShipFromAddress['PostalCode'] ?? NULL;
            }

            $fbaShipmentData = FbaShipmentModel::where('destination_fulfillment_center_id', $fba_shipment['DestinationFulfillmentCenterId'])->whereNotNull('ship_to_addr_name')->orderBy('id', 'desc')->first();

            if (!empty($fbaShipmentData))
            {
                $fbaShipAmzObj->ship_to_addr_name = $fbaShipmentData->ship_to_addr_name ?? NULL;
                $fbaShipAmzObj->ship_to_addr_line1 = $fbaShipmentData->ship_to_addr_line1 ?? NULL;
                $fbaShipAmzObj->ship_to_addr_line2 = $fbaShipmentData->ship_to_addr_line2 ?? NULL;
                $fbaShipAmzObj->ship_to_addr_district_county = $fbaShipmentData->ship_to_addr_district_county ?? NULL;
                $fbaShipAmzObj->ship_to_addr_city = $fbaShipmentData->ship_to_addr_city ?? NULL;
                $fbaShipAmzObj->ship_to_addr_state_province_code = $fbaShipmentData->ship_to_addr_state_province_code ?? NULL;
                $fbaShipAmzObj->ship_to_addr_country_code = $fbaShipmentData->ship_to_addr_country_code ?? NULL;
            }

            $fbaShipAmzObj->updated_at = date('Y-m-d H:i:s');
            $fbaShipAmzObj->is_items_fetched = 2;

            if(!$fbaShipAmzObj->exists)
            {
                $fbaShipAmzObj->shipment_created_from = 2;
                $fbaShipAmzObj->is_items_fetched = 0;
                $fbaShipAmzObj->created_at = date('Y-m-d H:i:s');
            }

            $fbaShipAmzObj->save();
        }
    }
}
