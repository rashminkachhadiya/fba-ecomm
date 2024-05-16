<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentPlan extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function shipmentProducts()
    {
        return $this->hasMany(ShipmentProduct::class);
    }

    protected static function booted() : void
    {
        static::deleted(function($supplier) {
            $supplier->shipmentProducts()->delete();
        });
    }

    public function fbaShipment()
    {
        return $this->hasMany(FbaShipment::class, "fba_shipment_plan_id", "id");
    }

    public static function getDraftPlanList()
    {
        $data = ShipmentPlan::select('id', 'plan_name')
            ->withCount('shipmentProducts')
            ->where('plan_status', 'Draft')
            ->orderBy('id', 'DESC')
            ->get()
            ->toArray();

        return $data;
    }
}
