<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_config_id',
        'store_marketplace',
        'merchant_id',
        'refresh_token',
        'access_token',
        'client_id',
        'client_secret',
        'aws_access_key_id',
        'aws_secret_key',
        'session_token',
        'sts_access_key_id',
        'sts_secret_key',
        'role_arn',
        'deleted_at'
    ];

    /**
     * Return 
     */
    public function store_config() : BelongsTo
    {
        return $this->belongsTo(StoreConfig::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    public static function getStoreConfig($storeId)
    {
        // If store id is not empty
        if (!empty($storeId)) {
            // Set credentials and config data for store id
            return self::where('id', $storeId)->active()
                ->with(['store_config' => function($query) {
                    $query->select('id','store_type','amazon_marketplace_id','store_currency','amazon_aws_region','aws_endpoint','store_url');
                }])->first();
        }

        return [];
    }
}
