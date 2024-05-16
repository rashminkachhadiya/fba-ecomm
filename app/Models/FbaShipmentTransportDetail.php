<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FbaShipmentTransportDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function setFreightReadyDateAttribute($value)
    {
        if ($value == '') {
            $this->attributes['freight_ready_date'] = "0000-00-00";
        } else {
            $this->attributes['freight_ready_date'] = Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
        }
    }

    public function getFreightReadyDateAttribute($value)
    {
        if (!empty($value) && $value != '0000-00-00') {
            return Carbon::parse($value)->format('d-m-Y');
        } else {
            return '';
        }

    }

    public function getConfirmDeadlineAttribute($value)
    {
        if (!empty($value)) {
            return Carbon::parse($value)->format('d-m-Y H:i:s');
        }
    }

}
