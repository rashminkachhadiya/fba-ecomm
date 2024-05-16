<?php

namespace App\Http\Controllers\Supplier;

use App\DataTables\Supplier\ContactsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\SupplierContact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\CommonService;

class ContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ContactsDataTable $dataTable, Request $request)
    {
        if ($request->has('supplierId')) {
            $supplier_id = request('supplierId');

        } else {
            $supplier_id = '';
        }
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('suppliers.contacts.list', compact(['supplier_id','listingCols']));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactRequest $request)
    {
        $supplier_id = $request->supplier_id;
        $contact_info_id = $request->contact_info_id;
        $is_default = $request->is_default;
        $is_default_val = '0';
        $default_contact_info = SupplierContact::getDefaultContact($supplier_id);

        if(!is_null($default_contact_info) && !empty($default_contact_info)){
            if($is_default == '1'){
                SupplierContact::where('id', $default_contact_info->id)->update(['is_default' => '0']);
                $is_default_val = '1';
            }elseif(is_null($is_default) && ($contact_info_id == $default_contact_info->id)){
                $is_default_val = '1';
            }
        }else{
            $is_default_val = '1';
        }

        if (empty($contact_info_id)) {
            $data = $request->validated();
            if (!is_null($supplier_id)) {
                $data['supplier_id'] = $supplier_id;
                $data['created_by'] = auth()->user()->id;
                $data['created_at'] = Carbon::now();
                $data['is_default'] = $is_default_val;

                $contact_info = SupplierContact::create($data);

                if ($contact_info) {
                    $response = [
                        'type' => 'success',
                        'status' => 200,
                        'message' => 'Contact Information added successfully',
                    ];
                } else {
                    $response = [
                        'type' => 'error',
                        'status' => 500,
                        'message' => 'Something went wrong',
                    ];
                }
            } else {
                $response = [
                    'type' => 'error',
                    'status' => 500,
                    'message' => 'Add Basic Information of Supplier First',
                ];
            }
        } else {
            $data = $request->validated();
            $data['updated_by'] = auth()->user()->id;
            $data['updated_at'] = Carbon::now();
            $data['is_default'] = $is_default_val;

            SupplierContact::where('id', $contact_info_id)->update($data);

            $response = [
                'type' => 'success',
                'status' => 200,
                'message' => 'Contact Information updated successfully',
            ];
        }

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $contact_info = SupplierContact::find($id);
        return response()->json($contact_info);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        SupplierContact::where('id', $id)->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json([
            'type' => 'success',
            'status' => 200,
            'message' => 'Contact deleted successfully',
        ]);
    }

    public function columnVisibility(Request $request, ContactsDataTable $dataTable)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.supplier_contacts'));
       if(isset($response['status']) && $response['status'] == true){
         return $this->sendResponse('Listing columns updated or created successfully.',200);
       }else{
         return $this->sendValidation($response['message'],400);
       }
    }
}
