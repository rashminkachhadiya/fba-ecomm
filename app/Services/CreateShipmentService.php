<?php

namespace App\Services;

use App\Models\FbaShipment as ModelsFbaShipment;
use App\Models\FbaShipmentItem;
use Tops\AmazonSellingPartnerAPI\Api\FbaShipment;
use Batch;

class CreateShipmentService extends BaseService
{
    public CronCommonService $cronCommonService;

    public function __construct()
    {
        $this->cronCommonService = new CronCommonService();
    }

    public function invokeCreateShipmentApi($storeId, $shipmentId)
    {
        $response = [];

        if (!empty($shipmentId))
        {
            $this->reportType = 'FBA_CREATE_SHIPMENT';

            $fba_ShipmentStatusList_fliped_arr = array_flip(config('amazon_params.ShipmentStatusList'));
            $fba_BoxContentsSource_arr = config('amazon_params.BoxContentsSource');
            $fba_BoxContentsSource_fliped_arr = array_flip($fba_BoxContentsSource_arr);
            
            try
            {
                $this->cronName = 'CRON_'.time().'_' . $storeId;
                $this->storeId = $storeId;

                // Get store config for store id
                $storeData = $this->cronCommonService->getStore($this->storeId);
                
                // Set report configuration
                $this->cronCommonService->setReportApiConfig($storeData);

                // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#createinboundshipment
                $fbaShipmentApi = new FbaShipment($this->cronCommonService->reportApiConfig);

                $marketplaceId = $this->cronCommonService->reportApiConfig['marketplace_ids'][0];

                $fbaShipObj = ModelsFbaShipment::with(['shipmentPlan','fbaShipmentItems'])
                            ->where("store_id", $storeId)
                            ->whereNull("shipment_status")
                            ->where("id", $shipmentId)
                            ->first();

                if(!empty($fbaShipObj))
                {
                    $labelPrepPreference = $fbaShipObj->shipmentPlan->prep_preference;

                    $fbaShipmentAmazon = $fbaShipObj;

                    if($fbaShipmentAmazon->fbashipmentItems->count() > 0)
                    {
                        $shipmentName = 'FBA-'.date('m/d');
                        if(!empty($fbaShipObj->shipment_name))
                        {
                            $shipmentName = $fbaShipObj->shipment_name;
                        } else {
                            $shipmentName = $fbaShipObj->shipmentPlan->plan_name;
                        }

                        $InboundShipmentHeader = [
                            'ShipmentName' => $shipmentName,
                            'ShipmentStatus' => 'WORKING',
                            'LabelPrepPreference' => $labelPrepPreference,
                            'DestinationFulfillmentCenterId' => $fbaShipObj->destination_fulfillment_center_id,
                            'AreCasesRequired' => ( $fbaShipObj->are_cases_required == 1 ),
                            'IntendedBoxContentsSource' => "2D_BARCODE",
                        ];

                        $ShipFromAddress = [
                            "Name" => $fbaShipmentAmazon->ship_from_addr_name,
                            "AddressLine1" => $fbaShipmentAmazon->ship_from_addr_line1,
                            "City" => $fbaShipmentAmazon->ship_from_addr_city,
                            "StateOrProvinceCode" => $fbaShipmentAmazon->ship_from_addr_state_province_code,
                            "CountryCode" => $fbaShipmentAmazon->ship_from_addr_country_code,
                            "PostalCode" => $fbaShipmentAmazon->ship_from_addr_postal_code,
                        ];
                        
                        if(!empty( $fbaShipmentAmazon->ship_from_addr_district_county))
                        {
                            $ShipFromAddress['DistrictOrCounty'] = $fbaShipmentAmazon->ship_from_addr_district_county;
                        }

                        $InboundShipmentHeader['ShipFromAddress'] = $ShipFromAddress;

                        $InboundShipmentItems = [];
                        $QtyShipped_update_arr = [];
                        foreach( $fbaShipmentAmazon->fbashipmentItems AS $fbaShipmentItemAmazon )
                        {
                            $InboundShipmentItemsTemp = [
                                'ShipmentId' => $fbaShipmentItemAmazon->shipment_id,
                                'SellerSKU' => $fbaShipmentItemAmazon->seller_sku,
                                'FulfillmentNetworkSKU' => $fbaShipmentItemAmazon->fulfillment_network_sku,
                                'QuantityShipped' => $fbaShipmentItemAmazon->quantity_shipped,
                            ];

                            $InboundShipmentItems[] = $InboundShipmentItemsTemp;

                            $QtyShipped_update_arr[] = [
                                'id' => $fbaShipmentItemAmazon->id,
                                'quantity_shipped' => $InboundShipmentItemsTemp['QuantityShipped'],
                            ];
                        }

                        $body = [
                            'MarketplaceId' => $marketplaceId,
                            'InboundShipmentHeader' => $InboundShipmentHeader,
                            'InboundShipmentItems' => $InboundShipmentItems,
                        ];
                        
                        return ['status'=>'success', 'message'=>'Shipment created successfully.', 'shipment_id'=>$shipmentId];
                        $fbaRes = $fbaShipmentApi->createInboundShipment($body, $fbaShipmentAmazon->shipment_id);
                        
                        if(isset($fbaRes['payload']['ShipmentId']) && !empty($fbaRes['payload']['ShipmentId']))
                        {
                            $fbaShipObj->shipmentPlan->status = 4;
                            $fbaShipObj->shipmentPlan->save();

                            $fbaShipObj->is_update = 0;
                            $fbaShipObj->is_approved = 2;
                            // $fbaShipObj->internal_status = 1;
                            $fbaShipObj->save();

                            $fbaShipmentAmazon->are_cases_required = $InboundShipmentHeader['AreCasesRequired'] ? 1 : 0;
                            $fbaShipmentAmazon->shipment_name = $InboundShipmentHeader['ShipmentName'];
                            $fbaShipmentAmazon->box_contents_source = $fba_BoxContentsSource_fliped_arr[$InboundShipmentHeader['IntendedBoxContentsSource']] ?? 0;
                            $fbaShipmentAmazon->shipment_status = $fba_ShipmentStatusList_fliped_arr[ $InboundShipmentHeader['ShipmentStatus'] ] ?? 0;
                            $fbaShipmentAmazon->save();

                            Batch::update(new FbaShipmentItem, $QtyShipped_update_arr, "id");

                            $response = ['status'=>'success', 'message'=>'Shipment created successfully.', 'shipment_id'=>$shipmentId];
                        } elseif(isset($fbaRes['errors'])) {
                            $error_msg = $fbaRes['errors'][0]['message'];

                            $error = json_encode($fbaRes);

                            $fbaShipObj->shipmentPlan->status = 5;
                            $fbaShipObj->shipmentPlan->remark = $error;
                            $fbaShipObj->shipmentPlan->save();

                            $fbaShipObj->is_approved = 4;
                            // $fbaShipObj->internal_status = 0;
                            $fbaShipObj->remark = $error;
                            $fbaShipObj->save();
                            
                            $response = ['status'=>'error', 'message'=>$error_msg];
                        }
                    }
                }
                // Log cron end
                // $cronLog->updateEndTime();
            } catch(\Exception $e) {
                $error_msg = 'Line No.:'. $e->getLine() .', File:'. $e->getFile() . ', Message: ' .$e->getMessage();
                $response = ['status'=>'error', 'message' => $error_msg];
            }

        } else {
            $error_msg = 'Plan id not getting.';
            $response = ['status'=>'error', 'message' => $error_msg];
        }

        return $response;
    }
}