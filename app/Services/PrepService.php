<?php

namespace App\Services;

use App\Helpers\CommonHelper;
use App\Models\FbaPrepBoxDetail;
use App\Models\FbaPrepDetail;
use App\Models\FbaPrepNote;
use App\Models\FbaShipment;
use App\Models\FbaShipmentItem;
use App\Models\MultiSkusBox;
use App\Models\PrepLog;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Batch;
use Illuminate\Support\Facades\Log;
use Tops\AmazonSellingPartnerAPI\Api\FbaShipment as FbaShipmentApi;

class PrepService 
{
    public function getTotalDoneUnits($shipmentId)
    {
        $totalDoneUnit = 0;
        if(isset($shipmentId) && !empty($shipmentId))
        {
            $doneUnits = FbaPrepDetail::select(DB::raw("SUM(done_qty) as done_units"))->where('fba_shipment_id', $shipmentId)->first()->toArray();
           
            $totalDoneUnit = $doneUnits['done_units'];
        }
        
        return $totalDoneUnit;
    }

    public function calPercentage($num_amount, $num_total)
    {
        $count1 = 0;
        if ($num_total != 0)
        {
            $count1 = $num_amount / $num_total;
        }
        $count2 = $count1 * 100;
        $count = number_format($count2, 0);
        return $count;
    }

    public function prepInstructions()
    {
        $prepInstructionArr = [
            0 => 'Polybagging',
            1 => 'BubbleWrapping',
            2 => 'Taping',
            3 => 'BlackShrinkWrapping',
            4 => 'Labeling',
            5 => 'HangGarment',
            6 => 'SetCreation',
            7 => 'Boxing',
            8 => 'RemoveFromHanger',
            9 => 'Debundle',
            10 => 'SuffocationStickering',
            11 => 'CapSealing',
            12 => 'SetStickering',
            13 => 'BlankStickering',
            14 => 'NoPrep'
        ];

        return $prepInstructionArr;
    }

    public function generateProductBarcodeImages($label_details=array(),$big='', $count='')
    {
        $file_data=array();
        if(!empty($label_details))
        {
            if (!Storage::exists('public/uploads/barcode/3in1/'))
            {
                Storage::makeDirectory('public/uploads/barcode/3in1/', 0777, true);
                print("storage");
            }

            $targetPath = storage_path("app/public/uploads/barcode/3in1/");
            //$targetPath     = public_path()."/uploads/barcode/3in1/";
            $productData    = "{$label_details['fnsku']}";
            $barcode        = new \Com\Tecnick\Barcode\Barcode();
            if(!empty($big))
            {
                $bobj = $barcode->getBarcodeObj('C128A', "{$productData}", 270, 26, 'black', array(0,0,0,0))->setBackgroundColor('white');
            } else {
                $bobj = $barcode->getBarcodeObj('C128A', "{$productData}", 189, 26, 'black', array(0,0,0,0))->setBackgroundColor('white'); 
            }

            $imageData      = $bobj->getSvgCode();
            $timestamp      = time();
            $filename       = 'product_barcode_'.$count.'_'.$label_details['fnsku'].'_'.$timestamp . '.svg';
            $filepath       = $targetPath . $filename;                
            file_put_contents($filepath, $imageData);

            //prepare response data
            $file_data['filepath']=$filepath;
            $file_data['filename']=$filename;
        }        
        return $file_data;        
    }

    public function commonGenerateBoxLabelsPdf($request, $printBoxIdsArr = [])
    {
        //box 2d barcode generate_twod_barcode_image
        $pdf_url='';

        if (Session::has('multiSkuLabelHtml'))
        {
            Session::forget('multiSkuLabelHtml');
        }
        
        if (!Storage::exists('public/uploads/pdf/'))
        {
            Storage::makeDirectory('public/uploads/pdf/', 0777, true);
            print("storage");
        }

        $targetPath = storage_path("app/public/uploads/pdf/");
        $htmlData = [];

        //simple shipment barcode
        $shipmentLabelImageData = $this->generateShipmentLabelBarcodeImage($request, 'normal', $printBoxIdsArr);
        
        //label 2d barcode
        $shipmentTwodlabelImageData = $this->generateShipmentLabelBarcodeImage($request, 'twod', $printBoxIdsArr);                
        $twodbarcode_image_data = $this->generateTwodBarcodeImage($request,$printBoxIdsArr);
        $shipment = FbaShipment::where('shipment_id',$request->fba_shipment_id)->first();

        $data['shipment_label_image'] = $shipmentLabelImageData['filename'];
        $data['shipment_label_labelstring'] = $shipmentLabelImageData['labelstring'];
        $data['shipment_twodlabel_image'] = $shipmentTwodlabelImageData['filename'];
        $data['shipment_boxlabel_image'] = $twodbarcode_image_data['filename'];
        $data['product_barcode_image'] = '';

        $data['shipmentData'] = [
            'title' => $shipment->shipment_name,
            'created_at' => Carbon::now(),
            'bcode' => $shipment->shipment_name,
            'box_id' => $shipmentLabelImageData['box_number'],
            'destination_center_id' => $shipment->destination_fulfillment_center_id,
            'amazon_shipment_id' => $request->fba_shipment_id,
            'qty' => array_sum(array_column($request->shipmentItems, 'units')),
            'product_condition' => 'New',
            'truck_name' => '',
        ];

        $data['ship_from_address'] = [
            'Name' => $shipment->shipment_name,
            'ship_from_addr_name' => $shipment->ship_from_addr_name,
            'AddressLine1' => $shipment->ship_from_addr_line1, 
            'City' => $shipment->ship_from_addr_city,
            'StateOrProvinceCode' => $shipment->ship_from_addr_state_province_code,
            'PostalCode' => $shipment->ship_from_addr_postal_code,
            'CountryCode' => $shipment->ship_from_addr_country_code,
        ];

        $data['ship_to_address'] = [
            'Name' => "FBA: ".$shipment->shipment_name,
            'ship_to_addr_name' => $shipment->ship_to_addr_name,
            'AddressLine1' => $shipment->ship_to_addr_line1, 
            'City' => $shipment->ship_to_addr_city,
            'StateOrProvinceCode' => $shipment->ship_to_addr_state_province_code,
            'PostalCode' => $shipment->ship_to_addr_postal_code,
            'CountryCode' => $shipment->ship_to_addr_country_code,
        ];

        $htmlData[] = $data;

        Session::put('multiSkuLabelHtml', $htmlData);

        $this->printBoxLabelLog($request, $shipmentLabelImageData['box_number']);
        $pdf_url = storage_path("app/public/uploads/pdf/Box_Labels.pdf");
        return $pdf_url;
    }

