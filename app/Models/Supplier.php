<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, HasFactory;

    public $timestamps = true;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    /**
     * Interact with the Supplier's name.
     */
    // protected function name(): Attribute
    // {
    //     return Attribute::make(
    //         get:fn(string $value) => ucfirst($value),
    //     );
    // }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }
    protected static function booted() : void
    {
        static::deleted(function($supplier) {
            $supplier->supplierProducts()->delete();
        });
    }
}
