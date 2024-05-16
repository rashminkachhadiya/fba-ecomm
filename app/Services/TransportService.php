<?php

namespace App\Services;

use Tops\AmazonSellingPartnerAPI\Api\FbaShipment;
use Batch;
use App\Services\CronCommonService;
use App\Models\FbaShipmentTransportDetail;
use App\Models\FbaShipmentLog;
use App\Models\FbaShipment as FbaShipmentModel;
use Auth;
use Carbon\Carbon;
use App\Models\FbaShipmentTransportPalletDetail;

class TransportService extends BaseService
{
    public CronCommonService $commonService;

    public function __construct()
    {
        $this->commonService = new CronCommonService();
    }

    public function invokePutTransportDetailApi($storeId, $shipmentId, $body)
    {
        $response = [];

        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#puttransportdetails
        $fbaShipmentApi = $this->commonAuthentication($storeId); 

        // $fbaRes = $fbaShipmentApi->putTransportDetails($body, $shipmentId);

        $fbaRes = [
            'payload' => [
                'TransportResult' => [
                    'TransportStatus' => 'WORKING'
                ]
            ]
        ];

        $commonResponse = $this->commonResponse($shipmentId, $fbaRes, $systemStatus = '1');

        if(is_null($commonResponse)){
           $response = ['status'=>'success', 'message'=>'Transport details sent successfully.'];
        }else{
           $response = ['status'=>'error', 'message' => $commonResponse];
        }

        return $response;
    }

    public function invokeEstimateTransportApi($storeId, $shipmentId){
        $response = [];
       
        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#gettransportdetails
        $fbaShipmentApi = $this->commonAuthentication($storeId); 

        // $fbaRes = $fbaShipmentApi->estimateTransport($shipmentId);
        $fbaRes = [
            'payload' => [
                'TransportResult' => [
                    'TransportStatus' => 'ESTIMATED'
                ]
            ]
        ];

        $commonResponse = $this->commonResponse($shipmentId, $fbaRes, $systemStatus = '2');
        
        if(is_null($commonResponse)){
            $getTransformDetails = $this->invokeGetTransportDetailApi($fbaShipmentApi, $shipmentId, $transportStatus = 'ESTIMATED');
            if(isset($getTransformDetails['status']) && $getTransformDetails['status'] == 'success'){
                $response = ['status'=>'success', 'message'=>'Estimate Transport Detail Successfully.'];
            }else{
                $response = ['status'=>'error', 'message' => 'Something went wrong. Please try again.'];
            }
        }else{
           $response = ['status'=>'error', 'message' => $commonResponse];
        }
      
        return $response;
    }

    public function invokeGetTransportDetailApi($fbaShipmentApi, $shipmentId, $transportStatus){
        $response = [];

        $fba_shipment_data = FbaShipmentModel::where('shipment_id', $shipmentId)->first();

        // $fbaRes = $fbaShipmentApi->getTransportDetails($shipmentId);

        // if(isset($fbaRes['payload']['TransportContent']) && !empty($fbaRes['payload']['TransportContent']))
        // {
        //     $transportContent = $fbaRes['payload']['TransportContent'];

        //     $transportDetail = $transportContent['TransportDetails'];

        //     $transportStatus = isset($transportContent['TransportResult']['TransportStatus']) ? $transportContent['TransportResult']['TransportStatus'] : '';
            
        //     $IsPartnered = isset($transportContent['TransportHeader']['IsPartnered']) ? $transportContent['TransportHeader']['IsPartnered'] : '';

        //     $ShipmentType = isset($transportContent['TransportHeader']['ShipmentType']) ? $transportContent['TransportHeader']['ShipmentType'] : '';

        //     if($IsPartnered){
        //         $transport_obj = $ShipmentType == 'SP' ? $transportDetail['PartneredSmallParcelData'] : $transportDetail['PartneredLtlData'];
        //     }else{
        //         $transport_obj = $ShipmentType == 'SP' ? $transportDetail['NonPartneredSmallParcelData'] : $transportDetail['NonPartneredLtlData'];
        //     }

            if($transportStatus == 'ESTIMATED'){

                FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                    ->update([
                        'estimate_shipping_cost'	=>	'33.2',
                        'shipping_currency'			=>	'CAD',
                        'confirm_deadline'          =>   Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
                        'transport_status'	        =>	$transportStatus,
                        'is_added_from'	            =>	1,
                ]);
                // if(isset($transport_obj['PartneredEstimate']))
                // {
                //     FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                //         ->update([
                //             'estimate_shipping_cost'	=>	(string)$transport_obj['PartneredEstimate']['Amount']['Value'],
                //             'shipping_currency'			=>	(string)$transport_obj['PartneredEstimate']['Amount']['CurrencyCode'],
                //             'confirm_deadline'          =>  isset($transport_obj['PartneredEstimate']['ConfirmDeadline']) ? date('Y-m-d H:i:s', strtotime((string)$transport_obj['PartneredEstimate']['ConfirmDeadline'])) : NULL,
                //             'transport_status'	        =>	'ESTIMATED',
                //             'is_added_from'	            =>	1,
                //     ]);
                    
                // }
            }elseif($transportStatus == 'CONFIRMED'){

                FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                    ->update([
                        'estimate_shipping_cost'	=>	'33.2',
                        'shipping_currency'			=>	'CAD',
                        'confirm_deadline'          =>   Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
                        'transport_status'	        =>	$transportStatus,
                        'is_added_from'	            =>	1,
                ]);
                // if(isset($transport_obj['PartneredEstimate'])){

                //     FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                //         ->update([
                //             'void_cost_deadline'	    =>	isset($transport_obj['PartneredEstimate']['VoidDeadline']) ? date('Y-m-d H:i:s', strtotime((string)$transport_obj['PartneredEstimate']['VoidDeadline'])) : NULL,
                //             'transport_status'	        =>	'CONFIRMED',
                //             'is_added_from'	            =>	1,
                //         ]);
                // }
            }else{
                FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)->delete();
                FbaShipmentTransportPalletDetail::where('fba_shipment_id', $fba_shipment_data->id)->delete();
            }   
        // $response = ['status'=>'success'];

