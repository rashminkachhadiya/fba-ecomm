<?php

namespace App\Http\Controllers;

use App\DataTables\OrderDataTable;
use App\Models\Store;
use App\Services\CommonService;
use Illuminate\Http\Request;

class AmazonOrderReportController extends Controller
{
    public function list(OrderDataTable $dataTable)
    {
        $listingCols = $dataTable->listingColumns();
        $orderStatus = array_keys(config('constants.order_status_color'));
        $fulfillmentChannel = config('constants.fulfillment_channel');
        $stores = Store::active()->pluck('store_name','id')->toArray();
        return $dataTable->render('order_reports.list', compact(['orderStatus','listingCols','fulfillmentChannel','stores']));
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.order_report'));
        if(isset($response['status']) && $response['status'] == true){
            
            return $this->sendResponse('Listing columns updated or created successfully.',200);
        }else{
            return $this->sendValidation($response['message'],400);
        }
    }
}
