<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use App\Models\Setting;

class PDFController extends Controller
{
    public function generatePDF(int $id)
    {
        $poDetail = PurchaseOrder::whereId($id)
                                    ->with(['purchaseOrderItems' => function($query){
                                        return $query->with(['product' => function($product){
                                                        return $product->select('id','sku','title');            
                                                    },'supplierProduct' => function($supplierProduct){
                                                        return $supplierProduct->select('id','supplier_sku')->withTrashed();
                                                    }])
                                                    ->select('id','po_id','supplier_product_id','product_id','order_qty','unit_price','total_price');
                                    }])
                                    ->select('id','po_number','po_order_date')
                                    ->first();

        $companyDetail = Setting::select('shipping_address','company_address','company_email','company_phone','warehouse_address')->first();
        
        if($poDetail)
        {
            $pdf = PDF::loadView('purchase_order_pdf', ['poDetail' => $poDetail, 'companyDetail' => $companyDetail]);
            return $pdf->download($poDetail->po_number.'_'.Carbon::now().'.pdf');
        }
    }
}
