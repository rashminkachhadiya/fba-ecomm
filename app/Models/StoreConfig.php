<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreConfig extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_type',
        'store_country',
        'store_url',
        'amazon_marketplace_id',
        'amazon_aws_region',
        'aws_endpoint',
        'store_currency',
        'store_timezone'
    ];
}
