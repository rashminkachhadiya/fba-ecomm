<?php

namespace App\Http\Controllers\Prep;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Prep\FbaPrepDataTable;
use App\Http\Requests\PrepBoxRequest;
use App\Http\Requests\PrepItemRequest;
use App\Models\AmazonProduct;
use App\Models\FbaPrepBoxDetail;
use App\Models\FbaPrepDetail;
use App\Models\FbaShipment;
use App\Models\FbaShipmentItem;
use App\Models\FbaShipmentItemPrepDetail;
use App\Models\PrepLog;
use App\Models\ShipmentPlan;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Batch;
use App\Models\FbaPrepNote;
use App\Models\MultiSkusBox;
use App\Models\PrepNoteLog;
use Illuminate\Support\Facades\Validator;
use App\Services\PrepService;
use App\Exports\ExcelExport;
use Excel;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;

class FbaPrepController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function index(FbaPrepDataTable $dataTable, Request $request)
    {
        $prepStatus = config('constants.prep_status');
        $shipmentStatus = config('constants.fba_shipment_status_filter');
        $latestFbaShipment = FbaShipment::select('id', 'shipment_status', 'updated_at')->where('shipment_status', '!=', null)->orderby('id', 'DESC')->first();
        $listingCols = $dataTable->listingColumns();

        return $dataTable->render('fba_prep.prep_list', compact('latestFbaShipment','listingCols','prepStatus','shipmentStatus'));
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.fba_prep_list'));
        if (isset($response['status']) && $response['status'] == true) {

            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }

    public function editPrep(Request $request, $shipmentId)
    {
        $allShipmentIds = FbaShipment::where('shipment_id', $shipmentId)->get();
        $shipment = $allShipmentIds[0];
        // $shipment = FbaShipment::select('id','shipment_status','prep_status')->where('shipment_id', $shipmentId)->first();
        
        if ($shipment->shipment_status != 6)
        {
            $prepType = $shipment->prep_status=='2' ? 'ViewPrep' : 'EditPrep';
        } else {
            $prepType = 'ViewPrep';
        }
        
        $params = [];
        if(isset($request->product_info_search) && !empty($request->product_info_search))
        {
            $params['product_info_search'] = $request->product_info_search;
        }
        
        $typo = "multiple";

        $shipmentItems = FbaShipmentItem::getPrepDetailsInfo($typo,$shipmentId, $params, $request->page);
        
        $totalShippedUnits = FbaShipmentItem::getTotalQtyShippedUnits($shipmentId);
        $totalShippedUnits = $totalShippedUnits->total_units;
        $totalDoneUnits = $this->getTotalDoneUnits($shipmentId);
        $cal_percentage = $this->calPercentage($totalDoneUnits,$totalShippedUnits);
        $fbaPrepAllBoxDetail = FbaShipmentItem::fbaPrepAllBoxDetail($shipmentId);
        $prepInstructionsArr = $this->prepInstructions();
        $totalSkus = FbaShipmentItem::where('fba_shipment_id',$shipment->id)->where('quantity_shipped', '!=', 0)->count('id');
        $skusPrepped = FbaShipmentItem::where('fba_shipment_id', $shipment->id)->whereIn('skus_prepped', [2, 3])->count('id');
        $prep_instruction_status = FbaShipmentItemPrepDetail::where('fba_shipment_id', $shipment->id)->pluck('prep_instruction')->first();

        // $fbaPrepAllBoxDetail = FbaShipmentItem::fbaPrepAllBoxDetail($shipmentId);

        // Plan list for model pop up
        $planList = ShipmentPlan::getDraftPlanList();
       
        // Load more data of po invoice when page scrolls
        if ($request->ajax())
        {
            $view = view('fba_prep.partials._prep_working_data', compact(
                'shipmentItems',
                'shipment',
                'totalShippedUnits',
                'totalDoneUnits',
                'cal_percentage',
                'fbaPrepAllBoxDetail',
                'prepInstructionsArr',
                'totalSkus',
                'skusPrepped',
                'prep_instruction_status',
                'prepType',
                'planList',
                'allShipmentIds'
            ))->render();
            return response()->json(['html' => json_encode($view)]);
        }
 
        // dd($shipmentItems->toArray());
        return view(
            'fba_prep.edit_prep',
            compact(
                'shipmentItems',
                'shipment',
                'totalShippedUnits',
                'totalDoneUnits',
                'cal_percentage',
                'fbaPrepAllBoxDetail',
                'prepInstructionsArr',
                'totalSkus',
                'skusPrepped',
                'prep_instruction_status',
                'fbaPrepAllBoxDetail',
                'prepType',
                'planList',
                'allShipmentIds'
            )
        );
    }

    public function getTotalDoneUnits($shipmentId)
    {
        $totalDoneUnit = 0;
        if(isset($shipmentId) && !empty($shipmentId))
        {
            $doneUnits = FbaPrepDetail::select( DB::raw("SUM(done_qty) as done_units"))->where('fba_shipment_id', $shipmentId)->first()->toArray();
           
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

    public function getSinglePrepDetailsInfo(Request $request)
    {
        if(!empty($request->itemId) && !empty($request->shipmentId))
        {
            $itemId = $request->itemId;
            $shipmentId = $request->shipmentId;
            $shipment = FbaShipment::select('id', 'shipment_status', 'shipment_id', 'prep_status','shipment_name','destination_fulfillment_center_id','shipping_schedule_id')->where('shipment_id', $shipmentId)->first();
            $typo = "single"; $params = [];
            $shipmentItem = FbaShipmentItem::getPrepDetailsInfo($typo,$itemId, $params, null);
            $prepInstructionsArr = $this->prepInstructions();
            $prep_instruction_status = FbaShipmentItemPrepDetail::where('fba_shipment_id', $shipment->id)->pluck('prep_instruction')->first();
            $totalShippedUnits = FbaShipmentItem::getTotalQtyShippedUnits($shipmentId);
            $totalShippedUnits = $totalShippedUnits->total_units;
            $fbaPrepBoxDetail = FbaShipmentItem::fbaPrepBoxDetail($shipmentId, $itemId);
            
            if($shipmentItem)
            {
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'shipment' => $shipment,
                    'shipmentItem' => $shipmentItem,
                    'prepInstructionsArr' => $prepInstructionsArr,
                    'prep_instruction_status' => $prep_instruction_status,
                    'totalShippedUnits' => $totalShippedUnits,
                    'fbaPrepBoxDetail' => $fbaPrepBoxDetail,
                    'message' => 'Suggestion Item per box',
                ]);
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'itemCount' => '',
                    'message' => 'Something went wrong.',
                ]);
            }
        }
    }

    public function generateProductLabels(PrepItemRequest $request)
    {
        if(Session::has('productLabelHtml'))
        {
            Session::forget('productLabelHtml');
        }

        if (!Storage::exists('public/uploads/pdf/'))
        {
            Storage::makeDirectory('public/uploads/pdf/', 0777, true);
            print("storage");
        }

        $targetPath = storage_path("app/public/uploads/pdf/");
        $filename = "product_barcode.png";
        $fnsku = $request->fnsku;
        $title_data = preg_replace('/[^A-Za-z0-9. -]/', '', $request->title);
        $product_condition = $request->product_condition;
        $number_of_label = $request->number_of_label;
        
        $expire_date = $request->expire_date;
        
        $sku = "";
        $filepath = '';
        $barcodeImage ='';
        $label_details['fnsku'] = $fnsku;
        $label_details['sku'] = $sku;
        $file_data = $this->generateProductBarcodeImages($label_details);
        
        if(!empty($file_data))
        {
            $imgPathw = storage_path("app/public/uploads/barcode/3in1/");
            $barcodeImage = "<img style='width: 50mm;height: 7mm;' alt=\"Item Labels\" src='".config('app.url').'/storage/uploads/barcode/3in1/' .$file_data['filename']."' />";
            $filepath = $file_data['filepath']; 
        }

        $htmlData = [];
        for($i=0;$i<$number_of_label;$i++)
        {
            $htmlData[] = [
                'barcodeImage' => $file_data['filename'],
                'fnsku' => $fnsku,
                'title_data' => $title_data,
                'product_condition' => $product_condition,
                'expire_date' => $expire_date,
            ];
        }

        Session::put('productLabelHtml', $htmlData);
        
        $storagePath = Storage::disk('public')->url("uploads/pdf/Item_Labels.pdf");
        if(!empty($htmlData) && !empty($storagePath))
        {
            $user_name = auth()->user()->name;
            $title = 'Item label printed by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipmentId)->first();
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $request->itemId,
                'type' => 2,
                'title' => $title,
                'field_type' => 'Item_labels',
                'description' => "<h6>Item Label Printed ( Count - ". $number_of_label ." )</h6>",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            PrepLog::insert($data);

            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'url' => $storagePath,
                'message' => 'Item Label generated successfully',
            ]);

        }else{
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong.',
            ]);
        }
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

    public function updateSkuValidate(Request $request)
    {
        if(isset($request->fba_shipment_item_id) && !empty($request->fba_shipment_item_id) && isset($request->fba_shipment_id) && !empty($request->fba_shipment_id))
        {
            $result = FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['is_validated' => "1"]);
            if($result)
            {
                $user_name = auth()->user()->name;
                $title = 'Product image validated by '.$user_name;
                $desc = "<h6> Product image validated. </h6>";
                $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();
                $data = [
                    'fba_shipment_id' => $shipmentData->id,
                    'fba_shipment_item_id' => $request->fba_shipment_item_id,
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'product_image_validate',
                    'description' => $desc,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                $resData = PrepLog::insert($data);

                return response()->json([
                    'type' => 'success',
                    'status' => 200,
                    'message' => 'SKU successfully validated',
                ]);
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
        }else{
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong.',
            ]);
        }
    }

    public function generateBoxLabels(PrepBoxRequest $request) 
    {
        $allDoneQty = FbaPrepDetail::where('fba_shipment_item_id',$request->fba_shipment_item_id)->pluck('done_qty')->first();
        $reqTotalQty = array_sum($request->tot_qty);
        $totalDoneQty = ($reqTotalQty + (!empty($allDoneQty) ? $allDoneQty : 0));

        $boxLabelQty = $request->box_lbl_qty;

        $percentageValue = floor((config('constants.SHIPMENT_ASIN_QTY_PERCENT') / 100) * $boxLabelQty);
        $shipmentMaxQty = config('constants.SHIPMENT_ASIN_QTY_MAX');
        
        $newShipmentQty = 0;
        if ($percentageValue > $shipmentMaxQty)
        {
            $newShipmentQty = $boxLabelQty + $percentageValue;
        } else {
            $newShipmentQty = $boxLabelQty + $shipmentMaxQty;
        }

        if ($totalDoneQty <= $newShipmentQty)
        {
            //open pdf in next tab
            $printBoxIdsArr = $this->getAddAllBoxes($request);
            
            if($printBoxIdsArr==false)
            {
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
          
            $pdf_url = $this->common_generate_box_labels_pdf($request, $printBoxIdsArr); 
            if(!empty($pdf_url))
            {
               $storagePath = Storage::disk('public')->url("uploads/pdf/Box_Labels.pdf");
               if(!empty($pdf_url) && !empty($storagePath))
               {
                    $this->printBoxLabelLog($request);
                    
                    if(!empty($request->asin_weight) && empty($request->old_asin_weight)){
                        //first time added asin weight...
                        $this->addedAsinLog($request, 'Added');
                    }

                    if(!empty($request->asin_weight) && !empty($request->old_asin_weight) && $request->asin_weight != $request->old_asin_weight){
                        //updated asin weight time update log...
                        $this->addedAsinLog($request, 'Updated');
                    }
                    
                   return response()->json([
                       'type'   => 'success',
                       'status' => 200,
                       'url' => $storagePath,
                       'message' => 'Box Label generated successfully',
                   ]);
       
               }else{
                   return response()->json([
                       'type'   => 'error',
                       'status' => 400,
                       'message' => 'Something went wrong.',
                   ]);
               }
            }
        } else {
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Overpacking not allowed.',
            ]);
        }
    }

    public function generateProductLabelHtml(Request $request)
    {
        $html = Session::get('productLabelHtml');
        return view('fba_prep.partials.product_label_layout', compact('html'));
    }

    public function generateBoxLabelHtml(Request $request)
    {
        $htmlData = Session::get('boxLabelHtml');
        return view('fba_prep.partials.box_label_layout', compact('htmlData'));
    }

    public function getPerBoxItemCount(Request $request)
	{
		$sum = 0;
		$count = 0;
		$scaleVal = 2;
        $product_weight = !empty($request->asin_weight) ? $request->asin_weight : 0;
        for($b=1;$b<=500;$b++){
            if ($count < 45 && !empty($product_weight)) { //45 pound is the total weight
                //$mult = (float) $product_weight * $b;
                $sum = bcmul($product_weight, $b, $scaleVal);
                $count = $count+1;
            }
        }
		
		$result = floor($count / $product_weight);

        if($result)
        {
            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'itemCount' => $result,
                'message' => 'Suggestion Item per box',
            ]);
        }else{
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'itemCount' => '',
                'message' => 'Something went wrong.',
            ]);
        }
	}

    public function common_generate_box_labels_pdf($request, $printBoxIdsArr = [])
    {
        $file_name='';
        $pdf_url='';
        $html = '';
        // $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [101.6, 152.4]]); //4"X6" converted into MM 

        if (Session::has('boxLabelHtml')) {
            $result=Session::forget('boxLabelHtml');
        }
        
        if (!Storage::exists('public/uploads/pdf/')) {
            Storage::makeDirectory('public/uploads/pdf/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/pdf/");
         
        $mtype = "";
        $amazProduct = [];
        if(isset($request->boxRowId) && !empty($request->boxRowId)){
            $boxDetails = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->get();
            if (isset($boxDetails[0]->sku) && !empty($boxDetails[0]->sku)) {
                $amazProduct = AmazonProduct::select('title','fnsku')->where(['sku' => $boxDetails[0]->sku])->first()->toArray();
            }
        }else{
            if(isset($request[0]->mtype) && !empty($request[0]->mtype) && $request[0]->mtype == 'printAll')
            {
                $boxDetails = $request;
                $mtype = $request[0]->mtype;
            }else{
                if(isset($printBoxIdsArr) && !empty($printBoxIdsArr)){
                    $boxDetails = FbaPrepBoxDetail::whereIn('id',$printBoxIdsArr)->get(); 
                }
                //$boxDetails = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id,'fba_shipment_item_id' => $request->fba_shipment_item_id])->get();
            }
        }

        if(isset($boxDetails) && !empty($boxDetails))
        {
            $htmlData = [];
            $loopCount = 0;
            foreach($boxDetails as $ks => $boxDetail)
            {
                if(!empty($mtype) && $mtype == "printAll")
                {
                    $label_type = !empty($boxDetail->is_printed_type) && $boxDetail->is_printed_type == 2 ? '3in1' : $boxDetail->label_type;
                    $fnsku = $boxDetail->fnsku;
                    $sku = $boxDetail->sku;
                    $destination = $boxDetail->destination;
                    $fba_shipment_id = $boxDetail->fba_shipment_id;
                    $shipment_name = $boxDetail->shipment_name;
                    $isPrintedType = $boxDetail->is_printed_type;
                }else{
                    $label_type = !empty($boxDetail->is_printed_type) && $boxDetail->is_printed_type == 2 ? '3in1' : $boxDetail->label_type;
                    $fnsku = !empty($request->fnsku) ? $request->fnsku : "";
                    $sku = $request->sku;
                    $destination = $request->destination;
                    $fba_shipment_id = $request->fba_shipment_id;
                    $shipment_name = $request->shipment_name;
                    $isPrintedType = $boxDetail->is_printed_type;
                }

                if(empty($fnsku))
                {
                    $fnsku = isset($amazProduct['fnsku']) && !empty($amazProduct['fnsku']) ? $amazProduct['fnsku'] : "";
                }

                $shipment = FbaShipment::where('shipment_id', $fba_shipment_id)->get()->first();

                $is_3in1_box_label_required =  $label_type;
                $type = '';

                $loopCount = $loopCount + 1;
                //simple shipment barcode
                $shipment_label_image_data = $this->generate_shipment_label_barcode_image($request, $type, $boxDetail, $mtype, $loopCount);
                
                //label 2d barcode
                $shipment_twodlabel_image_data = $this->generate_shipment_label_barcode_image($request, $type='twod', $boxDetail, $mtype, $loopCount);                
               
                //box 2d barcode
                $twodbarcode_image_data = $this->generate_twod_barcode_image($request,$boxDetail, $mtype, $loopCount);
                
                //product fnsku barcode
                $product_barcode_image_data = array();
                if(!empty($is_3in1_box_label_required))
                {
                    $label_details['fnsku'] = $fnsku;
                    $label_details['sku'] = $sku;
                    $product_barcode_image_data = $this->generateProductBarcodeImages($label_details,'1', $loopCount);
                }
                
                if(!empty($shipment_label_image_data['filename']) && !empty($twodbarcode_image_data['filename']))
                {
                    $data['shipment_label_image']=$shipment_label_image_data['filename'];
                    $data['shipment_label_labelstring']=$shipment_label_image_data['labelstring'];
                    $data['shipment_twodlabel_image']=$shipment_twodlabel_image_data['filename'];
                    $data['shipment_boxlabel_image']=$twodbarcode_image_data['filename'];
                    $data['product_barcode_image']=!empty($product_barcode_image_data['filename'])?$product_barcode_image_data['filename']:'';
                    $data['is_printed_type'] = $boxDetail->is_printed_type;

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

                    $product_title = null;
                    if (isset($request->product_title)) {
                        $product_title = $request->product_title;
                    } elseif (isset($boxDetail) && !empty($boxDetail)) {
                        $product_title = isset($amazProduct['title']) && !empty($amazProduct['title']) ? $amazProduct['title'] : '';
                    }

                    $data['shipmentData'] = [
                        'title' => $shipment->shipment_name,
                        'created_at' => Carbon::now(),
                        'bcode' => $shipment_name,
                        'box_id' => $boxDetail->box_number,
                        'destination_center_id' => $destination,
                        'amazon_shipment_id' => $fba_shipment_id,
                        'qty' => $boxDetail->units,
                        'fnsku' => $fnsku,
                        'sku' => $sku,
                        'product_condition' => 'New',
                        'expiry_date' => $boxDetail->expiry_date,
                        'product_title' => $product_title,
                        'truck_name' => !empty($shipment->getAssociatedTruck) ? $shipment->getAssociatedTruck->schedule_name : '',
                    ];
                    
                    $htmlData[] = $data;
                    
                    if(is_dir($targetPath)) 
                    {
                        if (!empty($shipment_label_image_data['filepath']) && file_exists($shipment_label_image_data['filepath']))
                        {
                            // unlink($shipment_label_image_data['filepath']);
                        }

                        if (!empty($shipment_twodlabel_image_data['filepath']) && file_exists($shipment_twodlabel_image_data['filepath']))
                        {
                            // unlink($shipment_twodlabel_image_data['filepath']);
                        }

                        if (!empty($twodbarcode_image_data['filepath']) && file_exists($twodbarcode_image_data['filepath']))
                        {
                            // unlink($twodbarcode_image_data['filepath']);
                        }

                        if (!empty($product_barcode_image_data) && !empty($product_barcode_image_data['filepath']) && file_exists($product_barcode_image_data['filepath']))
                        {
                            // unlink($product_barcode_image_data['filepath']);
                        }                        
                    }
                }
            }

            Session::put('boxLabelHtml', $htmlData);
        } 
        
        //case print box label...
        $file_name='Box_Labels.pdf';
        // $mpdf->Output($targetPath.$file_name,'F');
        $pdf_url = storage_path("app/public/uploads/pdf/Box_Labels.pdf");
        
        return $pdf_url;
    }

    public function generate_shipment_label_barcode_image($request, $type, $boxDetail, $mtype, $count)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/'))
        {
            Storage::makeDirectory('public/uploads/barcode/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/barcode/");
        
        if(!empty($mtype) && $mtype == "printAll")
        {
            $label_type = $boxDetail->label_type;
            $fnsku = $boxDetail->fnsku;
            $sku = $boxDetail->sku;
            $destination = $boxDetail->destination;
            $fba_shipment_id = $boxDetail->fba_shipment_id;
        }else{
            $label_type = $request->label_type;
            $fnsku = $request->fnsku;
            $sku = $request->sku;
            $destination = $request->destination;
            $fba_shipment_id = $request->fba_shipment_id;
        }

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
        if($type=='twod')
        {
            $filename  = 'product_twod_label_'.$count.'_'.$fnsku.'_'.$timestamp . '.svg';
        } else {
            $filename = 'product_label_'.$count.'_'.$fnsku.'_'.$timestamp . '.svg';   
        }

        $filepath = $targetPath . $filename;                
        file_put_contents($filepath, $imageData);

        //prepare response data
        $file_data['filepath']=$filepath;
        $file_data['filename']=$filename;
        $file_data['labelstring']=$productData;
      
        return $file_data;
    }

    public function generate_twod_barcode_image($request, $boxDetail, $mtype, $count)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/2d/'))
        {
            Storage::makeDirectory('public/uploads/barcode/2d/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/barcode/2d/");

        if(!empty($mtype) && $mtype == "printAll")
        {
            $label_type = $boxDetail->label_type;
            $fnsku = $boxDetail->fnsku;
            $sku = $boxDetail->sku;
            $destination = $boxDetail->destination;
            $fba_shipment_id = $boxDetail->fba_shipment_id;
            $asin = $boxDetail->asin;
        }else{
            $label_type = $request->label_type;
            $fnsku = $request->fnsku;
            $sku = $request->sku;
            $destination = $request->destination;
            $fba_shipment_id = $request->fba_shipment_id;
            $asin = $request->asin;
        }
     
        $qty = $boxDetail->units;
        $expiry_date = $boxDetail->expiry_date;    
        
        if($expiry_date!='0000-00-00')
        {
            $expiry_date = date('ymd',strtotime($expiry_date));                    
            $productData    = "AMZN,PO:".$fba_shipment_id.",FNSKU:".$fnsku.",QTY:".$qty.",EXP:".$expiry_date;
        } else {
            $productData    = "AMZN,PO:".$fba_shipment_id.",FNSKU:".$fnsku.",QTY:".$qty;   
        }
        
        $barcode  = new \Com\Tecnick\Barcode\Barcode();
        $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 350, 147, 'black', array(0,0,0,0))->setBackgroundColor('white');

        $imageData      = $bobj->getSvgCode();
        $timestamp      = time();
        $filename       = 'box_twod_label_'.$count.'_'.$fnsku.'_'.$timestamp . '.svg';
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
      
        $isExist = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id, 'fba_shipment_item_id' => $request->fba_shipment_item_id])->count();
 
        $done_qty = FbaPrepDetail::where('fba_shipment_item_id',$request->fba_shipment_item_id)->pluck('done_qty')->first();
 
        $done_qty = !empty($done_qty) ? $done_qty : 0;
        $remainingQty = $request->remaining_qty - $done_qty;
        $processingTotalQty = isset($request->tot_qty) && !empty($request->tot_qty) ?  array_sum($request->tot_qty) : 0;
 
        if(empty($done_qty))
        {
           $completePrepedQty = isset($request->tot_qty) && !empty($request->tot_qty) ?  array_sum($request->tot_qty) : 0;
        }else{
            $completePrepedQty = $done_qty + array_sum($request->tot_qty);
        }
        
        if(!empty($processingTotalQty) || !empty($remainingQty))
        {
            if(isset($resultArr) && !empty($resultArr))
            {
                $result = $this->getBatchInsertBoxes($resultArr, $request, $completePrepedQty, $done_qty);
            }
        }else{
             return response()->json([
                 'type'   => 'error',
                 'status' => 400,
                 'message' => "Total box qty can't greater then remaining qty.",
             ]);
        }
        return $result;
    }

    public function getBoxArrayDetails($request)
    {
        $resultArr = [];
        
        if(isset($request->no_of_boxes_count) && !empty($request->no_of_boxes_count))
        {
            $existBox = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id])->orderBy('box_number','DESC')->first();

            $existBoxNumber = isset($existBox->box_number) && !empty($existBox->box_number) ? $existBox->box_number : 0;
            $countBoxes = array_sum($request->no_of_boxes_count);
          
            foreach($request->no_of_boxes_count as $key => $value)
            {
                for($i=1;$i<=$value;$i++)
                {
                    $resultArr[] = [
                        'fba_shipment_id' => $request->fba_shipment_id,
                        'fba_shipment_item_id' => $request->fba_shipment_item_id,
                        'units' => isset($request->per_box_item_count[$key]) ? $request->per_box_item_count[$key] : "",
                        'expiry_date' => isset($request->expiry_box_date[$key]) ? date('Y-m-d', strtotime($request->expiry_box_date[$key])) : "",
                        'sku' => $request->sku,
                        'created_by' => Auth::user()->id,
                        'main_image' => isset($request->main_image) && !empty($request->main_image) ? $request->main_image : "",
                        'is_printed_type' => isset($request->label_type) && !empty($request->label_type) && $request->label_type == '3in1' ? 2 : 1,
                    ];
                } 
            }

            $existBoxNumbersArr = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id])->get()->pluck('box_number')->toArray();

            $missingBoxNumberArr = [];
            if(isset($existBoxNumbersArr) && count($existBoxNumbersArr) > 0)
            {
                $missingBoxNumberArr = $this->missing_box_number($existBoxNumbersArr);
                $missingBoxNumberArr = array_values(array_filter($missingBoxNumberArr));
            }
            
            $counterArr = [];
            if(isset($resultArr) && count($resultArr) > 0 && isset($missingBoxNumberArr) && count($missingBoxNumberArr) > 0)
            {
                foreach($resultArr as $key => $value)
                {
                    foreach($missingBoxNumberArr as $ken => $missedBoxNumber)
                    {
                        if($key==$ken)
                        {
                            $resultArr[$ken]['box_number'] = $missedBoxNumber;
                        }else{
                            $counterArr[] = ($existBoxNumber + $key+1);
                            //$resultArr[$key]['box_number'] =  $existBoxNumber + $key+1;
                        } 
                    }
                }
               
                $newArr = array_values(array_unique($counterArr));
                if(isset($newArr) && !empty($newArr) && count($missingBoxNumberArr) > 0)
                {
                    $noOfCount[] = $countBoxes-count($missingBoxNumberArr);
                    foreach($newArr as $keys => $val)
                    {
                        foreach($noOfCount as $key => $value)
                        {
                            for($i=0;$i<$value;$i++)
                            {
                                if($i==$keys)
                                {
                                    $newKey = $keys + count($missingBoxNumberArr);
                                    $resultArr[$newKey]['box_number'] = $val;
                                }
                            }
                        }
                    }
                }
            }else{
                foreach($resultArr as $key => $value)
                {
                    $resultArr[$key]['box_number'] =  $existBoxNumber + $key+1;
                }
            }
        }
       
        return $resultArr;
    }

    public function printBoxLabelLog($request)
    {
        if(isset($request->no_of_boxes_count) && !empty($request->no_of_boxes_count))
        {
           // $countBoxes = array_sum($request->no_of_boxes_count);
            $totalQty = isset($request->tot_qty[0]) && !empty($request->tot_qty[0]) ? array_sum($request->tot_qty) : "0";
            $user_name = Auth::user()->first_name.' '.Auth::user()->last_name;
            if(!empty($request->label_type)){ $labelType = ", 3 IN 1";}else{ $labelType = ""; }
            $title = $labelType.' Box label printed by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->fba_shipment_id)->first();
           
            for($i=0; $i < count($request->no_of_boxes_count); $i++)
            {
                $data = [
                    'fba_shipment_id' => $shipmentData->id,
                    'fba_shipment_item_id' => $request->fba_shipment_item_id,
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'print_box_labels',
                    //'description' => "<h6>".$labelType." Box Label Printed ( Count - ". $countBoxes .", Qty - ".$totalQty. " )</h6>",
                    'description' => "<h6>".$request->no_of_boxes_count[$i].$labelType." Box Labels with expiration date " . $request->expiry_box_date[$i]. " printed for a total of quantity ".$request->tot_qty[$i]."</h6>",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                $resData = PrepLog::insert($data);
            } 
        }
    }

    // ASIN Weight Update...
    public function addedAsinLog($request, $type)
    {
        if(isset($request->asin) && !empty($request->asin)){
            $asinNote = FbaPrepNote::select('asin_weight')->where('asin', $request->asin)->first()->toArray();
            if($type=='Updated'){ 
                $displayVal = 'updated';
                $desc = "<h6> ASIN Weight ".ucfirst($displayVal)." from ( ".$request->old_asin_weight." Pound ) to ( ".$asinNote['asin_weight']." Pound )</h6>";
            }else{ 
                $displayVal = 'added';
                $desc = "<h6> ASIN Weight ".ucfirst($displayVal)." to ( ".$asinNote['asin_weight']." Pound )</h6>";
            }
            $user_name = Auth::user()->first_name.' '.Auth::user()->last_name;
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

    public function getBatchInsertBoxes($resultArr, $request, $completePrepedQty, $already_done_qty)
    {
        $totalUnitsArr = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->fba_shipment_id, 'fba_shipment_item_id' => $request->fba_shipment_item_id])->get()->pluck('units')->toArray();
       
        $getTotalShipmentsUnits = array_sum($totalUnitsArr) + $request->remaining_qty;
        
        if((!empty($completePrepedQty) && !empty($getTotalShipmentsUnits)) || (empty($already_done_qty) && empty($getTotalShipmentsUnits)))
        {
            $result = Batch::insert(new FbaPrepBoxDetail, array_keys($resultArr[0]), $resultArr, 50);

            if($result)
            {
                $lastId = FbaPrepBoxDetail::orderByDesc('id')->first()->id;
                $printBoxIdsArr = range($lastId - count($resultArr) + 1, $lastId);

                $fbaShipmentItemQty = FbaShipmentItem::select('id','quantity_shipped')->where('id', $request->fba_shipment_item_id)->first();
                
                FbaPrepDetail::updateOrCreate([
                    'fba_shipment_id' => $request->fba_shipment_id,
                    'fba_shipment_item_id' => $request->fba_shipment_item_id
                ],[
                    'done_qty' => $completePrepedQty,
                    'discrepancy_qty' => ($fbaShipmentItemQty->quantity_shipped - $completePrepedQty),
                    'actual_done_qty' => $completePrepedQty,
                ]);

                //update asin weight...
                FbaPrepNote::updateOrCreate([
                    'asin' => $request->asin
                ],[
                    'asin_weight' => $request->asin_weight
                ]);
               
                //Updated skus_prepped status...
                if($getTotalShipmentsUnits < $completePrepedQty)
                {
                    FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['skus_prepped' => "3"]);
                    MultiSkusBox::where('shipment_id', $request->fba_shipment_id)->where('fba_shipment_item_id', $request->fba_shipment_item_id)->delete();
                }else if($getTotalShipmentsUnits == $completePrepedQty){
                    FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['skus_prepped' => "2"]);
                    MultiSkusBox::where('shipment_id', $request->fba_shipment_id)->where('fba_shipment_item_id', $request->fba_shipment_item_id)->delete();
                }else{
                     FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['skus_prepped' => "1"]);
                }

                // if($getTotalShipmentsUnits <= $completePrepedQty){
                //     FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['skus_prepped' => "2"]);
                // }else{
                //      FbaShipmentItem::where('id', $request->fba_shipment_item_id)->update(['skus_prepped' => "1"]);
                // }
                return $printBoxIdsArr;
            }
        }
        return false;
    }

    public function missing_box_number($num_list)
    {
        // construct a new array
        // $new_arr = range($num_list[0],max($num_list));
        $new_arr = range(1,max($num_list));
        // use array_diff to find the missing elements
        // dd(array_diff($new_arr, $num_list));
        return array_diff($new_arr, $num_list);
    }

    public function getViewAllPrepDetailsInfo(Request $request)
    {
        if(isset($request->shipmentId) && !empty($request->shipmentId))
        {
            $shipmentId = $request->shipmentId;
            $fbaPrepBoxDetail = FbaShipmentItem::fbaPrepAllBoxDetail($shipmentId);
        
            if(count($fbaPrepBoxDetail) > 0)
            {
                return response()->json([
                    'type' => 'success',
                    'status' => 200,
                    'fbaPrepBoxDetail' => $fbaPrepBoxDetail
                ]);
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'itemCount' => '',
                    'message' => 'Something went wrong.',
                ]);
            }
        }
    }

    public function generateSingleBoxLabels(Request $request)
    {
        $pdf_url = $this->common_generate_box_labels_pdf($request);

        if(!empty($pdf_url))
        {
           $storagePath = Storage::disk('public')->url("uploads/pdf/Box_Labels.pdf");
           if(!empty($pdf_url) && !empty($storagePath))
           {
                //Generate reprint single box label log...
                $this->reprintSingleBoxLabelLog($request);

                return response()->json([
                   'type'   => 'success',
                   'status' => 200,
                   'url' => $storagePath,
                   'message' => 'Box Label generated successfully',
                ]);
           }else{
                return response()->json([
                   'type'   => 'error',
                   'status' => 400,
                   'message' => 'Something went wrong.',
                ]);
           }
        }
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

    public function generateAllBoxLabels(Request $request, PrepService $prepService)
    {
        $boxDetails = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->shipment_id,'fba_shipment_item_id' => $request->itemId])->get()->toArray();

        $destination = FbaShipment::where('shipment_id',$request->shipment_id)->pluck('destination_fulfillment_center_id')->first();

        $shipment_name = FbaShipment::where('shipment_id',$request->shipment_id)->pluck('shipment_name')->first();
        
        $itemDataIdArr = [];
        if(isset($boxDetails) && !empty($boxDetails))
        {
            foreach($boxDetails as $key => $boxData){
                $itemDataIdArr[] = FbaShipmentItem::where('id',$boxData['fba_shipment_item_id'])->pluck('id')->first();
            }
        }
       
        $itemDataIdArr = array_unique($itemDataIdArr);
        $allItemArr = [];
        if(isset($itemDataIdArr) && !empty($itemDataIdArr))
        {
            foreach($itemDataIdArr as $key => $itemId){
                $allItemArr[$itemId] = FbaShipmentItem::getAllPrepDetailsInfo($itemId);
            }
        }

        $shipmentItemArr = [];
        if(isset($allItemArr) && !empty($allItemArr))
        {
            foreach($allItemArr as $itemId => $itemData){
                $shipmentItemArr[] = $itemData[0];
            }
        }
        //$object = json_decode(json_encode($shipmentItemArr), FALSE);

        $newArr = [];
        $multiSkuArr = [];
        if(isset($shipmentItemArr) && !empty($shipmentItemArr))
        {
            foreach($shipmentItemArr as $key => $shipmentItem)
            {
                if(isset($boxDetails) && !empty($boxDetails))
                {
                    foreach($boxDetails as $key => $boxData)
                    {
                        if($boxData['fba_shipment_item_id']==$shipmentItem['id'])
                        {
                            if($boxData['box_type'] == 1)
                            {
                                $multiSkuArr[] = $boxData['id'];
                            }else{
                                $newArr[] = [
                                    'id' => $shipmentItem['id'],
                                    'done_qty' => $shipmentItem['done_qty'],
                                    'qty' => $shipmentItem['qty'],
                                    'product_title' => $shipmentItem['amazon_data']['title'] ?? null,
                                    'asin' => $shipmentItem['amazon_data']['asin'] ?? null,
                                    'sku' => $shipmentItem['amazon_data']['sku'] ?? null,
                                    'fnsku' => $shipmentItem['amazon_data']['fnsku'] ?? null,
                                    'item_weight' => $shipmentItem['amazon_data']['item_weight'] ?? null,
                                    'fba_shipment_item_id' => $boxData['fba_shipment_item_id'] ?? null,
                                    'fba_shipment_id' => $boxData['fba_shipment_id'],
                                    'box_number' => $boxData['box_number'],
                                    'units' => $boxData['units'],
                                    'expiry_date' => $boxData['expiry_date'],
                                    'label_type' => NULL,
                                    'destination' => isset($destination) ? $destination : "",
                                    'shipment_name' => isset($shipment_name) ? $shipment_name : "",
                                    'mtype' => 'printAll',
                                    'is_printed_type' => $boxData['is_printed_type'],
                                ];
                            }
                        }
                    }
                }
            }
        }

        $object = json_decode(json_encode($newArr), FALSE);
       
        $pdf_url = $this->common_generate_box_labels_pdf($object);

        if(!empty($multiSkuArr))
        {
            $prepService->generateBoxLabelsPdf($multiSkuArr);
        }
        // dd("hello");
        if(!empty($pdf_url))
        {
            $storagePath = Storage::disk('public')->url("uploads/pdf/Box_Labels.pdf");
            if(!empty($pdf_url) && !empty($storagePath))
            {
                //Generate reprint all box labels log...
                // $this->reprintAllBoxLabelsLog($request);

                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'url' => $storagePath,
                    'message' => 'All Box Label generated successfully',
                ]);
    
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
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

    public function deleteSingleBoxData(Request $request)
    {
        if(isset($request->boxRowId) && !empty($request->boxRowId))
        {
            $itemId = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->pluck('fba_shipment_item_id')->first();
            $boxId = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->pluck('box_number')->first();

            $boxDetails = FbaPrepBoxDetail::where(['id' => $request->boxRowId])->delete();
            if(!empty($boxDetails))
            {
                $units = FbaPrepBoxDetail::select(DB::raw("SUM(units) as total_units"))->where(['fba_shipment_item_id' => $itemId])->first()->toArray();

                $fbaShipmentItem = FbaShipmentItem::select('id','quantity_shipped','skus_prepped')->where('id', $itemId)->first();
               
                //update Qty...
                if(isset($itemId) && !empty($itemId))
                {
                    FbaPrepDetail::updateOrCreate([
                        'fba_shipment_item_id' => $itemId
                    ],[
                        'done_qty' => $units['total_units'],
                        'discrepancy_qty' => ($fbaShipmentItem->quantity_shipped - $units['total_units']),
                        'actual_done_qty' => $units['total_units'],
                    ]);

                    //Prepped Skus updated into Fba Shipment Item table...
                    $prepDetailResult = FbaPrepDetail::where('fba_shipment_item_id',$itemId)->first();

                    $totalShipedQty = FbaShipmentItem::where(['id' => $itemId])->pluck('quantity_shipped')->first();

                    if(empty($prepDetailResult['done_qty']) && $prepDetailResult['done_qty']==0){
                        FbaShipmentItem::where('id', $itemId)->update(['skus_prepped' => "0"]);
                    }else{
                        $remainingQty = $totalShipedQty - $prepDetailResult['done_qty'];
                        if($remainingQty == "0"){
                            //complete qty prepped
                            FbaShipmentItem::where(['id' => $itemId])->update(['skus_prepped' => 2]);
                        }else if($remainingQty < 0){
                            //overpacked qty preped
                            FbaShipmentItem::where(['id' => $itemId])->update(['skus_prepped' => 3]);
                        }else{
                            FbaShipmentItem::where('id', $itemId)->update(['skus_prepped' => "1"]);
                        }
                    }
                    
                    $this->deletedBoxLog($boxId, $itemId, $request->shipmentId, $request->units);
                }
            
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'itemId' => $itemId,
                    'message' => 'Box deleted successfully',
                ]);
    
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
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

    public function updatePrepListingLogStatus(Request $request)
    {
        if(!empty($request->shipmentId))
        {
            $shipmentId = $request->shipmentId;
            $prepType = $request->prepType;

            $shipmentData = FbaShipment::select('id','prep_status')->where('shipment_id', $shipmentId)->first();
            
            if($shipmentData->prep_status != "2")
            {
                $result = FbaShipment::where('shipment_id', $shipmentId)->update(['prep_status' => 1]);
                if($result)
                {
                    $resData = $this->prepDataResult($shipmentData,$prepType);
                } 
            }else{
                $result = FbaShipment::where('shipment_id', $shipmentId)->update(['prep_status' => 2]);
                if($result)
                {
                    $resData = $this->prepDataResult($shipmentData,$prepType);
                }
            }

            if($resData)
            {
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'message' => 'prep log status updated.',
                ]);
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
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

    public function getSearchBoxNumber(Request $request)
    {
        if(!empty($request->shipmentId) && !empty($request->typeSrch) && !empty($request->boxNumber))
        {
            if($request->typeSrch=='single' && !empty($request->itemId))
            {
                $boxData = FbaPrepBoxDetail::where(['fba_shipment_item_id' => $request->itemId,'fba_shipment_id' => $request->shipmentId,'box_number' => $request->boxNumber])->first();
                // $boxData = FbaPrepBoxDetail::where(['fba_shipment_item_id' => $request->itemId,'fba_shipment_id' => $request->shipmentId])
                //                         ->where(function($query) use($request){
                //                             $query->where('box_number', $request->boxNumber)
                //                                     ->orWhere('sku', 'like', $request->boxNumber.'%');
                //                         })
                //                         ->first();
                if(!empty($boxData))
                {
                    $boxData = $boxData->toArray();
                }
            }else{
                
                $boxData = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->shipmentId,'box_number' => $request->boxNumber])->first();
                // $boxData = FbaPrepBoxDetail::where('fba_shipment_id', $request->shipmentId)
                //                             ->where(function($query) use($request){
                //                                 $query->where('box_number', $request->boxNumber)
                //                                         ->orWhere('sku', 'like', $request->boxNumber.'%');
                //                             })
                //                             ->first();
                if(!empty($boxData))
                {
                    $boxData = $boxData->toArray();
                }
            }
             
            if($boxData)
            {
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'boxData' => $boxData,
                    'message' => 'Box Number searched successfully',
                ]);
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'itemCount' => '',
                    'message' => 'Something went wrong.',
                ]);
            }
        }
    }

    public function deleteAllBoxesData(Request $request)
    {
        if(isset($request->shipmentId) && !empty($request->shipmentId) && isset($request->itemId) && !empty($request->itemId))
        {
            $prepData = FbaPrepBoxDetail::select(DB::raw("SUM(units) as Qty"), DB::raw("COUNT(box_number) as totCount"))->where(['fba_shipment_id' => $request->shipmentId, 'fba_shipment_item_id' => $request->itemId])->first();

            if(!empty($prepData))
            {
                $prepData = $prepData->toArray();
            }

            $boxDetails = FbaPrepBoxDetail::where(['fba_shipment_id' => $request->shipmentId, 'fba_shipment_item_id' => $request->itemId])->delete();
            if(!empty($boxDetails))
            {
                $fbaShipmentItem = FbaShipmentItem::select('id','quantity_shipped','skus_prepped')->where('id', $request->itemId)->first();

                $updateProduct = FbaPrepDetail::where('fba_shipment_item_id',$request->itemId)
                                    ->update([
                                        'done_qty' => 0, 
                                        'discrepancy_qty' => $fbaShipmentItem->quantity_shipped, 
                                        'actual_done_qty' => 0
                                    ]);

                $fbaShipmentItem->skus_prepped = 0;
                $fbaShipmentItem->save();

                //Deleted All Box IDs log...
                $this->deletedAllBoxLog($request, $prepData);
                
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'message' => 'All Boxes deleted successfully',
                ]);
    
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
        }
    }

    //Deleted All Box IDs log...
    public function deletedAllBoxLog($request, $prepData)
    { 
        if(isset($request->shipmentId) && !empty($request->shipmentId) && isset($request->itemId) && !empty($request->itemId))
        {
            $user_name = auth()->user()->name;
            $title = 'All Box Labels deleted by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipmentId)->first();
            $data = [
                'fba_shipment_id' => $shipmentData->id,
                'fba_shipment_item_id' => $request->itemId,
                'type' => 2,
                'title' => $title,
                'field_type' => 'deleted_all_box_label',
                'description' => "<h6>All Box Labels deleted ( Count - ".$prepData['totCount']." Qty - ".$prepData['Qty']." ) </h6>",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            PrepLog::insert($data);
        }
    }

    public function updatePrepNotes(Request $request)
    {
        $result = '';
        if($request->type=='Discrepancy' && !empty($request->itemId))
        {
            $result = FbaPrepDetail::updateOrCreate([
                 'fba_shipment_item_id' => $request->itemId
            ],[
                'fba_shipment_id' => $request->shipmentId,
                'fba_shipment_item_id' => $request->itemId,
                'discrepancy_note' => $request->notes,
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);

            if($request->type=='Discrepancy')
            {
                $user_name = auth()->user()->name;
                $title = ' Discrepancy note updated by '.$user_name;
                $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipmentId)->first();
                $data = [
                    'fba_shipment_id' => $shipmentData->id,
                    'fba_shipment_item_id' => $request->itemId,
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'discrepancy_note',
                    'description' => "<h6>Updated discrepancy note </h6>( <span> " .$request->notes. " ) </span>",
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ];
                
                PrepLog::insert($data);
            }

        }else if($request->type=='Prep' && !empty($request->asin)){            
            $result = FbaPrepNote::updateOrCreate([
                'asin' => $request->asin
            ],[
                'asin' => $request->asin,
                'prep_note' => $request->notes,
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);
        }else if($request->type=='Warehouse' && !empty($request->asin)){
            
            $result = FbaPrepNote::updateOrCreate([
                'asin' => $request->asin
            ],[
                'asin' => $request->asin,
                'warehouse_note' => $request->notes,
                'module_name' => 'Prep',
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);
        }

        if($result)
        {
            if($request->type=='Prep' || $request->type=='Warehouse')
            {
                $user_name = auth()->user()->name;
                
                if($request->type=='Prep')
                {
                    $description = 'Update Prep Note for- ' . $request->asin . ' : ' . $request->notes . ' on ' . date("m-d-Y") . ' from Prep by ' . $user_name;
                    $title =  'Update Prep Note- ' . $request->asin . ' by ' . $user_name;
                }else{
                    $description = 'Update Warehouse Note for- ' . $request->asin . ' : ' . $request->notes . ' on ' . date("m-d-Y") . ' from Prep by ' . $user_name;
                    $title =  'Update Warehouse Note- ' . $request->asin . ' by ' . $user_name;
                }
            
                $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipmentId)->first();
                $data = [
                    'asin' => $request->asin,
                    'type' => $request->type,
                    'title' => $title,
                    'description' => $description,
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ];
                
                PrepNoteLog::insert($data);
            }

            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'message' => 'Notes updated successfully',
            ]);
        }else{
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong.',
            ]);
        }

    }

    public function printAllLabel(Request $request)
    {
        $shipmentItems = FbaShipmentItem::where('shipment_id', $request->shipmentId)
                        ->where('skus_prepped','!=',2)
                        ->with(['amazonData' => function($query){
                            return $query->select('fnsku','sku','title');
                        }])
                        ->get(['seller_sku','id','quantity_shipped']);

                        
        if($shipmentItems->count() > 0)
        {
            $prepItems = FbaPrepDetail::whereIn('fba_shipment_item_id', $shipmentItems->pluck('id'))->pluck('done_qty','fba_shipment_item_id');
            
            $fileData = [];
            $productDetails = [];
            $numberOfLabel = 0;
            foreach($shipmentItems as $item)
            {
                if(isset($prepItems[$item->id]) && $prepItems[$item->id] == $item->quantity_shipped)
                {
                    continue;
                }
                
                $labelDetails['fnsku'] = $item->amazonData->fnsku;
                $labelDetails['sku'] = $item->amazonData->sku;
                $fileData = $this->generateProductBarcodeImages($labelDetails);
                $productDetails[] = [
                    'fba_shipment_item_id' => $item->id,
                    'fnsku' => $item->amazonData->fnsku,
                    'title' => preg_replace('/[^A-Za-z0-9. -]/', '', $item->amazonData->title),
                    'product_condition' => 'New',
                    'filepath' => $fileData['filepath'],
                    'filename' => $fileData['filename'],
                    'number_of_label' => $item->quantity_shipped - (isset($prepItems[$item->id]) ? $prepItems[$item->id] : 0)
                ];
                $numberOfLabel += $item->quantity_shipped - (isset($prepItems[$item->id]) ? $prepItems[$item->id] : 0);
            }
        }

        $htmlData = [];

        foreach($productDetails as $productDetail)
        {
            for($i=0;$i<$productDetail['number_of_label'];$i++)
            {   
                $htmlData[] = [
                    'barcodeImage' => $productDetail['filename'],
                    'fnsku' => $productDetail['fnsku'],
                    'title_data' => $productDetail['title'],
                    'product_condition' => $productDetail['product_condition'],
                ];
            }
        }

        Session::put('productLabelHtml', $htmlData);
        
        $storagePath = Storage::disk('public')->url("uploads/pdf/Item_Labels.pdf");
        if(!empty($htmlData) && !empty($storagePath))
        {
            $user_name = auth()->user()->name;
            $title = 'Item label printed by '.$user_name;
            $shipmentData = FbaShipment::select('id')->where('shipment_id', $request->shipmentId)->first();
            $data = [];

            foreach($productDetails as $productDetail)
            {
                $data[] = [
                    'fba_shipment_id' => $shipmentData->id,
                    'fba_shipment_item_id' => $productDetail['fba_shipment_item_id'],
                    'type' => 2,
                    'title' => $title,
                    'field_type' => 'Item_labels',
                    'description' => "<h6>Item Label Printed ( Count - ". $productDetail['number_of_label'] ." )</h6>",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                
            }
            
            if(!empty($data))
            {
                Batch::insert(new PrepLog, array_keys($data[0]), $data, 50);
            }
            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'url' => $storagePath,
                'message' => 'Item Label generated successfully',
            ]);

        }else{
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong.',
            ]);
        }
    }

    public function printShipmentPalletLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required',
            'number_of_pallet' => 'required|integer|between:1,20',
        ])->validate();

        $file_name='';
        $pdf_url='';
        $html = '';
        if (Session::has('palletLabelHtml')) {
            $result = Session::forget('palletLabelHtml');
        }
        
        if (!Storage::exists('public/uploads/pdf/')) {
            Storage::makeDirectory('public/uploads/pdf/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/pdf/");

        $shipment = FbaShipment::where('id', $request->shipment_id)->first();
        $maxPalletLabel = ($request->number_of_pallet * 4);

        if (!empty($shipment)) {
            for ($i=1; $i <= $maxPalletLabel; $i++) { 
                $shipment_label_image_data = $this->generatePalletLabelBarcodeImage($shipment, $i);
                
                $shipment_twodlabel_image_data = $this->generatePalletLabelBarcodeImage($shipment, $i, 'twod');
                
                if(!empty($shipment_label_image_data['filename']) && !empty($shipment_twodlabel_image_data['filename']))
                {
                    $data['shipment_label_image']=$shipment_label_image_data['filename'];
                    $data['shipment_label_labelstring']=$shipment_label_image_data['labelstring'];
                    $data['shipment_twodlabel_image']=$shipment_twodlabel_image_data['filename'];

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

                    $product_title = null;
                    if (isset($request->product_title)) {
                        $product_title = $request->product_title;
                    } elseif (isset($boxDetail) && !empty($boxDetail)) {
                        $product_title = isset($amazProduct['title']) && !empty($amazProduct['title']) ? $amazProduct['title'] : '';
                    }

                    $data['shipmentData'] = [
                        'title' => $shipment->shipment_name,
                        'created_at' => Carbon::now(),
                        'bcode' => $shipment->shipment_name,
                        'destination_center_id' => $shipment->destination_fulfillment_center_id,
                        'amazon_shipment_id' => $shipment->shipment_id,
                        'product_condition' => 'New',
                        'truck_name' => !empty($shipment->getAssociatedTruck) ? $shipment->getAssociatedTruck->schedule_name : '',
                    ];
                    
                    $htmlData[] = $data;
                }
            }

            Session::put('palletLabelHtml', $htmlData);
            
            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'message' => 'Pallet label generated succesfully.'
            ]);
        }
        return response()->json([
            'type'   => 'error',
            'status' => 400,
        ]);
    }   

    public function generatePalletLabelBarcodeImage($shipment, $loopCount, $type=null)
    {
        $file_data = array();
        if (!Storage::exists('public/uploads/barcode/pallet_label/')) {
            Storage::makeDirectory('public/uploads/barcode/pallet_label/', 0777, true);
            print("storage");
        }
        $targetPath = storage_path("app/public/uploads/barcode/pallet_label/");
        
        $fba_shipment_id = $shipment->shipment_id;
        // $paddingData    = str_pad($loopCount,6,"0",STR_PAD_LEFT);
        $productData    = $fba_shipment_id;
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        
        if($type=='twod')
        { 
            $bobj = $barcode->getBarcodeObj('PDF417', "{$productData}", 170, 83, 'black', array(0,0,0,0))->setBackgroundColor('white');
        }
        else
        {
            $bobj = $barcode->getBarcodeObj('C128', "{$productData}", 260, 73, 'black', array(0,0,0,0))->setBackgroundColor('white');
        }
        
        $imageData = $bobj->getSvgCode();
        $timestamp = time();
        if($type=='twod')
        {
            $filename  = 'pallet_twod_label_'.$loopCount.'_'.$timestamp . '.svg';
        }
        else
        {
            $filename = 'pallet_label_'.$loopCount.'_'.$timestamp . '.svg';   
        }

        $filepath = $targetPath . $filename;                
        file_put_contents($filepath, $imageData);

        //prepare response data
        $file_data['filepath']=$filepath;
        $file_data['filename']=$filename;
        $file_data['labelstring']=$productData;
      
        return $file_data;
    }

    public function generatePalletLabelHtml(Request $request)
    {
        $htmlData = Session::get('palletLabelHtml');

        return view('fba_prep.partials.pallet_label_layout', compact('htmlData'));
    }
    // public function searchMultiSkus(Request $request)
    // {
    //     $shipmentProducts = FbaShipmentItem::select(
    //         'fba_shipment_items.id',
    //         'fba_shipment_items.seller_sku',
    //         'fba_prep_notes.prep_note',
    //         'fba_prep_details.done_qty',
    //         'quantity_shipped as qty',
    //         'original_quantity_shipped as orig_qty',
    //         'amazon_products.sku',
    //         'amazon_products.title',
    //         'amazon_products.main_image',
    //         'amazon_products.fnsku',
    //         'amazon_products.asin',
    //         'amazon_products.product_note',
    //     )
    //     ->where('quantity_shipped', '!=', 0)
    //     ->where('fba_shipment_items.shipment_id', $request->shipmentId)
    //     ->where(function($query) use($request){
    //         $query->where('amazon_products.sku', 'like', '%'.$request->searchSku.'%')
    //                 ->orWhere('amazon_products.fnsku', 'like', '%'.$request->searchSku.'%')
    //                 ->orWhere('amazon_products.asin', 'like', '%'.$request->searchSku.'%');
    //     })
    //     ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
    //     ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
    //     ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
    //     ->get();

    //     if($shipmentProducts->count() == 0)
    //     {
    //         return response()->json('No Products found', 422);
    //     }

    //     if($request->searchSku == '')
    //     {
    //         $allProducts = true;

    //         if($shipmentProducts->count() > 0)
    //         {
    //             $shipmentProducts = $shipmentProducts->reject(function ($shipmentProduct) {
    //                 return $shipmentProduct->qty == $shipmentProduct->done_qty;
    //             });
    //         }
    //         return view('fba_prep.multi_skus_box_body',compact('shipmentProducts','allProducts'));
    //     }
        
    //     return view('fba_prep.multi_skus_box_body',compact('shipmentProducts'));
    // }

    public function searchMultiSkus(Request $request)
    {
        // $shipmentProductsQuery = FbaShipmentItem::select(
        //     'fba_shipment_items.id',
        //     'fba_shipment_items.seller_sku',
        //     'fba_prep_notes.prep_note',
        //     'fba_prep_details.done_qty',
        //     'quantity_shipped as qty',
        //     'original_quantity_shipped as orig_qty',
        //     'amazon_products.sku',
        //     'amazon_products.title',
        //     'amazon_products.main_image',
        //     'amazon_products.fnsku',
        //     'amazon_products.asin',
        //     'amazon_products.product_note',
        // )
        // ->where('quantity_shipped', '!=', 0)
        // ->where('fba_shipment_items.shipment_id', $request->shipmentId)
        // ->when(request()->searchSku, function($query) use($request){
        //     $query->where('amazon_products.sku', $request->searchSku)
        //             ->orWhere('amazon_products.fnsku', $request->searchSku)
        //             ->orWhere('amazon_products.asin', $request->searchSku);
        // })
        // ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        // ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
        // ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin');

        if($request->searchSku == '')
        {
            $alreadyExistSku = MultiSkusBox::where([
                'shipment_id' => $request->shipmentId,
            ])->pluck('fba_shipment_item_id');

            $shipmentProducts = FbaShipmentItem::select(
                'fba_shipment_items.id',
                'fba_shipment_items.seller_sku',
                'fba_prep_notes.prep_note',
                'fba_prep_details.done_qty',
                'quantity_shipped as qty',
                'original_quantity_shipped as orig_qty',
                'amazon_products.sku',
                'amazon_products.title',
                'amazon_products.main_image',
                'amazon_products.fnsku',
                'amazon_products.asin',
                'amazon_products.product_note',
            )
            ->where('quantity_shipped', '!=', 0)
            ->where('fba_shipment_items.shipment_id', $request->shipmentId)
            ->whereNotIn('fba_shipment_items.id', $alreadyExistSku)
            // ->when(request()->searchSku, function($query) use($request){
            //     $query->where('amazon_products.sku', $request->searchSku)
            //             ->orWhere('amazon_products.fnsku', $request->searchSku)
            //             ->orWhere('amazon_products.asin', $request->searchSku);
            // })
            ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
            ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
            ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
            ->get();

            if($shipmentProducts->count() == 0)
            {
                return response()->json([
                    'title' => 'No Products found', 
                    'message' => 'No Products found'
                ]);
            }

            $allProducts = true;
            $shipmentProducts = $shipmentProducts->reject(function ($shipmentProduct) {
                return ($shipmentProduct->qty - $shipmentProduct->done_qty) <= 0;
            });

            if($shipmentProducts->count() == 0)
            {
                return response()->json([
                    'title' => 'No Products found', 
                    'message' => 'Prep is completed for all skus.'
                ]);
            }

            return view('fba_prep.multi_skus_box_body',compact('shipmentProducts','allProducts'));
        }

        $shipmentProducts = FbaShipmentItem::select(
            'fba_shipment_items.id',
            'fba_shipment_items.seller_sku',
            'quantity_shipped as qty',
            'fba_prep_details.done_qty',
        )
        ->where('quantity_shipped', '!=', 0)
        ->where('fba_shipment_items.shipment_id', $request->shipmentId)
        ->when(request()->searchSku, function($query) use($request){
            $query->where('amazon_products.sku', $request->searchSku)
                    ->orWhere('amazon_products.fnsku', $request->searchSku)
                    ->orWhere('amazon_products.asin', $request->searchSku);
        })
        ->leftJoin('amazon_products', 'amazon_products.sku', 'fba_shipment_items.seller_sku')
        ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
        ->first();
        
        if($shipmentProducts == null)
        {
            return response()->json([
                'title' => 'No Sku found',
                'message' => "No Sku found",
            ]);
        }

        if(($shipmentProducts->qty - $shipmentProducts->done_qty) <= 0)
        {
            return response()->json([
                'title' => 'Sku is already packed',
                'message' => "This sku's all units are packed",
            ]);
        }

        $isExistsProducts = MultiSkusBox::where([
            'shipment_id' => $request->shipmentId,
            'seller_sku' => $shipmentProducts->seller_sku,
        ])->first();

        if(!$isExistsProducts)
        {
            MultiSkusBox::create([
                'shipment_id' => $request->shipmentId,
                'fba_shipment_item_id' => $shipmentProducts->id,
                'seller_sku' => $shipmentProducts->seller_sku,
            ]);
        }

        $multiSkusData = MultiSkusBox::where('multi_skus_boxes.shipment_id', $request->shipmentId)
                            ->leftJoin('amazon_products', 'amazon_products.sku', 'multi_skus_boxes.seller_sku')
                            ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
                            ->leftJoin('fba_shipment_items', 'fba_shipment_items.id', 'multi_skus_boxes.fba_shipment_item_id')
                            ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
                            ->select('multi_skus_boxes.id', 'multi_skus_boxes.fba_shipment_item_id', 'multi_skus_boxes.sellable_units', 'amazon_products.sku', 'amazon_products.fnsku',
                                    'amazon_products.title', 'amazon_products.main_image','fba_prep_notes.prep_note','fba_shipment_items.quantity_shipped as qty','fba_prep_details.done_qty')
                            ->get();

        // return view('fba_prep.multi_skus_box_body',compact('shipmentProducts','multiSkusData'));
        return view('fba_prep.multi_skus_box_body',compact('multiSkusData'));
    }

    public function getMultiSkus($shipmentId)
    {
        $multiSkusData = MultiSkusBox::where('multi_skus_boxes.shipment_id', $shipmentId)
                            ->leftJoin('amazon_products', 'amazon_products.sku', 'multi_skus_boxes.seller_sku')
                            ->leftJoin('fba_prep_notes', 'fba_prep_notes.asin', 'amazon_products.asin')
                            ->leftJoin('fba_shipment_items', 'fba_shipment_items.id', 'multi_skus_boxes.fba_shipment_item_id')
                            ->leftJoin('fba_prep_details', 'fba_prep_details.fba_shipment_item_id', 'fba_shipment_items.id')
                            ->select('multi_skus_boxes.id', 'multi_skus_boxes.fba_shipment_item_id', 'multi_skus_boxes.sellable_units', 'amazon_products.sku', 'amazon_products.fnsku',
                                    'amazon_products.title', 'amazon_products.main_image','fba_prep_notes.prep_note','fba_shipment_items.quantity_shipped as qty','fba_prep_details.done_qty')
                            ->latest('multi_skus_boxes.created_at')
                            ->get();

        return view('fba_prep.multi_skus_box_body',compact('multiSkusData'));
    }

    public function deleteSku($skuId)
    {
        $skuQuery = MultiSkusBox::where('id', $skuId);
        $shipmentId = $skuQuery->value("shipment_id");
        $skuQuery->delete();
        
        return $this->getMultiSkus($shipmentId);
    }

    public function addMultiSkus(Request $request)
    {
        $sellerSkus = [];
        if(!empty($request->sellerSkus))
        {
            foreach($request->sellerSkus as $sku)
            {
                $sellerSkus[] = [
                    'shipment_id' => $request->shipmentId,
                    'fba_shipment_item_id' => $sku['shipment_item_id'],
                    'seller_sku' => $sku['seller_sku'],
                    'created_at' => now()
                ];
            }
        }
        
        MultiSkusBox::insert($sellerSkus);

        return $this->getMultiSkus($request->shipmentId);
    }

    public function updateSkuUnit($skuId, Request $request)
    {
        $skuQuery = MultiSkusBox::where('id', $skuId);
        $shipmentId = $skuQuery->value("shipment_id");
        $skuQuery->update(['sellable_units' => $request->sellableUnits]);
        return $this->getMultiSkus($shipmentId);
    }

    public function createMultiSkusBox(Request $request, PrepService $prepService)
    {
        $printBoxIdsArr = $prepService->getAddAllBoxes($request);
        // dd($printBoxIdsArr);
        // $printBoxIdsArr = [27, 28, 29];

        if($printBoxIdsArr==false)
        {
            return response()->json([
                'type'   => 'error',
                'status' => 400,
                'message' => 'Something went wrong.',
            ]);
        }
            
        $pdf_url = $prepService->commonGenerateBoxLabelsPdf($request, $printBoxIdsArr); 
        if(!empty($pdf_url))
        {
            $storagePath = Storage::disk('public')->url("uploads/pdf/Box_Labels.pdf");
            return response()->json([
                'type'   => 'success',
                'status' => 200,
                'url' => $storagePath,
                'message' => 'Box Label generated successfully',
            ]);

            if(!empty($pdf_url) && !empty($storagePath))
            {
                $this->printBoxLabelLog($request);
                
                if(!empty($request->asin_weight) && empty($request->old_asin_weight))
                {
                    //first time added asin weight...
                    $this->addedAsinLog($request, 'Added');
                }

                if(!empty($request->asin_weight) && !empty($request->old_asin_weight) && $request->asin_weight != $request->old_asin_weight){
                    //updated asin weight time update log...
                    $this->addedAsinLog($request, 'Updated');
                }
                
                return response()->json([
                    'type'   => 'success',
                    'status' => 200,
                    'url' => $storagePath,
                    'message' => 'Box Label generated successfully',
                ]);
    
            }else{
                return response()->json([
                    'type'   => 'error',
                    'status' => 400,
                    'message' => 'Something went wrong.',
                ]);
            }
        }
        
    }

    public function generateMultiSkusBoxLabelHtml(Request $request)
    {
        $htmlData = Session::get('multiSkuLabelHtml');
        return view('fba_prep.partials.multi_skus_box_label_layout', compact('htmlData'));
    }

    public function exportPrepAsXls($id, PrepService $prepService)
    {
        $prepDetails = $prepService->exportPrepDetails($id);

        if(empty($prepDetails))
        {
            $fbaShipment = FbaShipment::find($id);
            $file_name = $fbaShipment->shipment_id.'.xls';
            // return back();
        }else{
            $file_name = $prepDetails[0]['Shipment ID'].'.xls';
        }
        
        $headerColumns = array('PO Number', 'Shipment ID', 'Invoice Number',  'Discrepency Cases', 'Product Title', 'Case Pack', 'Case Price', 'SKU', 'Extended Price', 'Discrepancy Note');

        return Excel::download(new ExcelExport($prepDetails, $headerColumns), $file_name);
    }

    public function exportPrepAsCSV($id, PrepService $prepService)
    {
        $prepDetails = $prepService->exportPrepDetails($id, 'csv');
        
        if(empty($prepDetails))
        {
            $fbaShipment = FbaShipment::find($id);
            $file_name = $fbaShipment->shipment_id;
            // return back();
        }else{
            $file_name = $prepDetails->first()->shipment_id;
        }

        // $file_name = $prepDetails->first()->shipment_id;

        // $fbaShipment = FbaShipment::find($id);

        // $file_name = 'file';
        // if ($fbaShipment)
        // {
        //     $file_name = $fbaShipment->shipment_id;
        // }

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=" . $file_name . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columns = ['PO Number', 'Shipment ID', 'Invoice Number', 'Discrepency Cases', 'Product Title', 'Case Pack', 'Case Price', 'SKU', 'Extended Price', 'Discrepancy Note'];

        $callback = function () use ($prepDetails, $columns)
        {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($prepDetails as $item)
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

                $dataArr = array(
                    'PO Number' => !empty($item->po_number) ? $item->po_number : '-',
                    'Shipment ID' => !empty($item->shipment_id) ? $item->shipment_id : '-',
                    'Invoice Number' => $invoiceNums,
                    'Discrepency Cases' => !empty($caseNeeded) ? abs($caseNeeded) : '-',
                    'Product Title' => !empty($item->amazonData) ? $item->amazonData->title : '-',
                    'Case Pack' => !empty($item->case_pack) ? $item->case_pack : '-',
                    'Case Price' => !empty($item->case_price) ? $item->case_price : '-',
                    'SKU' => $item->seller_sku,
                    'Extended Price' => !empty($extendedPrice) ? $extendedPrice : '-',
                    'Discrepancy Note' => $item->discrepancy_note,
                );

                fputcsv($file, $dataArr);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function checkShipmentDiscrepancy(Request $request)
    {
        $shipmentItems = FbaShipmentItem::getCompleteShipmentPrepData($request->shipmentId);

        if (count($shipmentItems) > 0)
        {
            $allDiscrepancyQty = $shipmentItems->filter(function($item) { 
                return $item->all_discrepancy_qty != 0 ? $item->all_discrepancy_qty : ''; 
            });

            if (count($allDiscrepancyQty) != 0)
            {
                $fbaShipment = FbaShipment::select('id', 'shipment_id', 'shipment_status')->where('id', $request->shipmentId)->first();
                $data = View::make('fba_prep.partials.complete_prep_modal', ['shipmentItems' => $shipmentItems, 'shipment' => $fbaShipment])->render();
                $response = $this->sendResponse('Success', 200, $data);
            } else {
                $response = $this->updateShipmentCompletePrepStatus($request, 'no_discrepancy_qty');
            }
        } elseif (!empty($request->shipmentId)) {
            $fbaShipmentItems = FbaShipmentItem::getNoDiscrepancyPrepData($request->shipmentId);

            if ($fbaShipmentItems > 0)
            {
                $response = $this->updateShipmentCompletePrepStatus($request, 'no_discrepancy_qty');
            } else {
                $response = $this->sendError(Lang::get('messages.something_went_wrong'), 400); 
            }
        } else {
            $response = $this->sendError(Lang::get('messages.something_went_wrong'), 400);   
        }

        return $response;
    }

    public function updateShipmentCompletePrepStatus(Request $request, $actionFrom=null)
    {
        $prepService = new PrepService();
        
        // $fbaShipment = FbaShipment::with('fbaShipmentItems')->where('id', $request->shipmentId)->where('prep_status', 1)->first();
        $fbaShipment = FbaShipment::where('id', $request->shipmentId)->where('prep_status', 1)->first();
        
        if (!empty($fbaShipment))
        {
            if (!empty($actionFrom) && $actionFrom == 'no_discrepancy_qty')
            {
                $fbaShipment->prep_status = 2;
                $fbaShipment->save();

                $fbaShipment->fbaShipmentItems()->update(['skus_prepped' => 2]);

                $desc = "<h6>Prep Completed Without discrepancy</h6>";
                $keyword = "Completed";

                $user_name = auth()->user()->name;
                $title = 'Prep '.$keyword.' by '.$user_name;

                // FbaShipmentLog::create([
                //     'fba_shipment_id' => $fbaShipment->id,
                //     'type' => 1,
                //     'action_from' => 2,
                //     'title' => $title,
                //     'description' => '<div class="row">'.$desc.'</div>',
                //     'user_id' => auth()->user()->id,
                // ]);
              
                PrepLog::create([
                    'fba_shipment_id' => $fbaShipment->id,
                    'type' => 1,
                    'title' => $title,
                    'description' => $desc
                ]);

                $response = [
                    'type'   => 'success',
                    'status' => 200,
                    'message' => 'Prep completed successfully.'
                ];

            } else {
                try {
                    if ($fbaShipment->shipment_status == 0)
                    {
                        // $fbaShipmentItems = FbaShipmentItem::where('fba_shipment_id', $fbaShipment->id)->get();
                        $fbaShipmentItems = FbaShipmentItem::with(['fbaPrepDetail' => function($query){
                            return $query->select('id','fba_shipment_item_id','done_qty','discrepancy_qty');
                        }])->where('fba_shipment_id', $fbaShipment->id)->where('quantity_shipped','!=',0)->get();

                        $updatedQtyArr = $result = [];

                        if (count($fbaShipmentItems) > 0)
                        {
                            foreach($fbaShipmentItems as $fbaShipmentItem)
                            {
                                // $FbaPrepDetail = FbaPrepDetail::select('id','fba_shipment_item_id','done_qty','discrepancy_qty')->where('fba_shipment_item_id', $fbaShipmentItem->id)->first();

                                // if (!empty($FbaPrepDetail))
                                if(!empty($fbaShipmentItem->fbaPrepDetail))
                                {
                                    $allowedUpdatedQty = $prepService::actualQtyShippedCalculate($fbaShipmentItem->original_quantity_shipped, $fbaShipmentItem->fbaPrepDetail->done_qty);
                                    // $fbaShipmentItem->quantity_shipped = $allowedUpdatedQty;
                                    // $fbaShipmentItem->fbaPrepDetail->done_qty = $allowedUpdatedQty;
                                } else {
                                    $allowedUpdatedQty = $prepService::actualQtyShippedCalculate($fbaShipmentItem->original_quantity_shipped, 0);
                                    // $fbaShipmentItem->quantity_shipped = $allowedUpdatedQty;
                                }

                                // if (!empty($fbaShipmentItem->fbaPrepDetail) && ($fbaShipmentItem->original_quantity_shipped != $fbaShipmentItem->fbaPrepDetail->done_qty))
                                // {
                                //     $updatedQtyArr[] = [
                                //         'id' => $fbaShipmentItem->id,
                                //         'shipment_id' => $fbaShipmentItem->shipment_id,
                                //         'sellerSku' => $fbaShipmentItem->seller_sku,
                                //         'fnSku' => $fbaShipmentItem->fulfillment_network_sku,
                                //         'new_update_quantity' => $allowedUpdatedQty,
                                //         'done_qty' => !empty($fbaShipmentItem->fbaPrepDetail->done_qty) ? $fbaShipmentItem->fbaPrepDetail->done_qty : 0
                                //     ];
                                // }

                                if (empty($fbaShipmentItem->fbaPrepDetail) || ($fbaShipmentItem->original_quantity_shipped != $fbaShipmentItem->fbaPrepDetail->done_qty))
                                {
                                    $updatedQtyArr[] = [
                                        'id' => $fbaShipmentItem->id,
                                        'shipment_id' => $fbaShipmentItem->shipment_id,
                                        'sellerSku' => $fbaShipmentItem->seller_sku,
                                        'fnSku' => $fbaShipmentItem->fulfillment_network_sku,
                                        'new_update_quantity' => $allowedUpdatedQty,
                                        'done_qty' => (!empty($fbaShipmentItem->fbaPrepDetail) && !empty($fbaShipmentItem->fbaPrepDetail->done_qty)) ? $fbaShipmentItem->fbaPrepDetail->done_qty : $allowedUpdatedQty
                                    ];
                                }
                            }

                            // dd($updatedQtyArr);

                            // $result = ['status' => 'success', 'message' => 'Shipment updated successfully.'];
                            if (!empty($updatedQtyArr))
                            {
                                $result = $prepService->invokeUpdateShipmentApi($fbaShipment->id, $updatedQtyArr, 'completePrep', '', $fbaShipment->store_id);
                            }

                            // dd($result);

                            if (isset($result['status']) && $result['status'] == 'success')
                            {
                                $response = [
                                    'type'   => 'success',
                                    'status' => 200,
                                    'message' => $result['message']
                                ];

                            } elseif (isset($result['message']) && !empty($result['message'])) {

                                $error = isset($result['message']['errors']) ? $result['message']['errors'][0] : $result;

                                $message = isset($error['message']) && !empty($error['message']) ? $error['message'] : '';

                                $response = [
                                    'type'   => 'error',
                                    'status' => 400,
                                    'message' => $message,
                                ];
                            } else {

                                $response = [
                                    'type'   => 'error',
                                    'status' => 400,
                                    'message' => 'Something went wrong',
                                ];
                            }
                        }
                    } else {
                        $fbaShipment->prep_status = 2;
                        $fbaShipment->save();

                        $desc = "<h6>Prep Completed</h6>";
                        $keyword = "Completed";

                        $user_name = auth()->user()->name;
                        $title = 'Prep '.$keyword.' by '.$user_name;
                      
                        PrepLog::create([
                            'fba_shipment_id' => $fbaShipment->id,
                            'type' => 1,
                            'title' => $title,
                            'description' => $desc
                        ]);

                        $response = [
                            'type'   => 'success',
                            'status' => 200,
                            'message' => 'Prep completed successfully.'
                        ];
                    }
                } catch (\Exception $e) {
                    $response = [
                        'type'   => 'error',
                        'status' => 400,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } else {
            $response = [
                'type'   => 'error',
                'status' => 400,
                'message' => 'Record not found!',
            ];
        }
        
        return $response;
    }

    public function generateMultiSkuBoxLabels(Request $request, PrepService $prepService)
    {
        $boxId = [$request->boxRowId];
        $pdf_url = $prepService->generateBoxLabelsPdf($boxId);

        if(!empty($pdf_url))
        {
           $storagePath = Storage::disk('public')->url("uploads/pdf/Box_Labels.pdf");
           if(!empty($pdf_url) && !empty($storagePath))
           {
                //Generate reprint single box label log...
                // $this->reprintSingleBoxLabelLog($request);

                return response()->json([
                   'type'   => 'success',
                   'status' => 200,
                   'url' => $storagePath,
                   'message' => 'Box Label generated successfully',
                ]);
           }else{
                return response()->json([
                   'type'   => 'error',
                   'status' => 400,
                   'message' => 'Something went wrong.',
                ]);
           }
        }
    }

    public function generateBoxLabelsHtml(Request $request)
    {
        $htmlData = Session::get('multiSkuLabelHtml');
        $allRegularBoxData = Session::get('boxLabelHtml');
        return view('fba_prep.partials.multi_skus_box_label_layout', compact('htmlData','allRegularBoxData'));
    }
}
