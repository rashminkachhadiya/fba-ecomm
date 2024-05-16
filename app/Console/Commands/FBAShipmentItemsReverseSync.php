<?php

namespace App\Console\Commands;

use App\Models\FbaShipment as FbaShipmentModel;
use App\Models\FbaShipmentItem;
use App\Services\CronCommonService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tops\AmazonSellingPartnerAPI\Api\FbaShipment;
use Batch;

class FBAShipmentItemsReverseSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fba-shipment-items-reverse-sync {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to fetch fba shipment items from amazon marketplace';

    protected CronCommonService $cronService;
    protected $endTime;

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
        if (empty($this->argument('store_id')))
        {
            $stores = $this->cronService->getStoreIds();

            if ($stores->count() > 0)
            {
                foreach ($stores as $store) {
                    Artisan::call('app:fba-shipment-items-reverse-sync', [
                        'store_id' => $store->id,
                    ]);
                }
            }
        } else {
            if(is_numeric($this->argument('store_id')))
            {
                $this->cronService->storeId = $this->argument('store_id');
                $this->fetchFbaShipmentItems();
            }
            return;
        }
    }

    protected function fetchFbaShipmentItems()
    {
        try {
            // Set cron name & cron type
            $this->cronService->cronName = 'CRON_'.time().'_'.$this->cronService->storeId;
            $this->cronService->cronType = 'FBA_FETCH_SHIPMENT_ITEMS';

            // Get store config for store id
            $storeConfig = $this->cronService->getStore($this->cronService->storeId);

            // If store config found
            if (!isset($storeConfig->id))
            {
                return;
            }
                
            // Log cron start
            $cronLog = $this->cronService->storeCronLogs();

            if(!$cronLog) return;

            // Set report api config for authorize amazon sp api
            $this->cronService->setReportApiConfig($storeConfig);
            
            $this->endTime = Carbon::now()->addMinutes(config('constants.CRON_STOP_MINUTE'))->format(config('constants.INSERT_DATE_FORMAT'));

            // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#getshipmentitemsbyshipmentid
            $fbaShipmentApi = new FbaShipment($this->cronService->reportApiConfig);

            $body = [
                'MarketplaceId' => $this->cronService->reportApiConfig['marketplace_ids'][0],
            ];

            $fbaShipObjArr = FbaShipmentModel::where("store_id", $this->cronService->storeId)->whereIn('is_items_fetched', [0, 2])->take(2)->get();

            if (count($fbaShipObjArr) == 0)
            {
                $fbaShipObjArr = FbaShipmentModel::where("store_id", $this->cronService->storeId)->where('shipment_status', 3)->where('is_receiving_item_fetched', 0)->take(5)->get();

                if (count($fbaShipObjArr) == 0)
                {
                    FbaShipmentModel::select('id')->where("store_id", $this->cronService->storeId)->where('shipment_status', 3)->where('is_receiving_item_fetched', 1)->chunkById(config('constants.BATCH_UPDATE_LIMIT'), function ($rows) {
                        if (!empty($rows))
                        {
                            $keep_up = [];
                            foreach ($rows as $row) {
                                $keep_up[] = array("id" => $row->id, "is_receiving_item_fetched" => "0");
                            }
                            Batch::update(new FbaShipmentModel, $keep_up, 'id');
                        }
                    });
                }
            }

            if( $fbaShipObjArr->count() > 0 )
            {
                foreach( $fbaShipObjArr AS $fbaShipObj )
                {
                    sleep(1);

                    $fbaRes = $fbaShipmentApi->getShipmentItemsByShipmentId($body, $fbaShipObj->shipment_id);

                    if(isset($fbaRes['errors']))
                    {
                        $error = $fbaRes['errors']['message'] ?? "Error in getting FBA Shipments Items";

                        // Store error log
                        $this->cronService->storeAmazonCronErrorLog($error);
                        break;
                    }

                    if(!empty($fbaRes['payload']['ItemData']))
                    {
                        $itemData = $fbaRes['payload']['ItemData'];

                        $sellerSku = array_column($itemData, 'SellerSKU');

                        FbaShipmentItem::whereNotIn('seller_sku', $sellerSku)->where('fba_shipment_id', $fbaShipObj->id)->where('shipment_id', $fbaShipObj->shipment_id)->update(['quantity_shipped' => 0]);

                        $this->saveFBAShipmentItems($itemData, $fbaShipObj);
                        
                    } elseif(isset($fbaRes['payload']['ItemData']) && empty($fbaRes['payload']['ItemData'])) {

                        FbaShipmentItem::where('fba_shipment_id', $fbaShipObj->id)->update(['quantity_shipped' => 0]);

                        $fbaShipObj->is_items_fetched = 1;
                        $fbaShipObj->save();
                    }
                }
            }

            // Log cron end
            $this->cronService->updateCronLogs();
        } catch(\Exception $e) {
            // Store error log
            $this->cronService->storeAmazonCronErrorLog($e->getMessage() . ' - ' . $e->getLine());
        }
        
    }

    public function saveFBAShipmentItems($shipmentItemsListArr=[], $fbaShipObj = NULL )
    {
        foreach( $shipmentItemsListArr as $fba_shipment_item )
        {
            $fbaShipAmzObj = FbaShipmentItem::firstOrNew([
                'store_id' => $this->cronService->storeId,
                'shipment_id' => $fba_shipment_item['ShipmentId'],
                'seller_sku' => $fba_shipment_item['SellerSKU'],
                'fba_shipment_id' => $fbaShipObj->id,
            ]);

            // $oldReceivedQty = $fbaShipAmzObj->quantity_received ?? 0;

            $fbaShipAmzObj->fulfillment_network_sku = $fba_shipment_item['FulfillmentNetworkSKU'] ?? NULL;
            $fbaShipAmzObj->quantity_shipped = $fba_shipment_item['QuantityShipped'];
            $fbaShipAmzObj->quantity_received = $fba_shipment_item['QuantityReceived'] ?? NULL;
            $fbaShipAmzObj->quantity_in_case = $fba_shipment_item['QuantityInCase'] ?? NULL;
            $fbaShipAmzObj->release_date = $fba_shipment_item['ReleaseDate'] ?? NULL;

            $fbaShipAmzObj->updated_at = date('Y-m-d H:i:s');

            if ($fbaShipAmzObj->is_quantity_updated != 1)
            {
                $fbaShipAmzObj->original_quantity_shipped = $fba_shipment_item['QuantityShipped'] ?? NULL;
            }

            if(!$fbaShipAmzObj->exists)
            {
                $fbaShipAmzObj->created_at = date('Y-m-d H:i:s');
            }
            
            $fbaShipAmzObj->save();

            // if(!empty( $fba_shipment_item['PrepDetailsList']))
            // {
            //     foreach( $fba_shipment_item['PrepDetailsList'] AS $PrepDetailsList )
            //     {
            //         if (isset($PrepDetailsList['PrepInstruction']))
            //         {
            //             $fbaShipItemPrepAmzObj = FbaShipmentItemPrepDetail::firstOrNew([
            //                 'fba_shipment_item_amz_id' => $fbaShipAmzObj->id,
            //                 'fba_shipment_id' => $fbaShipObj->id,
            //             ]);

            //             $fbaShipItemPrepAmzObj->prep_instruction = $this->fba_PrepInstruction_fliped_arr[ $PrepDetailsList['PrepInstruction'] ];
            //             $fbaShipItemPrepAmzObj->prep_instruction_value = $PrepDetailsList['PrepInstruction'];
            //             $fbaShipItemPrepAmzObj->prep_owner = $this->fba_PrepOwner_fliped_arr[ $PrepDetailsList['PrepOwner'] ];

            //             $fbaShipItemPrepAmzObj->updated_at = date('Y-m-d H:i:s');
            //             if( ! $fbaShipItemPrepAmzObj->exists )
            //             {
            //                 $fbaShipItemPrepAmzObj->created_at = date('Y-m-d H:i:s');
            //             }
            //             $fbaShipItemPrepAmzObj->save();
            //         }
            //     }
            // }

            // if (!empty($fba_shipment_item['QuantityReceived']) && ($fba_shipment_item['QuantityReceived'] - $oldReceivedQty) > 0)
            // {
            //     $newReceivedQty = $fba_shipment_item['QuantityReceived'];
            //     $this->calculateProductCogs($this->cronService->storeId, $fbaShipAmzObj, $oldReceivedQty, $newReceivedQty);
            // }
        }

        if ($fbaShipObj->shipment_status == 3)
        {
            $fbaShipObj->is_receiving_item_fetched = 1;
        }

        $fbaShipObj->is_items_fetched = 1;
        $fbaShipObj->save();
    }
}
