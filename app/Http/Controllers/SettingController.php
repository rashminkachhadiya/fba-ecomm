<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Http\Requests\WarehouseRequest;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = Setting::first();
        $warehouse = Warehouse::first();
        return view('settings.create', compact('setting', 'warehouse'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SettingRequest $request)
    {
        Setting::updateOrCreate(['id' => 1], $request->validated());
        return $this->sendResponse('Setting updated successfully', 200);
    }

    public function shipmentReverseSync(Request $request)
    {
        if (isset($request->store_id) && !empty($request->store_id)) {
            \Artisan::call("app:fba-shipment-reverse-sync", ['store_id' => $request->store_id]);
        } else {
            \Artisan::call("app:fba-shipment-reverse-sync");
        }

        if (isset($request->store_id) && !empty($request->store_id)) {
            \Artisan::call("app:fba-shipment-items-reverse-sync", ['store_id' => $request->store_id]);
        } else {
            \Artisan::call("app:fba-shipment-items-reverse-sync");
        }

        $response = [
            'type'   => 'success',
            'status' => 200,
            'message' => 'Shipments synchronized successfully',
        ];

        return response()->json($response);
    }
    public function updateWarehouseData(WarehouseRequest $request)
    {
        $warehouseArray = [
            'name' => $request->warehouse_name,
            'address_1' => $request->warehouse_address_1,
            'address_2' => $request->warehouse_address_2,
            'country' => $request->country,
            'city' => $request->city,
            'state_or_province_code' => $request->state_or_province_code,
            'country_code' => $request->country_code,
            'postal_code' => $request->postal_code,
        ];
        Warehouse::updateOrCreate(['id' => $request->warehouse_id], $warehouseArray);
        return $this->sendResponse('Warehouse Updated successfully', 200);
    }
}