    public function generateShipmentLabelBarcodeImage($request, $type, $printBoxIdsArr)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/'))
        {
            Storage::makeDirectory('public/uploads/barcode/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/barcode/");
        
        $fba_shipment_id = $request->fba_shipment_id;

        $boxDetail = FbaPrepBoxDetail::whereIn('id', $printBoxIdsArr)->select('box_number')->first();

        $box_index      = $boxDetail->box_number;   
        $paddingData    = str_pad($box_index,6,"0",STR_PAD_LEFT);
        $productData    = $fba_shipment_id.'U'.$paddingData;
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        
        if($type=='twod')
        { 
            $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 170, 83, 'black', array(0,0,0,0))->setBackgroundColor('white');
        } else {
            $bobj = $barcode->getBarcodeObj('C128', "{$productData}", 260, 73, 'black', array(0,0,0,0))->setBackgroundColor('white');
        }
        
        $imageData = $bobj->getSvgCode();
        $timestamp = time();
        $count = count($request->shipmentItems);
        if($type=='twod')
        {
            $filename  = 'product_twod_label_multisku_label_'.$count.'_'.$request->shipmentItems[0]['fnsku'].'_'.$timestamp . '.svg';
        } else {
            $filename = 'product_label_multisku_label_'.$count.'_'.$request->shipmentItems[0]['fnsku'].'_'.$timestamp . '.svg';   
        }

        $filepath = $targetPath . $filename;                
        file_put_contents($filepath, $imageData);

        //prepare response data
        $file_data['filepath']=$filepath;
        $file_data['filename']=$filename;
        $file_data['labelstring']=$productData;
        $file_data['box_number'] = $box_index;
      
        return $file_data;
    }

    public function generateTwodBarcodeImage($request, $printBoxIdsArr, $mtype = '', $count = 0)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/2d/'))
        {
            Storage::makeDirectory('public/uploads/barcode/2d/', 0777, true);
            print("storage");
        }

        $targetPath = storage_path("app/public/uploads/barcode/2d/");
        $fba_shipment_id = $request->fba_shipment_id;

        $productData = '';
        $boxDetail = FbaPrepBoxDetail::whereIn('id', $printBoxIdsArr)->get(['fba_shipment_item_id','units','expiry_date'])->groupBy('fba_shipment_item_id')->toArray();

        foreach($request->shipmentItems as $key => $shipmentItem)
        {
            if($key == 0)
            {
                $productData .= "AMZN,PO:".$fba_shipment_id;
            }

            if(isset($boxDetail[$shipmentItem['fba_shipment_item_id']]))
            {
                $qty = $boxDetail[$shipmentItem['fba_shipment_item_id']][0]['units'];
                $expiry_date = $boxDetail[$shipmentItem['fba_shipment_item_id']][0]['expiry_date'];
                
                if($expiry_date != '0000-00-00')
                {
                    $expiry_date = date('ymd',strtotime($expiry_date));           
                    $productData .= ",FNSKU:".$shipmentItem['fnsku'].",QTY:".$qty.",EXP:".$expiry_date;
                } else {
                    $productData .= ",FNSKU:".$shipmentItem['fnsku'].",QTY:".$qty;   
                }
            }
        }
        
