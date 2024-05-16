<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SupplierContact extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public static function getDefaultContact($supplier_id)
    {
        return self::where('supplier_id', $supplier_id)->where('is_default', '1')->first();
    }
}