        // }elseif(isset($fbaRes['errors']) && !empty($fbaRes['errors'])){
        //     $error_msg = $fbaRes['errors'][0]['message'];

        //     FbaShipmentTransportDetail::where('fba_shipment_id', $fbaShipObj->id)
        //         ->update([
        //             'transport_status' => NULL,
        //             'error_code' => $fbaRes['errors'][0]['code'] ?? NULL,
        //             'error_description' => $fbaRes['errors'][0]['details'] ?? NULL,
        //             'is_added_from' => 1,

        //         ]);

        //     $response = ['status'=>'error', 'message' => $error_msg];
        // }
        $response = ['status'=>'success'];
        return $response;
        
    }

    public function commonAuthentication($storeId){
        // Get store config for store id
        $storeData = $this->commonService->getStore($storeId);

        $this->commonService->setReportApiConfig($storeData);

        $data = $this->commonService->reportApiConfig;
        $data['put_type'] = 'json';

        $fbaShipmentApi = new FbaShipment($data);

        return $fbaShipmentApi;
    }

    public function commonResponse($shipmentId, $fbaRes, $systemStatus){
        $error_msg = null;

        $fba_shipment_data = FbaShipmentModel::where('shipment_id', $shipmentId)->first();

        if(isset($fbaRes['payload']['TransportResult']) && !empty($fbaRes['payload']['TransportResult']))
        {
            $response = $fbaRes['payload']['TransportResult'];

            FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                ->update([
                    'transport_status' => $response['TransportStatus'] ?? NULL,
                    'error_code' => $response['ErrorCode'] ?? NULL,
                    'error_description' => $response['ErrorDescription'] ?? NULL,
                    'system_status' => $systemStatus
                ]);

            FbaShipmentLog::create([
                'fba_shipment_id' => $fba_shipment_data->id,
                'type' => 2,
                'title' => 'Transport detail by '. Auth::user()->name.' & '.Auth::user()->email,
                'description' => '<div class="row"><h6>Transport detail</h6><p>Transport status :'.$response['TransportStatus'].'</p></div>'
            ]);

            $fba_shipment_data->has_transport_detail = 1;
            $fba_shipment_data->save();

        } elseif(isset($fbaRes['errors'])){

            $error_msg = $fbaRes['errors'][0]['message'];

            FbaShipmentTransportDetail::where('fba_shipment_id', $fba_shipment_data->id)
                ->update([
                    'transport_status' => NULL,
                    'error_code' => $fbaRes['errors'][0]['code'] ?? NULL,
                    'error_msg' => $fbaRes['errors'][0]['message'] ?? 'Something went wrong. Please try again',
                    'error_description' => $fbaRes['errors'][0]['details'] ?? NULL,
                ]);
        }

        return $error_msg;
    }

    public function invokeConfirmTransportApi($storeId, $shipmentId){
        $response = [];

        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#gettransportdetails
        $fbaShipmentApi = $this->commonAuthentication($storeId); 

        // $fbaRes = $fbaShipmentApi->confirmTransport($shipmentId);
        $fbaRes = [
            'payload' => [
                'TransportResult' => [
                    'TransportStatus' => 'CONFIRMED'
                ]
            ]
        ];
        $commonResponse = $this->commonResponse($shipmentId, $fbaRes, $systemStatus = '3');

        if(is_null($commonResponse)){
            $getTransformDetails = $this->invokeGetTransportDetailApi($fbaShipmentApi, $shipmentId, $transportStatus = "CONFIRMED");
            if(isset($getTransformDetails['status']) && $getTransformDetails['status'] == 'success'){
                $response = ['status'=>'success', 'message'=>'Transport Detail Confirmed Successfully.'];
            }else{
                $response = ['status'=>'error', 'message' => 'Something went wrong. Please try again.'];
            }
        }else{
           $response = ['status'=>'error', 'message' => $commonResponse];
        }
      
        return $response;
    }

    public function invokeVoidTransportApi($storeId, $shipmentId){
        $response = [];
        $fbaRes = [];

        // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#gettransportdetails
        $fbaShipmentApi = $this->commonAuthentication($storeId); 

        // $fbaRes = $fbaShipmentApi->voidTransport($shipmentId);
        $fbaRes = [
            'payload' => [
                'TransportResult' => [
                    'TransportStatus' => 'VOIDED'
                ]
            ]
        ];
        $commonResponse = $this->commonResponse($shipmentId, $fbaRes, $systemStatus = '0');

        if(is_null($commonResponse)){
            $getTransformDetails = $this->invokeGetTransportDetailApi($fbaShipmentApi, $shipmentId, $transportStatus = "VOIDED");
            if(isset($getTransformDetails['status']) && $getTransformDetails['status'] == 'success'){
                $response = ['status'=>'success', 'message' => 'Transport Details Cancelled Successfully'];
            }else{
                $response = ['status'=>'error', 'message' => 'Something went wrong. Please try again.'];
            }
        }else{
           $response = ['status'=>'error', 'message' => $commonResponse];
        }
      
        return $response;
    }
}