        $barcode  = new \Com\Tecnick\Barcode\Barcode();
        $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 350, 147, 'black', array(0,0,0,0))->setBackgroundColor('white');

        $count = 0;
        $imageData      = $bobj->getSvgCode();
        $timestamp      = time();
        // $filename       = 'box_twod_label_'.$count.'_'.$fnsku.'_'.$timestamp . '.svg';
        $filename       = 'multi_sku_label_'.$count.'_'.$request->shipmentItems[0]['fnsku'].'_'.$timestamp . '.svg';
        $filepath       = $targetPath . $filename;                
        file_put_contents($filepath, $imageData);

        //prepare response data
        $file_data['filepath']=$filepath;
        $file_data['filename']=$filename;
             
        return $file_data;
    }

    public function getAddAllBoxes($request)
    {
        $resultArr = $this->getBoxArrayDetails($request);
        
        $doneQty = FbaPrepDetail::whereIn('fba_shipment_item_id',array_column($request->shipmentItems,'fba_shipment_item_id'))->pluck('done_qty','fba_shipment_item_id')->toArray();
 
        $allShipmentItems = [];
        foreach($request->shipmentItems as $shipmentItem)
        {
            if(isset($doneQty[$shipmentItem['fba_shipment_item_id']]))
            {
                $shipmentItem['done_qty'] = $doneQty[$shipmentItem['fba_shipment_item_id']];
                $shipmentItem['remaining_qty'] = $shipmentItem['total_qty'] - $shipmentItem['done_qty'];
                // $shipmentItem['expiry_date'] = $shipmentItem['expiry_date'];
                array_push($allShipmentItems, $shipmentItem);
            }else{
                $shipmentItem['done_qty'] = 0;
                // $shipmentItem['expiry_date'] = NULL;
                $shipmentItem['remaining_qty'] = $shipmentItem['total_qty'];
                array_push($allShipmentItems, $shipmentItem);
            }
        }

        $result = $this->getBatchInsertBoxes($resultArr, $allShipmentItems, $request->fba_shipment_id);
        
        return $result;
    }

    public function getBoxArrayDetails($request)
    {
        $resultArr = [];
        $existBox = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id])->orderBy('box_number','DESC')->first();

        $existBoxNumber = isset($existBox->box_number) && !empty($existBox->box_number) ? $existBox->box_number : 0;
          
        foreach($request->shipmentItems as $shipmentItem)
        {
            $resultArr[] = [
                'fba_shipment_id' => $request->fba_shipment_id,
                'fba_shipment_item_id' => $shipmentItem['fba_shipment_item_id'],
                'units' => $shipmentItem['units'],
                'expiry_date' => isset($shipmentItem['expiry_date']) && !empty($shipmentItem['expiry_date']) ? date('Y-m-d',strtotime($shipmentItem['expiry_date'])) : '',
                'sku' => $shipmentItem['sku'],
                'created_by' => auth()->user()->id,
                'main_image' => $shipmentItem['main_image'],
                'is_printed_type' => 1,
                'box_type' => 1,
                'box_weight' => $shipmentItem['box_weight'],
                'box_width' => $shipmentItem['box_width'],
                'box_height' => $shipmentItem['box_height'],
                'box_length' => $shipmentItem['box_length'],
            ];
        }

        $existBoxNumbersArr = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id])->get()->pluck('box_number')->toArray();

        $missingBoxNumberArr = [];
        if(isset($existBoxNumbersArr) && count($existBoxNumbersArr) > 0)
        {
            $missingBoxNumberArr = $this->missing_box_number($existBoxNumbersArr);
            $missingBoxNumberArr = array_values(array_filter($missingBoxNumberArr));
        }
        
        if(isset($resultArr) && count($resultArr) > 0 && isset($missingBoxNumberArr) && count($missingBoxNumberArr) > 0)
        {
            foreach($resultArr as $key => $value)
            {
                $resultArr[$key]['box_number'] = $missingBoxNumberArr[0];
            }
        }else{
            $boxNo = $existBoxNumber + 1;
            foreach($resultArr as $key => $value)
            {
                $resultArr[$key]['box_number'] =  $boxNo;
            }
        }
       
        return $resultArr;
    }

    public function printBoxLabelLog($request, $boxNo = 0)
    {
        if(isset($request->shipmentItems))
        {
            $user_name = auth()->user()->name;
            
            $title = 'Multi skus box label printed for Box No '.$boxNo.' by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();
           
            foreach($request->shipmentItems as $shipmentItem)
            {
                $data = [
                    'fba_shipment_id' => $shipmentData->id,
                    'fba_shipment_item_id' => $shipmentItem['fba_shipment_item_id'],
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'multi_skus_box_label',
                    'description' => "<h6>Multi skus Box Labels for Box No $boxNo with expiration date printed for a total of quantity ".$shipmentItem['units']."</h6>",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                PrepLog::insert($data);
            } 
        }
    }

    // ASIN Weight Update...
    public function addedAsinLog($request, $type)
    {
        if(isset($request->asin) && !empty($request->asin))
        {
            $asinNote = FbaPrepNote::select('asin_weight')->where('asin', $request->asin)->first()->toArray();
            if($type=='Updated'){ 
                $displayVal = 'updated';
                $desc = "<h6> ASIN Weight ".ucfirst($displayVal)." from ( ".$request->old_asin_weight." Pound ) to ( ".$asinNote['asin_weight']." Pound )</h6>";
            }else{ 
                $displayVal = 'added';
                $desc = "<h6> ASIN Weight ".ucfirst($displayVal)." to ( ".$asinNote['asin_weight']." Pound )</h6>";
            }
            $user_name = auth()->user()->name;
            $title = 'ASIN Weight '.$displayVal.' by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $request->fba_shipment_item_id,
                'type' => 2,
                'title' => $title,
                'field_type' => 'asin_weight',
                'description' => $desc,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            $resData = PrepLog::insert($data);
        }
    }

    public function getBatchInsertBoxes($resultArr, $allShipmentItems, $shipmentId, $completePrepedQty = 0, $already_done_qty = 0)
    {
        if(empty($allShipmentItems))
        {
            return false;
        }
        
        $result = Batch::insert(new FbaPrepBoxDetail, array_keys($resultArr[0]), $resultArr, 50);

        if($result)
        {
            $lastId = FbaPrepBoxDetail::orderByDesc('id')->first()->id;
            $printBoxIdsArr = range($lastId - count($resultArr) + 1, $lastId);
            
            $existPrepDetails = FbaPrepDetail::where('fba_shipment_id', $shipmentId)->whereIn('fba_shipment_item_id', array_column($allShipmentItems, 'fba_shipment_item_id'))->pluck('id','fba_shipment_item_id')->toArray();

            $insertPrepDetailsData = [];
            $updatePrepDetailsData = [];
            $shipmentItemDetails = [];
            if(empty($existPrepDetails))
            {
                foreach($allShipmentItems as $allShipmentItem)
                {
                    $insertPrepDetailsData[] = [
                        'fba_shipment_id' => $shipmentId,
                        'fba_shipment_item_id' => $allShipmentItem['fba_shipment_item_id'],
                        'done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                        'discrepancy_qty' => $allShipmentItem['remaining_qty'] - $allShipmentItem['units'],
                        'actual_done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                        // 'expiry_date' => $allShipmentItem['expiry_date']
                    ];

                    $shipmentItemDetails[] = [
                        'id' => $allShipmentItem['fba_shipment_item_id'],
                        'skus_prepped' => ($allShipmentItem['remaining_qty'] - $allShipmentItem['units'] == 0) ? 2 : 1
                    ];
                }
            }else{
                foreach($allShipmentItems as $allShipmentItem)
                {
                    if(isset($existPrepDetails[$allShipmentItem['fba_shipment_item_id']]))
                    {
                        $updatePrepDetailsData[] = [
                            'id' => $existPrepDetails[$allShipmentItem['fba_shipment_item_id']],
                            'done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                            'discrepancy_qty' => $allShipmentItem['remaining_qty'] - $allShipmentItem['units'],
                            'actual_done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                            // 'expiry_date' => $allShipmentItem['expiry_date']
                        ];
                    }else{
                        $insertPrepDetailsData[] = [
                            'fba_shipment_id' => $shipmentId,
                            'fba_shipment_item_id' => $allShipmentItem['fba_shipment_item_id'],
                            'done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                            'discrepancy_qty' => $allShipmentItem['remaining_qty'] - $allShipmentItem['units'],
                            'actual_done_qty' => $allShipmentItem['done_qty'] + $allShipmentItem['units'],
                            // 'expiry_date' => $allShipmentItem['expiry_date']
                        ];
                    }

                    $shipmentItemDetails[] = [
                        'id' => $allShipmentItem['fba_shipment_item_id'],
                        'skus_prepped' => ($allShipmentItem['remaining_qty'] - $allShipmentItem['units'] == 0) ? 2 : 1
                    ];
                }
            }

            if(!empty($insertPrepDetailsData))
            {
                Batch::insert(new FbaPrepDetail, array_keys($insertPrepDetailsData[0]), $insertPrepDetailsData, 50);
            }

            if(!empty($updatePrepDetailsData))
            {
                Batch::update(new FbaPrepDetail, $updatePrepDetailsData, 'id');
            }

            if(!empty($shipmentItemDetails))
            {
                $shipmentItemData = array_filter($shipmentItemDetails, function($item) {
                    return ($item['skus_prepped'] == 2);
                });

                Batch::update(new FbaShipmentItem, $shipmentItemDetails, 'id');
                
                MultiSkusBox::where('shipment_id', $shipmentId)->whereIn('fba_shipment_item_id', array_column($shipmentItemDetails, 'id'))->delete();
                // MultiSkusBox::where('shipment_id', $shipmentId)->whereIn('fba_shipment_item_id', array_column($shipmentItemData, 'id'))->delete();
                
            }

            return $printBoxIdsArr;
        }
        
        return false;
    }

    public function missing_box_number($num_list)
    {
        // construct a new array
        $new_arr = range(1,max($num_list));                                                    
        // use array_diff to find the missing elements 
        return array_diff($new_arr, $num_list);
    }

    //Reprint Single Box Label log...
    public function reprintSingleBoxLabelLog($request)
    {
        if(isset($request->boxRowId) && !empty($request->boxRowId))
        {
            $boxId = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->pluck('box_number')->first();
            $fba_shipment_item_id = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->pluck('fba_shipment_item_id')->first();
            $user_name = auth()->user()->name;
            $title = 'Box label reprinted for Box ID '.$boxId.' by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();

            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $fba_shipment_item_id,
                'type' => 2,
                'title' => $title,
                'field_type' => 'reprint_single_box_label',
                'description' => "<h6>Box Label reprinted for Box ID ( ". $boxId ." )</h6>",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
           
            PrepLog::insert($data);
        }
    }

    //Reprint All Box Label log...
    public function reprintAllBoxLabelsLog($request)
    {
        if(isset($request->shipment_id) && !empty($request->shipment_id))
        {
            $user_name = auth()->user()->name;
            $title = 'All Box Labels reprinted by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipment_id)->first();
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $request->itemId,
                'type' => 2,
                'title' => $title,
                'field_type' => 'reprint_all_box_labels',
                'description' => "<h6>All Box Labels reprinted.</h6>",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            PrepLog::insert($data);
        }
    }

    //Deleted Single Box ID log...
    public function deletedBoxLog($boxId, $itemId, $shipmentId, $units)
    {
        if(isset($itemId) && !empty($itemId) && isset($shipmentId) && !empty($shipmentId) && isset($boxId) && !empty($boxId))
        {
            $user_name = auth()->user()->name;
            $title = 'Box Label deleted for Box ID '.$boxId.' by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $shipmentId)->first();
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $itemId,
                'type' => 2,
                'title' => $title,
                'field_type' => 'deleted_single_box_label',
                'description' => "<h6>Box Label deleted for Box ID ".$boxId. " ( Count - 1, Qty - ".$units." )</h6>",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            PrepLog::insert($data);
        }
    }

    public function prepDataResult($shipmentData,$prepType)
    {
        $prepLogData = PrepLog::where(['fba_shipment_id' => $shipmentData->id, 'type' =>1])->first();

        if(!empty($prepLogData))
        {
            $prepLogData = $prepLogData->toArray();
        }
        
        if($prepType == "edit")
        {
            if(!empty($prepLogData) && count($prepLogData) > 0)
            {
                $desc = "<h6>Prep Resumed</h6>";
                $keyword = "Resumed";
            }else{
                $desc = "<h6>Prep Started</h6>";
                $keyword = "Started";
            } 
            
            $user_name = auth()->user()->name;
            $title = 'Prep '.$keyword.' by '.$user_name;
            
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'type' => 1,
                'title' => $title,
                'description' => $desc,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            return PrepLog::insert($data);
        }else{
            //when someone view the prep
            return true;
        }
    }

    public function exportPrepDetails($fbaShipmentId, $exportType = '')
    {
        $fbaShipmentItems = FbaShipmentItem::select(
                                'fba_shipment_items.shipment_id',
                                'fba_shipment_items.seller_sku',
                                'fba_shipment_items.quantity_shipped',
                                'fba_prep_details.done_qty',
                                'fba_prep_details.discrepancy_note',
                                'purchase_orders.po_number',
                                'purchase_orders.id as purchase_order_id',
                                'amazon_products.pack_of',
                                'amazon_products.case_pack',
                                'amazon_products.price as case_price',
                                'amazon_products.title'
                            )
                            ->where('fba_shipment_items.fba_shipment_id', $fbaShipmentId)
                            ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
                            ->leftJoin('shipment_plans', 'shipment_plans.id', 'fba_shipment_items.fba_shipment_plan_id')
                            ->leftJoin('purchase_orders', 'purchase_orders.id', 'shipment_plans.po_id')
                            ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
                            ->whereRaw('IFNULL(fba_prep_details.done_qty, 0) < fba_shipment_items.quantity_shipped')
                            ->groupBy('fba_shipment_items.seller_sku')
                            ->get();

        if($fbaShipmentItems->count() == 0)
        {
            return [];
        }

        if($exportType == 'csv')
        {
            return $fbaShipmentItems;
        }

        $exportData = [];
        foreach ($fbaShipmentItems as $item)
        {
            $invoiceNums = '-';
            $discrepancyQty = ($item->quantity_shipped - $item->done_qty);

            $caseNeeded = 0;
            if ((!empty($item->pack_of)) && !empty($item->case_pack))
            {
                $caseNeeded =  number_format(($discrepancyQty * ($item->pack_of/$item->case_pack)), 2);
            }
            
            $extendedPrice = 0;
            if (!empty($caseNeeded) && !empty($item->case_price))
            {
                $extendedPrice = number_format(($caseNeeded * $item->case_price), 2);
            }

            $exportData[] = array(
                'PO Number' => !empty($item->po_number) ? $item->po_number : '-',
                'Shipment ID' => !empty($item->shipment_id) ? $item->shipment_id : '-',
                'Invoice Number' => $invoiceNums,
                'Discrepency Cases' => !empty($caseNeeded) ? abs($caseNeeded) : '-',
                'Product Title' => !empty($item->title) ? $item->title : '-',
                'Case Pack' => !empty($item->case_pack) ? $item->case_pack : '-',
                'Case Price' => !empty($item->case_price) ? $item->case_price : '-',
                'SKU' => $item->seller_sku,
                // 'Extended Price' => !empty($extendedPrice) ? abs($extendedPrice) : '-',
                'Extended Price' => !empty($extendedPrice) ? $extendedPrice : '-',
                'Discrepancy Note' => $item->discrepancy_note,
            );
        }

        return $exportData;
    }

    public static function actualQtyShippedCalculate($originalQtyShipped, $doneQty)
    {
        $percentageValue = floor((config('constants.SHIPMENT_ASIN_QTY_PERCENT') / 100) * $originalQtyShipped);

        $shipmentMaxQty = config('constants.SHIPMENT_ASIN_QTY_MAX');

        $newUpdatedShipmentQty = 0;

        if (!empty($doneQty))
        {
            if ($originalQtyShipped < $doneQty)
            {
                $newUpdatedShipmentQty = $doneQty;
            } elseif ($originalQtyShipped > $doneQty) {
                if ($percentageValue > $shipmentMaxQty)
                {
                    $minimulShippedQty = $originalQtyShipped - $percentageValue;
                } else {
                    $minimulShippedQty = $originalQtyShipped - $shipmentMaxQty;
                }

                if ($minimulShippedQty > $doneQty)
                {
                    $newUpdatedShipmentQty = $minimulShippedQty;
                } else {
                    $newUpdatedShipmentQty = $doneQty;
                }
            } elseif ($originalQtyShipped == $doneQty) {
                $newUpdatedShipmentQty = $doneQty;
            }
        }

        return $newUpdatedShipmentQty;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }

    public function invokeUpdateShipmentApi($shipmentId, $requestData=[], $performAction='', $newShipmentName='', $storeId = '')
    {
        $cronCommonService = new CronCommonService();
        $response = [];

        if(empty($shipmentId))
        {
            return ['status'=>'error', 'message' => 'Shipment id not getting.'];
        }

        if(empty($storeId))
        {
            return ['status'=>'error', 'message' => 'Store is missing.'];
        }
        
        $fba_ShipmentStatusList_arr = config('amazon_params.ShipmentStatusList');
            
        try
        {
            if (!empty($performAction) && $performAction == 'markAsShipped')
            {
                $prepStatusArr = [2];
            } else {
                $prepStatusArr = [0, 1];
            }
            
            $storeData = $cronCommonService->getStore($storeId);

            $cronCommonService->setReportApiConfig($storeData);

            // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#updateinboundshipment
            $configArr = $cronCommonService->reportApiConfig;
            $configArr['put_type'] = "json";

            $fbaShipmentApi = new FbaShipmentApi($configArr);

            $MarketplaceId = $configArr['marketplace_ids'];

            $fbaShipmentObj = FbaShipment::with(['fbaShipmentItems','shipmentPlan'])
                                            ->where("store_id", $storeId)
                                            ->where('shipment_status', 0)
                                            ->whereIn('prep_status', $prepStatusArr)
                                            ->where("id", $shipmentId)
                                            ->first();
            
            if(!empty($fbaShipmentObj))
            {
                $LabelPrepPreference = !empty($fbaShipmentObj->shipmentPlan) ? $fbaShipmentObj->shipmentPlan->prep_preference : 'SELLER_LABEL';

                if(!empty($requestData) || !empty($performAction))
                {
                    if (!empty($performAction) && $performAction == 'updateShipmentName')
                    {
                        $shipmentName = $newShipmentName;
                    } else {
                        $shipmentName = $fbaShipmentObj->shipment_name;
                    }

                    $InboundShipmentHeader = [
                        'ShipmentName' => $shipmentName,
                        'LabelPrepPreference' => $LabelPrepPreference,
                        'DestinationFulfillmentCenterId' => $fbaShipmentObj->destination_fulfillment_center_id,
                        'AreCasesRequired' => ( $fbaShipmentObj->are_cases_required == 1 ),
                        'IntendedBoxContentsSource' => "2D_BARCODE",
                    ];

                    if(!empty($fba_ShipmentStatusList_arr[$fbaShipmentObj->shipment_status]) && $performAction != 'markAsShipped')
                    {
                        $InboundShipmentHeader['ShipmentStatus'] = $fba_ShipmentStatusList_arr[ $fbaShipmentObj->shipment_status ];
                    } elseif ($performAction == 'markAsShipped') {
                        $InboundShipmentHeader['ShipmentStatus'] = 'SHIPPED';
                    }

                    $ShipFromAddress = [
                        "Name" => $fbaShipmentObj->ship_from_addr_name,
                        "AddressLine1" => $fbaShipmentObj->ship_from_addr_line1,
                        "City" => $fbaShipmentObj->ship_from_addr_city,
                        "StateOrProvinceCode" => $fbaShipmentObj->ship_from_addr_state_province_code,
                        "CountryCode" => $fbaShipmentObj->ship_from_addr_country_code,
                        "PostalCode" => $fbaShipmentObj->ship_from_addr_postal_code,
                    ];

                    if( !empty( $fbaShipmentObj->ship_from_addr_district_county ) )
                    {
                        $ShipFromAddress['DistrictOrCounty'] = $fbaShipmentObj->ship_from_addr_district_county;
                    }
                    $InboundShipmentHeader['ShipFromAddress'] = $ShipFromAddress;

                    $InboundShipmentItems = $logData = [];
                    $ShipmentId = $fbaShipmentObj->shipment_id;

                    if(!empty($requestData) && ($performAction != 'markAsShipped' || $performAction == 'updateShipmentName'))
                    {
                        foreach($requestData as $updateData)
                        {
                            $InboundShipmentItems[] = [
                                'ShipmentId' => $fbaShipmentObj->shipment_id,
                                'SellerSKU' => $updateData['sellerSku'],
                                'QuantityShipped' => $updateData['new_update_quantity'],
                            ];
                        }
                    }

                    $body = [
                        'MarketplaceId' => $MarketplaceId,
                        'InboundShipmentHeader' => $InboundShipmentHeader,
                    ];

                    if($performAction != 'markAsShipped' && $performAction != 'updateShipmentName')
                    {
                        $body['InboundShipmentItems'] = $InboundShipmentItems;
                    }

                    // $fbaRes = $fbaShipmentApi->updateInboundShipment($body, $ShipmentId);

                    // if(!empty($fbaRes['payload']['ShipmentId']))
                    // {
                        $user_name = auth()->user()->name;

                        if (!empty($requestData))
                        {
                            foreach($requestData as $dataArr)
                            {
                                $fbaShipmentItem = FbaShipmentItem::where('id', $dataArr['id'])->first();

                                if (!empty($performAction) && $performAction == 'completePrep')
                                {
                                    $titleKeyword = 'Prep Completed';
                                    $actionFrom = 2;

                                    if(!empty($dataArr['done_qty']))
                                    {
                                        $fbaShipmentItem->skus_prepped = 2;
                                    }
                                } else {
                                    $titleKeyword = 'Update shipment qty';
                                    $actionFrom = 1;
                                }

                                $updatedQtyTitle = $titleKeyword. ' by '.$user_name;

                                $description = '<div class="row"><h6>'.$titleKeyword.'</h6><p>Shipment item original qty: '.$fbaShipmentItem->original_quantity_shipped.', old shipped qty: '.$fbaShipmentItem->quantity_shipped.' and new updated qty: '.$dataArr['new_update_quantity'].' by '.$user_name.'</p></div>';

                                $logData[] = [
                                    'fba_shipment_id' => $fbaShipmentObj->id,
                                    'fba_shipment_item_id' => $dataArr['id'],
                                    'type' => 1,
                                    'action_from' => $actionFrom,
                                    'title' => $updatedQtyTitle,
                                    'description' => $description,
                                    'user_id' => auth()->user()->id,
                                ];

                                $fbaShipmentItem->quantity_shipped = $dataArr['new_update_quantity'];
                                $fbaShipmentItem->is_quantity_updated = 1;
                                $fbaShipmentItem->updated_at = CommonHelper::getInsertedDateTime();
                                
                                // Log::info("fba shipment item = ".$fbaShipmentItem);
                                $fbaShipmentItem->save();
                            }
                        }
                        // dd("hello");

                        if (!empty($performAction) && $performAction == 'completePrep')
                        {
                            $fbaShipmentObj->prep_status = 2;
                        }

                        if (!empty($performAction) && $performAction == 'markAsShipped')
                        {
                            $fbaShipmentObj->shipment_status = 2;

                            $logData[] = [
                                'fba_shipment_id' => $fbaShipmentObj->id,
                                'type' => 3,
                                'action_from' => 1,
                                'title' => 'Mark as shipped by ' .$user_name,
                                'description' => '<div class="row"><h6>Mark as shipped</h6></div>',
                                'user_id' => auth()->user()->id,
                            ];
                        }

                        if (!empty($performAction) && $performAction == 'updateShipmentName')
                        {
                            $logData[] = [
                                'fba_shipment_id' => $fbaShipmentObj->id,
                                'type' => 4,
                                'action_from' => 1,
                                'title' => 'Update shipment name by ' .$user_name,
                                'description' => '<div class="row"><h6>Update shipment name</h6><p>Old shipment name is: '.$fbaShipmentObj->shipment_name.' changed to: '.$newShipmentName.' by '.$user_name.'</p></div>',
                                'user_id' => auth()->user()->id,
                            ];

                            $fbaShipmentObj->shipment_name = $newShipmentName;
                        }

                        $fbaShipmentObj->is_update = 2;
                        $fbaShipmentObj->updated_at = CommonHelper::getInsertedDateTime();
                        $fbaShipmentObj->save();

                        if (!empty($performAction) && $performAction == 'completePrep')
                        {
                            $desc = "<h6>Prep Completed</h6>";
                            $keyword = "Completed";

                            $user_name = auth()->user()->name;
                            $title = 'Prep '.$keyword.' by '.$user_name;
                            
                            PrepLog::create([
                                'fba_shipment_id' => $fbaShipmentObj->id,
                                'type' => 1,
                                'title' => $title,
                                'description' => $desc
                            ]);
                        }

                        // if (!empty($logData)) {
                        //     Batch::insert(new FbaShipmentLog,  array_keys($logData[0]), $logData, 500);
                        // }

                        $response = ['status'=>'success', 'message'=>'Shipment updated successfully.', 'shipment_id'=>$shipmentId];
                    // } elseif(empty($fbaRes) || isset($fbaRes['errors']) ) {
                    //     $error_msg = !empty($fbaRes) ? $fbaRes['errors'][0]['message'] : 'response not getting.';

                    //     $error = json_encode($fbaRes);
                        
                    //     foreach($requestData as $dataArr)
                    //     {
                    //         FbaShipmentItem::where('id', $dataArr['id'])->update(['response' => $error]);
                    //     }

                    //     $response = ['status'=>'error', 'message'=>$error_msg];
                    // }
                }
            }
            
        } catch(\Exception $e) {
            $error_msg = 'Line No.:'. $e->getLine() .', File:'. $e->getFile() . ', Message: ' .$e->getMessage();
            $response = ['status'=>'error', 'message' => $error_msg];
        }

        return $response;
    }

    public function generateBoxLabelsPdf($boxId)
    {
        //box 2d barcode generate_twod_barcode_image
        $pdf_url='';

        if (Session::has('multiSkuLabelHtml'))
        {
            Session::forget('multiSkuLabelHtml');
        }
        
        if (!Storage::exists('public/uploads/pdf/'))
        {
            Storage::makeDirectory('public/uploads/pdf/', 0777, true);
            print("storage");
        }

        $targetPath = storage_path("app/public/uploads/pdf/");

        //simple shipment barcode
        $shipmentLabelImageDatas = $this->shipmentLabelBarcodeImage('normal', $boxId);

        if(empty($shipmentLabelImageDatas))
        {
            return '';
        }
        
        //label 2d barcode
        $shipmentTwodlabelImageData = $this->shipmentLabelBarcodeImage('twod', $boxId);  
        
        $boxDetailsQuery = FbaPrepBoxDetail::whereIn('id', $boxId)->select('fba_shipment_item_id','units','expiry_date','fba_shipment_id','sku','box_number');
        $twodbarcode_image_data = $this->twodBarcodeImage($boxDetailsQuery);
        $boxDetails = $boxDetailsQuery->groupBy('fba_shipment_item_id')->get();
        
        $fbaShipmentId = '';
        $totalUnits = 0;
        if($boxDetails->count() > 0)
        {
            $fbaShipmentId = $boxDetails->first()->fba_shipment_id;
            // $totalUnits = $boxDetails->sum('units');
        }

        $shipment = FbaShipment::where('shipment_id',$fbaShipmentId)->first();

        $htmlData = [];
        
        // dd($shipmentLabelImageDatas);
        foreach($shipmentLabelImageDatas as $key => $shipmentLabelImageData)
        {
            $data['shipment_label_image'] = $shipmentLabelImageData['filename'];
            $data['shipment_label_labelstring'] = $shipmentLabelImageData['labelstring'];
            $data['shipment_twodlabel_image'] = $shipmentTwodlabelImageData[$key]['filename'];
            $data['shipment_boxlabel_image'] = $twodbarcode_image_data[$key]['filename'];
            $data['product_barcode_image'] = '';

            $data['shipmentData'] = [
                'title' => $shipment->shipment_name,
                'created_at' => Carbon::now(),
                'bcode' => $shipment->shipment_name,
                'box_id' => $shipmentLabelImageData['box_number'],
                'destination_center_id' => $shipment->destination_fulfillment_center_id,
                'amazon_shipment_id' => $fbaShipmentId,
                'qty' => $shipmentLabelImageData['qty'],
                'product_condition' => 'New',
                'truck_name' => '',
            ];

            $data['ship_from_address'] = [
                'Name' => $shipment->shipment_name,
                'ship_from_addr_name' => $shipment->ship_from_addr_name,
                'AddressLine1' => $shipment->ship_from_addr_line1, 
                'City' => $shipment->ship_from_addr_city,
                'StateOrProvinceCode' => $shipment->ship_from_addr_state_province_code,
                'PostalCode' => $shipment->ship_from_addr_postal_code,
                'CountryCode' => $shipment->ship_from_addr_country_code,
            ];

            $data['ship_to_address'] = [
                'Name' => "FBA: ".$shipment->shipment_name,
                'ship_to_addr_name' => $shipment->ship_to_addr_name,
                'AddressLine1' => $shipment->ship_to_addr_line1, 
                'City' => $shipment->ship_to_addr_city,
                'StateOrProvinceCode' => $shipment->ship_to_addr_state_province_code,
                'PostalCode' => $shipment->ship_to_addr_postal_code,
                'CountryCode' => $shipment->ship_to_addr_country_code,
            ];

            $htmlData[] = $data;
        }

        Session::put('multiSkuLabelHtml', $htmlData);

        $user_name = auth()->user()->name;
        
        if($boxDetails->count() > 0)
        {
            foreach($boxDetails as $boxDetail)
            {
                $title = 'Multi skus box label printed for Box No '.$boxDetail->box_number.' by '.$user_name;
                $prepLogData = [
                    'fba_shipment_id' => $shipment->id,
                    'fba_shipment_item_id' => $boxDetail->fba_shipment_item_id,
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'multi_skus_box_label',
                    'description' => "<h6>Multi skus Box Labels for Box No ".$boxDetail->box_number." with expiration date printed for a total of quantity ".$boxDetail->units."</h6>",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                PrepLog::insert($prepLogData);
            }
        }

        $pdf_url = storage_path("app/public/uploads/pdf/Box_Labels.pdf");
        return $pdf_url;
    }

    public function shipmentLabelBarcodeImage($type, $boxId)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/'))
        {
            Storage::makeDirectory('public/uploads/barcode/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/barcode/");
        
        // $boxDetail = FbaPrepBoxDetail::whereIn('id', $boxId)->select('box_number','fba_shipment_id','units','sku')->first();
        $boxDetails = FbaPrepBoxDetail::whereIn('id', $boxId)->select('box_number','fba_shipment_id','units','sku')->get();

        // if(empty($boxDetail))
        if($boxDetails->count() == 0)
        {
            return [];
        }

        $fileData = [];
        foreach($boxDetails as $boxDetail)
        {
            $box_index      = $boxDetail->box_number;   
            $paddingData    = str_pad($box_index,6,"0",STR_PAD_LEFT);
            $productData    = $boxDetail->fba_shipment_id.'U'.$paddingData;
            $barcode = new \Com\Tecnick\Barcode\Barcode();
            
            if($type=='twod')
            { 
                $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 170, 83, 'black', array(0,0,0,0))->setBackgroundColor('white');
            } else {
                $bobj = $barcode->getBarcodeObj('C128', "{$productData}", 260, 73, 'black', array(0,0,0,0))->setBackgroundColor('white');
            }
            
            $imageData = $bobj->getSvgCode();
            $timestamp = time();
            $count = $boxDetail->units;
            if($type=='twod')
            {
                $filename  = 'product_twod_label_multisku_label_'.$count.'_'.$boxDetail->sku.'_'.$timestamp . '.svg';
            } else {
                $filename = 'product_label_multisku_label_'.$count.'_'.$boxDetail->sku.'_'.$timestamp . '.svg';   
            }

            $filepath = $targetPath . $filename;                
            file_put_contents($filepath, $imageData);

            //prepare response data
            $file_data['filepath']=$filepath;
            $file_data['filename']=$filename;
            $file_data['labelstring']=$productData;
            $file_data['box_number'] = $box_index;
            $file_data['qty'] = $count;

            $fileData[] = $file_data;
        }
        
        return $fileData;
    }

    public function twodBarcodeImage($boxDetailsQuery)
    {
        $boxDetails = $boxDetailsQuery->get();
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/2d/'))
        {
            Storage::makeDirectory('public/uploads/barcode/2d/', 0777, true);
            print("storage");
        }

        $targetPath = storage_path("app/public/uploads/barcode/2d/");

        $productData = '';
        
        $fileData = [];
        $productData .= "AMZN,PO:".$boxDetails->first()->fba_shipment_id;
        foreach($boxDetails as $boxDetail)
        {
            $qty = $boxDetail->units;
            $expiry_date = $boxDetail->expiry_date;
            
            if($expiry_date != '0000-00-00')
            {
                $expiry_date = date('ymd',strtotime($expiry_date));           
                $productData .= ",FNSKU:".$boxDetail->sku.",QTY:".$qty.",EXP:".$expiry_date;
            } else {
                $productData .= ",FNSKU:".$boxDetail->sku.",QTY:".$qty;   
            }
        // }
        
            $barcode  = new \Com\Tecnick\Barcode\Barcode();
            $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 350, 147, 'black', array(0,0,0,0))->setBackgroundColor('white');

            // $count = $boxDetails->sum('units');
            $imageData      = $bobj->getSvgCode();
            $timestamp      = time();
            // $filename       = 'box_twod_label_'.$count.'_'.$fnsku.'_'.$timestamp . '.svg';
            $filename       = 'multi_sku_label_'.$qty.'_'.$boxDetails->first()->sku.'_'.$timestamp . '.svg';
            $filepath       = $targetPath . $filename;                
            file_put_contents($filepath, $imageData);

            //prepare response data
            $file_data['filepath']=$filepath;
            $file_data['filename']=$filename;
            $file_data['fba_shipment_id'] = $boxDetails->first()->fba_shipment_id;
            // $file_data['total_units'] = $qty;
            $fileData[] = $file_data;
        }
        return $fileData;
    }

    // public function printBoxLabelLog($request, $boxNo = 0)
    // {
    //     if(isset($request->shipmentItems))
    //     {
    //         $user_name = auth()->user()->name;
            
    //         $title = 'Multi skus box label printed for Box No '.$boxNo.' by '.$user_name;
    //         $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();
           
    //         foreach($request->shipmentItems as $shipmentItem)
    //         {
    //             $data = [
    //                 'fba_shipment_id' => $shipmentData->id,
    //                 'fba_shipment_item_id' => $shipmentItem['fba_shipment_item_id'],
    //                 'type' => 2,
    //                 'title' => $title,
    //                 'field_type' => 'multi_skus_box_label',
    //                 'description' => "<h6>Multi skus Box Labels for Box No $boxNo with expiration date printed for a total of quantity ".$shipmentItem['units']."</h6>",
    //                 'created_at' => Carbon::now(),
    //                 'updated_at' => Carbon::now(),
    //             ];

    //             PrepLog::insert($data);
    //         } 
    //     }
    // }
}