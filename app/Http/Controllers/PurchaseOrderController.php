<?php

namespace App\Http\Controllers;

use App\DataTables\EditPurchaseOrderDataTable;
use App\DataTables\PurchaseOrderDataTable;
use App\Exports\POExport;
use App\Helpers\CommonHelper;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Requests\ShippingDetailRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Services\CommonService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Storage;
use App\Http\Requests\POEmailRequest;
use Mail;
use App\Mail\POEmail;
use App\Models\Setting;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PurchaseOrderDataTable $dataTable)
    {
        $suppliers = Supplier::where('status', '1')->whereNULL('deleted_at')->pluck('name', 'id')->toArray();
        // $statusArr = CommonHelper::getPOStatusList();
        $statusArr = [];
        if (config('constants.po_status')) {
            foreach (config('constants.po_status') as $key => $value) {
                $statusArr[$key] = $key;
            }
        }
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('purchase_orders.list', compact(['listingCols', 'statusArr', 'suppliers']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::select('id', 'name')->where('status', '1')->whereNULL('deleted_at')->get();

        $poStatusList = CommonHelper::getPOStatusList();

        return view('purchase_orders.create', compact('suppliers', 'poStatusList'));
    }

    /**
     * get Supplier contact info
     */
    public function getSupplierContactInfo(Request $request)
    {
        if ($request->ajax()) {
            try {
                $contactData = [];
                $contactData = SupplierContact::select('id', 'name')->whereNULL('deleted_at')->where('supplier_id', $request->supplier_id)->get();
                $response = [
                    'type' => 'success',
                    'status' => 200,
                    'contactData' => $contactData,
                ];
                return response()->json($response);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        } else {
            return $this->sendError('Bad Request', 400);
        }
    }

    /**
     * Save purchase of the resource.
     */
    public function store(PurchaseOrderRequest $request)
    {
        return self::save($request);
    }

    /**
     * Save purchase order first tab data.
     */
    public function save(Request $request)
    {
        if ($request->purchase_order_id == 0) {

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'supplier_contact_id' => $request->supplier_contact_id,
                'po_number' => $request->po_number,
                'po_order_date' => $request->po_order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => $request->status,
                'created_by' => auth()->user()->id,
                'created_at' => Carbon::now(),
            ]);

            $response = [
                'type' => 'success',
                'created_purchase_order_id' => $purchaseOrder->id,
                'status' => 200,
                'message' => 'Purchase Order added successfully',
            ];
        }
        return response()->json($response);
    }

    /**
     * Display a edit page of purchase order
     *
     */
    public function edit($po_id, EditPurchaseOrderDataTable $dataTable)
    {
        if (!empty($po_id)) {
            $poDetails = PurchaseOrder::find($po_id);
            return $dataTable->with('po_id', $po_id)->render('purchase_orders.edit', compact('poDetails'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseOrderRequest $request, string $id)
    {
        $type = $request->updateFor;

        if ($type == 'update_expected_delivery_date') {
            try {
                PurchaseOrder::where('id', $id)->update([
                    "expected_delivery_date" => Carbon::createFromFormat('d-m-Y', $request->selected_date)->format('Y-m-d'),
                    "updated_by" => auth()->user()->id,
                    "updated_at" => Carbon::now(),
                ]);
                return $this->sendResponse('Expected delivery date updated successfully', 200);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }

        } elseif ($type == 'update_order_note') {
            try {
                PurchaseOrder::where('id', $id)->update([
                    "order_note" => $request->order_note,
                    "updated_by" => auth()->user()->id,
                    "updated_at" => Carbon::now(),
                ]);
                return $this->sendResponse('Order Note updated successfully', 200);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            PurchaseOrder::where('id', $id)->update([
                'status' => 'cancelled',
            ]);

            return $this->sendResponse('Purchase Order cancelled successfully', 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(Request $request, string $id)
    {
        $poDetails = PurchaseOrder::find($id);
        $purchaseOrder = PurchaseOrder::getPurchaseOrderIteams($id);
        return view('purchase_orders.view', compact('purchaseOrder', 'poDetails'));
    }

    public function columnVisibility(Request $request, PurchaseOrderDataTable $dataTable)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.purchase_order'));
        if (isset($response['status']) && $response['status'] == true) {
            return $this->sendResponse('Listing columns updated or created successfully.', 200);
        } else {
            return $this->sendValidation($response['message'], 400);
        }
    }

    public function updatePOStatus(ShippingDetailRequest $request)
    {
        $id = $request->input('po_id');

        if (isset($request->shipping_date)) {
            $updateData = [
                'shipping_company' => $request->input('shipping_company'),
                'shipping_id' => $request->input('shipping_id'),
                'shipping_date' => Carbon::createFromFormat('m-d-Y', $request->input('shipping_date'))->format('Y-m-d'),
                'status' => 'Shipped',
            ];
        } else {
            $status = $request->input('po_status');
            $updateData = [
                'status' => $status,
            ];
        }
        PurchaseOrder::where('id', $id)->update($updateData);

        if ($request->ajax()) {
            // Return a response indicating success or failure
            return response()->json(['message' => 'PO status updated successfully']);
        }

        return redirect()->route('purchase_orders.index');
    }

    /**
     * Download the Purchase Order as excel file
     */
    public function exportPOExcel(Request $request)
    {
        $po_id = $request->po_id;
        $po_name = PurchaseOrder::find($po_id)->po_number;
        return Excel::download(new POExport($po_id), $po_name . '.xlsx');
    }

    /**
     * Send email to supplier
     */
    public function sendPOEmail(Request $request)
    {
        $poId = $request->poId;
        $pdfGenerateUrl = $request->pdfGenerateUrl;
        $supplierId = PurchaseOrder::find($poId)->supplier_id;

        $defaultContactName = SupplierContact::where('supplier_id', $supplierId)->where('is_default', "1")->first();
        if(!empty($defaultContactName)){
            $supplierName = $defaultContactName->name;
        }else{
            $supplierData = Supplier::where('id', $supplierId)->withTrashed()->select('name')->first();
            $supplierName = $supplierData->name;
        }
        if (!empty($poId)) {
            $pdf = $this->generatePDF($poId);

            if ($pdf) {
                try{
                    PurchaseOrder::where('id', $poId)->update([
                        'pdf_filepath' => $pdf,
                        'updated_at' => Carbon::now(),
                        'updated_by' => auth()->user()->id,
                    ]);

                    $response = [
                        'type' => 'success',
                        'status' => 200,
                        'filePath' => $pdf,
                        'poId' => $poId,
                        'pdfGenerateUrl' => $pdfGenerateUrl,
                        'supplierName' => $supplierName,
                    ];
                    return $response;
                }catch(\Exception $e){
                    return $this->sendError($e->getMessage(), 500);
                }
            }
        }
    }

    /**
     * generate PDF of purchase order
     */
    public function generatePDF(int $id)
    {
        $poDetail = PurchaseOrder::whereId($id)
            ->with(['purchaseOrderItems' => function ($query) {
                return $query->with(['product' => function ($product) {
                    return $product->select('id', 'sku', 'title');
                }, 'supplierProduct' => function ($supplierProduct) {
                    return $supplierProduct->select('id', 'supplier_sku', 'unit_price')->withTrashed();
                }])
                    ->select('id','po_id','supplier_product_id','product_id','order_qty','unit_price','total_price');

            }])
            ->select('id', 'po_number', 'po_order_date')
            ->first();

        $companyDetail = Setting::select('shipping_address','company_address','company_email','company_phone','warehouse_address')->first();

            if ($poDetail) {
            try {
                $po_name = PurchaseOrder::find($id)->po_number;

                $pdf = PDF::loadView('purchase_order_pdf', ['poDetail' => $poDetail, 'companyDetail' => $companyDetail]);

                $filePath = '/po_documents/' . $id;

                if (!Storage::exists($filePath)) {
                    Storage::makeDirectory($filePath, 0777, true); //creates directory
                }
                $originalname = $po_name . '_' . Carbon::now() . '.pdf';
                $filePath = $filePath . '/' . $originalname;

                Storage::disk('public')->put($filePath, $pdf->output());

                return $originalname;
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), 500);
            }
        }
    }

    public function submitEmailPO(POEmailRequest $request){
        $attachment = PurchaseOrder::find($request->po_id)->pdf_filepath;
        // $supplierName = Supplier::whereId(PurchaseOrder::find($request->po_id)->supplier_id)->first()->name;

        $mailData = [
            'subject' => $request->subject,
            'message' => $request->message,
            'attachment' => '/po_documents/' . $request->po_id . '/' . $attachment,
        ];

        try{
            Mail::to($request->to)->send(new POEmail($mailData));
            return $this->sendResponse('Email sent successfully', 200);
        }catch(\Exception $e){
            return $this->sendError($e->getMessage(), 500);
        }

    }

    public function getShippingInfo($poId)
    {
        $shippingInfo = PurchaseOrder::whereId($poId)->select('shipping_date','shipping_company','shipping_id')->first();

        if($shippingInfo)
        {
            $shippingInfo->shipping_date = Carbon::parse($shippingInfo->shipping_date)->format('d-m-Y');
        }
        
        return response()->json(['status' => true, 'data' => $shippingInfo]);
    }
}
