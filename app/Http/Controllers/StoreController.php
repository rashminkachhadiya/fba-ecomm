<?php

namespace App\Http\Controllers;

use App\DataTables\StoreDataTable;
use App\Http\Requests\StoreRequest;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Services\CommonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Html\Builder;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StoreDataTable $dataTable)
    {
        $statusArr = config('constants.statusArr');
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('stores.list', compact(['statusArr','listingCols']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $storeTypes = StoreConfig::select('id','store_type')->get();
        return view('stores.create', compact('storeTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {
            $storeData = $request->validated();
            $storeData['user_id'] = Auth::user()->id;
            $storeData['role_arn'] = $request->role_arn;
            $stores = Store::create($storeData);

            if($stores)
            {
                return $this->sendResponse('Store created successfully', 200);
            }
            
            return $this->sendError('Something went wrong',500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(),500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        $storeTypes = StoreConfig::select('id','store_type')->get();
        return view('stores.edit',compact('store','storeTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Store $store)
    {
        try {
            $storeData = $request->validated();
            $storeData['role_arn'] = $request->role_arn;
            $store->update($storeData);

            if($store)
            {
                return $this->sendResponse('Store updated successfully', 200);
            }
            
            return $this->sendError('Something went wrong',500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(),500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        try {
            $store->delete();
            if($store)
            {
                return $this->sendResponse('Store deleted successfully', 200);
            }
            
            return $this->sendError('Something went wrong',500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(),500);
        }
    }

    /**
     * Update status of the store.
     */
    public function updateStatus(Request $request)
    {
        try{
            if($request->id) {
                $updateArr = [
                            'status' => $request->status == '1' ? '0' : '1',
                        ];
                
                Store::where('id', $request->id)->update($updateArr);

               return $this->sendResponse('Status Updated Successfully.',200);
            } else {
                return $this->sendValidation('Something went wrong, Please try again',400);
            }
        } catch(\Exception $ex) {
             return $this->sendValidation($ex->getMessage(),400);
        }   
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.stores'));
        if(isset($response['status']) && $response['status'] == true){
            
            return $this->sendResponse('Listing columns updated or created successfully.',200);
        }else{
            return $this->sendValidation($response['message'],400);
        }
    }
}
