<?php

namespace App\Http\Controllers;

use App\DataTables\POReceivedDataTable;
use App\Http\Requests\DiscrepancyRequest;
use App\Models\PoDiscrepancy;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POReceivedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(POReceivedDataTable $dataTable, Request $request)
    {
        $poName = PurchaseOrder::whereId($request->poId)->value('po_number');
        return $dataTable->render('po_receiving.list', compact('poName'));
    }

    /**
     * Update the PO items receiving details
     */
    public function update($id, Request $request, CommonService $commonService)
    {
        $poItemId = $request->poItemId;
        // $decryptId = Crypt::decrypt($id);
        // $getPO = PurchaseOrderItem::findOrFail($decryptId);
        DB::beginTransaction();
        try {
            $poItem = PurchaseOrderItem::where('id', $poItemId)->select('id','po_id','product_id','received_qty', 'difference_qty')->first();
            $productId = $poItem->product_id;
            $totalReceivedQty = $poItem->received_qty + $request->receivedQty;
            $diffQty = $poItem->difference_qty - $request->receivedQty;
            $poItem->update([
                'unit_price' => $request->unitPrice,
                // 'order_qty' => $request->orderQty,
                'total_price' => $request->totalPrice,
                'received_qty' => $totalReceivedQty,
                'received_price' => $totalReceivedQty * $request->unitPrice,
                'difference_qty' => $diffQty,
                'difference_price' => $diffQty * $request->unitPrice,
                'updated_at' => Carbon::now(),
            ]);

            $amazonProduct = $commonService->getAmazonProduct($productId);

            if($amazonProduct)
            {
                $productDetail = [
                    'id' => $productId,
                    'previous_qty' => $amazonProduct->wh_qty,
                    'updated_qty' => $amazonProduct->wh_qty + $request->receivedQty,
                    'pack_of' => $amazonProduct->pack_of
                ];
                $poName = $poItem->purchaseOrder->po_number;
                $commonService->storeQtyLog($productDetail, "Purchase order($poName) received qty detail updated");
                DB::commit();
                return $this->sendResponse('PO item updated successfully', 200);
            }
            return $this->sendError('Something went wrong');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->sendError($ex->getMessage(), 500);
        }

        // if($getPO)
        // {
        //     $getPO->update([
        //         'unit_price' => $request->unitPrice,
        //         'order_qty' => $request->orderQty,
        //         'total_price' => $request->totalPrice,
        //         'received_qty' => $request->receivedQty,
        //         'received_price' => $request->receivedPrice,
        //         'difference_qty' => $request->differenceQty,
        //         'difference_price' => $request->differencePrice,
        //     ]);
        //     return true;
        // }

        // return false;
    }

    public function addDiscrepancy(DiscrepancyRequest $request)
    {
        try {
            PoDiscrepancy::create([
                'reason' => $request->reason,
                'discrepancy_count' => $request->discrepancy_count,
                'discrepancy_note' => $request->discrepancy_note,
                'po_item_id' => $request->po_item_id,
                'po_id' => $request->po_id,
            ]);

            $this->updateDifferenceQty($request->po_item_id);

            $response = [
                'success' => true,
                'message' => 'Discrepancy added successfully',
                'po_id' => $request->po_id,
                'po_item_id' => $request->po_item_id,
                'po_discrepancy_id' => $request->poDiscrepancyId,
            ];
            return response()->json($response);
            // return $this->sendResponse('Discrepancy added successfully', 200);
        } catch (\Exception $ex) {
            return $this->sendError($ex->getMessage(), 500);
        }
    }

    public function editDiscrepancy(Request $request)
    {
        $poItemId = $request->itemId;
        $poId = $request->poId;

        try {
            $poDescrepancy = PoDiscrepancy::where('po_item_id', $poItemId)->where('po_id', $poId)->get();
            return response()->json($poDescrepancy);
        } catch (\Exception $ex) {
            return $this->sendError($ex->getMessage(), 500);
        }
    }

    public function deleteDiscrepancy(Request $request)
    {
        $poId = $request->po_id;
        $poItemId = $request->po_item_id;
        try {

            PoDiscrepancy::where('id', $request->poDiscrepancyId)->delete();

            $this->updateDifferenceQty($poItemId);

            $response = [
                'success' => true,
                'message' => 'Discrepancy deleted successfully',
                'po_id' => $poId,
                'po_item_id' => $poItemId,
                'po_discrepancy_id' => $request->poDiscrepancyId,
            ];
            return response()->json($response);
        } catch (\Exception $ex) {
            return $this->sendError($ex->getMessage(), 500);
        }

    }

    public function updateDiscrepancy(Request $request)
    {
        $type = $request->updateType;
        foreach($request->discrepancy_count as $key=>$value){
              $poItemId = PoDiscrepancy::where('id', $request->discrepancy_id[$key])->value('po_item_id');
            
            PoDiscrepancy::where('id', $request->discrepancy_id[$key])->update([
                'discrepancy_count' => $value,
                'discrepancy_note' => $request->discrepancy_note[$key],
                'reason' => $request->reason[$key],
                'updated_at' => Carbon::now(),
            ]);
            $this->updateDifferenceQty($poItemId);
          }
          return response()->json(['message' =>'success'],200);

        // if ($type == 'discrepancy_count') {
        //     PoDiscrepancy::where('id', $request->desc_id)->update([
        //         'discrepancy_count' => $request->discrepancy_count,
        //         'updated_at' => Carbon::now(),
        //         'updated_by' => auth()->user()->id,
        //     ]);


        //     return true;
        // } elseif ($type == 'discrepancy_note') {
        //     PoDiscrepancy::where('id', $request->desc_id)->update([
        //         'discrepancy_note' => $request->discrepancy_note,
        //         'updated_at' => Carbon::now(),
        //         'updated_by' => auth()->user()->id,
        //     ]);
        // } elseif ($type == 'discrepancy_reason') {
        //     PoDiscrepancy::where('id', $request->desc_id)->update([
        //         'reason' => $request->discrepancy_reason,
        //         'updated_at' => Carbon::now(),
        //         'updated_by' => auth()->user()->id,
        //     ]);
        // }
    }

    public function updateDifferenceQty($poItemId)
    {
        $itemData = PurchaseOrderItem::where('id', $poItemId)->select('unit_price', 'difference_qty', 'order_qty', 'received_qty')->first();
        $sumOfDiscQty = PoDiscrepancy::where('po_item_id', $poItemId)->sum('discrepancy_count');
        $updateDiffQty = ($itemData->order_qty - ($itemData->received_qty + $sumOfDiscQty));

        PurchaseOrderItem::where('id', $poItemId)->update([
            'difference_qty' => $updateDiffQty,
            'difference_price' => $updateDiffQty * $itemData->unit_price,
        ]);
    }

}
