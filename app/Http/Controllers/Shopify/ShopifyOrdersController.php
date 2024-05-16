<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Shopify\ShopifyOrdersDatatable;
use App\Services\CommonService;
use App\Models\ShopifyOrder;
use DataTables;
use App\DataTables\Shopify\ViewOrderDatatable;
use App\Models\ShopifyOrderItem;

class ShopifyOrdersController extends Controller
{
    public function index(ShopifyOrdersDatatable $dataTable)
    {
        $listingCols = $dataTable->listingColumns();
        $orderStatus = config('constants.shopify_order_status');
        return $dataTable->render('shopify.orders.list',compact('listingCols', 'orderStatus'));
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.shopify_orders'));
        if(isset($response['status']) && $response['status'] == true){
            
            return $this->sendResponse('Listing columns updated or created successfully.',200);
        }else{
            return $this->sendValidation($response['message'],400);
        }
    }

    public function saveOrderNote(Request $request){
        $orderNote = $request->orderNote;
        $orderId = $request->orderID;
        try{
            ShopifyOrder::where('id', $orderId)->update(['order_note' => $orderNote]);
        }catch(\Exception $e){
            return $this->sendError($e->getMessage(),400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, ViewOrderDatatable $dataTable)
    {
        if($id){
            try{
                $orders = ShopifyOrder::where('id', $id)->with('orderDetails')->first();

                $totalQty = ShopifyOrderItem::where('shopify_order_id' , $id)->sum('quantity');
                return $dataTable->with(['id' => $id])->render('shopify.orders.view',compact('orders', 'totalQty'));
            }catch(\Exception $e){
                return $this->sendError($e->getMessage(),400);
            }
        }
    }
}
