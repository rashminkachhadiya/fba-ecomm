<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FbaShipmentItem;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbaShipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function scopeShipmentStatus($query, $value)
    {
        return $query->where('shipment_status', $value);
    }

    public function fbaShipmentItems()
    {
        return $this->hasMany(FbaShipmentItem::class);
    }

    public function shipmentPlan()
    {
        return $this->belongsTo(ShipmentPlan::class, 'fba_shipment_plan_id', 'id');
    }

    public static function getLatestRecord()
    {
        return self::max('updated_at');
    }

    public static function getShipmentData($shipment_id)
    {
        return self::where('id', $shipment_id)->select('shipment_id', 'store_id')->first();
    }
}
