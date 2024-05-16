<?php

namespace App\Services;

use App\Models\ShipmentPlan;
use Tops\AmazonSellingPartnerAPI\Api\FbaShipment;
use App\Models\FbaShipment as FbaShipmentModel;
use App\Models\FbaShipmentItem;
use App\Models\ShipmentPlanError;
use App\Models\ShipmentProduct;
use App\Models\Warehouse;
use Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentPlanService extends BaseService
{
    public CronCommonService $commonService;
    public int $shipmentId = 0;
    public string $code = '', $reason = '';
    public array $products = [];

    public function __construct()
    {
        $this->commonService = new CronCommonService();
    }

    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }

    public function createShipmentPlan(int $storeId)
    {
        $response = [];

        // $module_name = 'FBA Create Shipment Plan Helper';

        $this->reportType = 'FBA_CREATE_SHIPMENT_PLAN';
        // $cron['report_type'] = 'FBA_CREATE_SHIPMENT_PLAN';
        // $cron['cron_title'] = 'FBA CREATE SHIPMENT PLAN';
        // $cron['report_source'] = '1';//SP API
        // $cron['report_freq'] = '2';//Daily

        $fbaLabelPrepTypeFlipedArr = config('constants.prep_preference');
        // $store_currency_arr = config('store_params.currency');

        try{
            // $cron['hour'] = (int) date('H', time());
            // $cron['date'] = date('Y-m-d');
            // $cron['cron_name'] = 'CRON_'.time().'_' . $storeId;
            // $cron['store_id'] = $storeId;

            // $cron['cron_param'] =  $storeId;

            // Get store config for store id
            $storeData = $this->commonService->getStore($storeId);
            // Set cron data
            // $cronStartStop = [
            //     'cron_type' => $cron['cron_title'],
            //     'cron_name' => $cron['cron_name'],
            //     'store_id' => $storeId,
            //     'cron_param' => $cron['cron_param'],
            //     'action' => 'start',
            // ];

            // // Log cron start
            // $cronLog = CronLog::cronStartEndUpdate( $cronStartStop );
            // $cronStartStop['id'] = $cronLog->id;

            $this->commonService->setReportApiConfig($storeData);

            // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#createinboundshipmentplan
            $fbaShipmentApi = new FbaShipment($this->commonService->reportApiConfig);
            $body = [
                'MarketplaceId' => $this->commonService->reportApiConfig['marketplace_ids'][0],
            ];

            
            $fbaShipObj = ShipmentPlan::with(['shipmentProducts'])->where('plan_status', 'Draft')
                                            ->where('status', 0)
                                            ->where("id", $this->shipmentId)
                                            ->first();
            if(!empty($fbaShipObj))
            {
                $body = [
                    'LabelPrepPreference' => $fbaShipObj->prep_preference,
                ];

                $wareHouse = Warehouse::first();

                if(!empty($wareHouse))
                {
                    $body['ShipFromAddress'] = [
                        "Name" => $wareHouse->name,
                        "AddressLine1" => $wareHouse->address_1,
                        "City" => $wareHouse->city,
                        "StateOrProvinceCode" => $wareHouse->state_or_province_code,
                        "CountryCode" => $wareHouse->country_code,
                        "PostalCode" => $wareHouse->postal_code,
                    ];
                }

                $InboundShipmentPlanRequestItems_arr = [];
                if($fbaShipObj->shipmentProducts->count() > 0)
                {
                    foreach( $fbaShipObj->shipmentProducts AS $fbaShipmentPlanProduct )
                    {
                        if( !empty( $fbaShipmentPlanProduct->sellable_unit ) )
                        {
                            $InboundShipmentPlanRequestItems_arr[] = [
                                'SellerSKU' => $fbaShipmentPlanProduct->sku,
                                'Condition' => 'NewItem',
                                'Quantity' => $fbaShipmentPlanProduct->sellable_unit,
                            ];
                        }
                    }
                    $body['InboundShipmentPlanRequestItems'] = $InboundShipmentPlanRequestItems_arr;

                    $fbaRes = $fbaShipmentApi->createInboundShipmentPlan($body);
                    
                    if(!empty($fbaRes['payload']['InboundShipmentPlans']))
                    {
                        foreach($fbaRes['payload']['InboundShipmentPlans'] AS $InboundShipmentPlan )
                        {
                            $fbaShipAmzObj = new FbaShipmentModel();
                            $fbaShipAmzObj->store_id = $storeId;
                            $fbaShipAmzObj->shipment_id = $InboundShipmentPlan['ShipmentId'];
                            $fbaShipAmzObj->fba_shipment_plan_id = $fbaShipObj->id;
                            $fbaShipAmzObj->shipment_name = $fbaShipObj->plan_name;

                            $fbaShipAmzObj->destination_fulfillment_center_id = $InboundShipmentPlan['DestinationFulfillmentCenterId'];
                            $fbaShipAmzObj->label_prep_type = $fbaLabelPrepTypeFlipedArr[$InboundShipmentPlan['LabelPrepType']] ?? NULL;

                            if(!empty($body['ShipFromAddress']))
                            {
                                $ShipFromAddress = $body['ShipFromAddress'];
                                $fbaShipAmzObj->ship_from_addr_name = $ShipFromAddress['Name'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_line1 = $ShipFromAddress['AddressLine1'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_district_county = $ShipFromAddress['DistrictOrCounty'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_city = $ShipFromAddress['City'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_state_province_code = $ShipFromAddress['StateOrProvinceCode'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_country_code = $ShipFromAddress['CountryCode'] ?? NULL;
                                $fbaShipAmzObj->ship_from_addr_postal_code = $ShipFromAddress['PostalCode'] ?? NULL;
                            }

                            if( !empty( $InboundShipmentPlan['ShipToAddress'] ) )
                            {
                                $ShipToAddress = $InboundShipmentPlan['ShipToAddress'];
                                $fbaShipAmzObj->ship_to_addr_name = $ShipToAddress['Name'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_line1 = $ShipToAddress['AddressLine1'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_line2 = $ShipToAddress['AddressLine2'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_district_county = $ShipToAddress['DistrictOrCounty'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_city = $ShipToAddress['City'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_state_province_code = $ShipToAddress['StateOrProvinceCode'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_country_code = $ShipToAddress['CountryCode'] ?? NULL;
                                $fbaShipAmzObj->ship_to_addr_postal_code = $ShipToAddress['PostalCode'] ?? NULL;
                            }

                            if( !empty( $InboundShipmentPlan['EstimatedBoxContentsFee'] ) )
                            {
                                $EstimatedBoxContentsFee = $InboundShipmentPlan['EstimatedBoxContentsFee'];

                                $fbaShipAmzObj->est_box_content_fee_total_unit = $EstimatedBoxContentsFee['TotalUnits'];

                                if( !empty( $EstimatedBoxContentsFee['FeePerUnit'] ) )
                                {
                                    $FeePerUnit = $EstimatedBoxContentsFee['FeePerUnit'];
                                    $fbaShipAmzObj->est_box_content_fee_per_unit = $FeePerUnit['Value'];
                                    $fbaShipAmzObj->est_box_content_fee_currency_code = $FeePerUnit['CurrencyCode'] ?? NULL;
                                }

                                if( !empty( $EstimatedBoxContentsFee['TotalFee'] ) )
                                {
                                    $TotalFee = $EstimatedBoxContentsFee['TotalFee'];
                                    $fbaShipAmzObj->est_box_content_total_fee = $TotalFee['Value'];
                                    $fbaShipAmzObj->est_box_content_total_fee_currency_code = $TotalFee['CurrencyCode'] ?? NULL;
                                }
                            }
                            $fbaShipAmzObj->updated_at = date('Y-m-d H:i:s');
                            $fbaShipAmzObj->created_at = date('Y-m-d H:i:s');
                            $fbaShipAmzObj->is_items_fetched = 1;

                            $fbaShipAmzObj->save();

                            if(!empty($InboundShipmentPlan['Items']))
                            {
                                $shipmentPlanItem_insert_arr = [];

                                $sku_arr = array_column($InboundShipmentPlan['Items'], "SellerSKU" );
                                $planProObj_arr = [];
                                if(!empty($sku_arr))
                                {
                                    $planProObj_arr = ShipmentProduct::where('shipment_plan_id', $fbaShipObj->id)->whereIn( "sku", $sku_arr )->get()->keyBy('sku');
                                }

                                foreach( $InboundShipmentPlan['Items'] AS $shipmentPlanItem )
                                {
                                    $tempArr = [
                                        'store_id' => $storeId,
                                        'fba_shipment_plan_id' => $fbaShipObj->id,
                                        'fba_shipment_id' => $fbaShipAmzObj->id,
                                        'shipment_id' => $InboundShipmentPlan['ShipmentId'],
                                        'seller_sku' => $shipmentPlanItem['SellerSKU'],
                                        'fulfillment_network_sku' => $shipmentPlanItem['FulfillmentNetworkSKU'],
                                        'quantity' => $shipmentPlanItem['Quantity'],
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ];

                                    $tempArr['quantity_shipped'] = $tempArr['quantity'];
                                    $tempArr['original_quantity_shipped'] = $tempArr['quantity'];

                                    $planProObj = $planProObj_arr[ $shipmentPlanItem['SellerSKU'] ] ?? NULL;
                                    // if( !empty( $planProObj->id ) )
                                    // {
                                    //     $tempArr['fba_plan_product_id'] = $planProObj->id;
                                    //     $tempArr['amazon_posting_id'] = $planProObj->amazon_product_posting_id;
                                    //     $tempArr['product_master_id'] = $planProObj->product_master_id;
                                    //     $tempArr['product_child_id'] = $planProObj->product_child_id;
                                    // }
                                    $shipmentPlanItem_insert_arr[] = $tempArr;
                                }

                                Batch::insert(new FbaShipmentItem, array_keys($shipmentPlanItem_insert_arr[0]), $shipmentPlanItem_insert_arr, 100);
                            }
                        }
                        
                        $fbaShipObj->status = 2;
                        $fbaShipObj->save();
                        
                        $response = ['status'=>'success', 'message'=>'Draft shipment created successfully.', 'plan_id'=>$this->shipmentId];

                    } elseif(isset($fbaRes['errors'])){

                        $error_msg = $fbaRes['errors'][0]['message'];

                        $error = json_encode($fbaRes);

                        // self::sendErrorLog($storeId, $module_name, $error);
                        $fbaShipObj->status = 5;
                        $fbaShipObj->remark = $error;
                        $fbaShipObj->save();

                        $response = ['status'=>'error', 'message' => $fbaRes];
                    }
                }
            }
            // Log cron end
            // $cronLog->updateEndTime();
        } catch(\Exception $e) {
            $error_msg = 'Line No.:'. $e->getLine() .', File:'. $e->getFile() . ', Message: ' .$e->getMessage();
            
            // self::sendErrorLog($storeId, $module_name, $error_msg);

            $response = ['status'=>'error', 'message' => $error_msg];
        }
        return $response;
    }

    public function shipmentPlanFinalize(array $result) : array
    {
        $shipmentPlan = ShipmentPlan::where('id', $this->shipmentId);

        if (isset($result['status']) && $result['status'] == 'success')
        {
            $shipmentPlan->update([
                    'plan_status' => 'Finalized',
                ]);

            $response = [
                'type'   => 'success',
                'status' => 200,
                'message' => $result['message']
            ];
        } elseif (isset($result['message']) && !empty($result['message'])) {
            $shipmentPlan->update([
                    'plan_status' => 'Draft',
                ]);

            $error = $result['message']['errors'][0];
            $this->code = $error['code'];

            if (strpos($error['message'], 'Reason') !== false)
            {
                $errorMessage = isset($error['message']) && !empty($error['message']) ? explode('Reason:', $error['message']) : [];
                
                $message = isset($errorMessage[0]) ? $errorMessage[0] : '';
                $this->reason = isset($errorMessage[1]) ? $errorMessage[1] : '';
                // Log::info("messages reason if condition = ".$this->reason);

                if (strpos($this->reason, 'Item(s) ineligible from being inbounded') !== false)
                {
                    $reasonArr = explode("Item(s) ineligible from being inbounded, with reason: ", $this->reason);
                    // Log::info("messages reason if and if condition = ", $reasonArr);

                    if (!empty($reasonArr))
                    {
                        foreach ($reasonArr as $reasons)
                        {
                            $strValue = trim(str_replace(array('[', ']'), '', $reasons));

                            if (!empty($strValue))
                            {
                                $arr1 = explode(" Corresponding items for the error: ", $strValue);
                                $errorReason = $arr1[0];
                                $errorSkus = explode(',', $arr1[1]);

                                foreach ($errorSkus as $errorSku)
                                {
                                    if (strpos($errorSku, 'MSKU') !== false)
                                    {
                                        $skuValue = explode('MSKU: ', $errorSku);
                                        $skuValue1 = str_replace(array('[', ']', '.', ','), '', $skuValue[1]);

                                        if (!empty($skuValue1))
                                        {
                                            $shipmentProduct = ShipmentProduct::where('shipment_plan_id', $this->shipmentId)->where("sku", $skuValue1)->first();

                                            $this->products[] = [
                                                'id' => $shipmentProduct->id,
                                                'asin' => $shipmentProduct->asin,
                                                'sku' => $shipmentProduct->sku,
                                                'title' => $shipmentProduct->title,
                                                'amazon_product_id' => $shipmentProduct->amazon_product_id,
                                                'sellable_unit' => $shipmentProduct->sellable_unit,
                                                'reason' => $errorReason
                                            ];

                                            // Log::info("messages reason if and if for loop before insert plan error = ", $this->products);

                                            ShipmentPlanError::create([
                                                'fba_shipment_id' => $this->shipmentId,
                                                'sku' => $skuValue1,
                                                'error_code' => $this->code,
                                                'reason' => trim($errorReason),
                                                'error_description' => $error['message']
                                            ]);
                                        }
                                    } elseif (preg_match('~^[^-]+-[^-]{4}+-[^-]+$~', $errorSku, $matches)){
                                        $strValue = trim($matches[0]);
                                        if (strpos($strValue, 'sku=') !== false) {
                                            $skuValue1 = str_replace(array('sku=', '[', ']', '.', ','), '', $strValue);
                                        } else {
                                            $skuValue = explode(' ', $errorSku);
                                            $skuValue1 = str_replace(array('[', ']', '.', ','), '', $skuValue[2]);
                                        }

                                        if (!empty($skuValue1))
                                        {
                                            $shipmentProduct = ShipmentProduct::where('shipment_plan_id', $this->shipmentId)->where("sku", $skuValue1)->first();

                                            $this->products[] = [
                                                'id' => $shipmentProduct->id,
                                                'asin' => $shipmentProduct->asin,
                                                'sku' => $shipmentProduct->sku,
                                                'title' => $shipmentProduct->title,
                                                'amazon_product_id' => $shipmentProduct->amazon_product_id,
                                                'sellable_unit' => $shipmentProduct->sellable_unit,
                                                'reason' => $errorReason
                                            ];

                                            // Log::info("messages reason if and if with else if before insert plan error = ", $this->products);

                                            ShipmentPlanError::create([
                                                'fba_shipment_id' => $this->shipmentId,
                                                'sku' => $skuValue1,
                                                'error_code' => $this->code,
                                                'reason' => trim($errorReason),
                                                'error_description' => $error['message']
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif (strpos($this->reason, 'InvalidItems') !== false) {
                    $errorReason = '';
                    $reasonString = trim($this->reason);

                    if (strpos($this->reason, 'reason=') !== false) {
                        $reasonArr = explode('reason=', $reasonString);
                        $errorReason = str_replace(array('reason=', '[', ']', '(', ')', '.', ','), '', $reasonArr[1]);
                    } else {
                        $reasonArr = explode('. ', $this->reason);
                        $errorReason = $reasonArr[0];
                    }

                    $trimReasonArr = explode(' ', $reasonString);

                    foreach ($trimReasonArr as $trimReason)
                    {
                        if (preg_match('~^[^-]+-[^-]{4}+-[^-]+$~', $trimReason, $matches))
                        {
                            $strValue = trim($matches[0]);

                            if (strpos($strValue, 'sku=') !== false) {
                                $skuValue1 = str_replace(array('sku=', '[', ']', '.', ','), '', $strValue);
                            } else {
                                $skuValue1 = str_replace(array('[', ']', '.', ','), '', $strValue);
                            }

                            if (!empty($skuValue1))
                            {
                                $shipmentProduct = ShipmentProduct::where('shipment_plan_id', $this->shipmentId)->where("sku", $skuValue1)->first();

                                $this->products[] = [
                                    'id' => $shipmentProduct->id,
                                    'asin' => $shipmentProduct->asin,
                                    'sku' => $shipmentProduct->sku,
                                    'title' => $shipmentProduct->title,
                                    'amazon_product_id' => $shipmentProduct->amazon_product_id,
                                    'sellable_unit' => $shipmentProduct->sellable_unit,
                                    'reason' => $errorReason
                                ];

                                // Log::info("messages reason if and if with else if second before insert plan error = ", $this->products);

                                ShipmentPlanError::create([
                                    'fba_shipment_id' => $this->shipmentId,
                                    'sku' => $skuValue1,
                                    'error_code' => $this->code,
                                    'reason' => trim($errorReason),
                                    'error_description' => $error['message']
                                ]);
                            }
                        }
                    }
                } else {
                    $shipmentPlan->update(['status' => 5]);

                    ShipmentPlanError::create([
                        'fba_shipment_id' => $this->shipmentId,
                        'error_code' => $this->code,
                        'reason' => $this->reason,
                        'error_description' => $error['message']
                    ]);

                    $message = isset($error['message']) && !empty($error['message']) ? $error['message'] : '';
                }
            } else {
                $shipmentPlan->update(['status' => 5]);

                ShipmentPlanError::create([
                    'fba_shipment_id' => $this->shipmentId,
                    'error_code' => $this->code,
                    'error_description' => isset($error['message']) && !empty($error['message']) ? $error['message'] : ''
                ]);

                $message = isset($error['message']) && !empty($error['message']) ? $error['message'] : '';
            }

            $response = [
                'type'   => 'error',
                'status' => 400,
                'code' => $this->code,
                'message' => $message,
                'reason' => $this->reason,
                'products' => $this->products
            ];
        } else {
            $shipmentPlan->update([
                    'plan_status' => 'Draft',
                    'status' => 5
                ]);

            ShipmentPlanError::create([
                'fba_shipment_id' => $this->shipmentId,
                'error_code' => $this->code,
                'reason' => $this->reason,
                'error_description' => 'Something went wrong'
            ]);

            $response = [
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong',
                'code' => $this->code,
                'reason' => $this->reason,
                'products' => $this->products
            ];
        }

        return $response;
    }
}