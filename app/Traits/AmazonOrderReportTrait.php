<?php

namespace App\Traits;

use App\Models\AmazonOrderReport;
use App\Models\AmazonProduct;

trait AmazonOrderReportTrait 
{
    public function excelColumnList() : array
    {
        return [
            'amazon-order-id' => ['string','amazon_order_id'],
            'merchant-order-id' => ['string','merchant_order_id'],
            'order-status' => ['string','order_status'],
            'fulfillment-channel' => ['string','fulfillment_channel'],
            'sales-channel' => ['string','sales_channel'],
            'order-channel' => ['string','order_channel'],
            'ship-service-level' => ['string','ship_service_level'],
            'sku' => ['string','sku'],
            'asin' => ['string','asin'],
            'item-status' => ['string','item_status'],
            'quantity' => ['string','quantity'],
            'currency' => ['string','currency'],
            'item-price' => ['int','item_price'],
            'item-tax' => ['int','item_tax'],
            'shipping-price' => ['int','shipping_price'],
            'shipping-tax' => ['int','shipping_tax'],
            'gift-wrap-price' => ['int','gift_wrap_price'],
            'gift-wrap-tax' => ['int','gift_wrap_tax'],
            'item-promotion-discount' => ['int','item_promotion_discount'],
            'ship-promotion-discount' => ['int','ship_promotion_discount'],
            'ship-city' => ['string','ship_city'],
            'ship-state' => ['string','ship_state'],
            'ship-postal-code' => ['string','ship_postal_code'],
            'ship-country' => ['string','ship_country'],
            'promotion-ids' => ['string','promotion_ids'],
            'is-business-order' => ['string','is_business_order'],
            'purchase-order-number' => ['string','purchase_order_number'],
            'price-designation' => ['string','price_designation']
        ];
    }

    public function getAmazonProduct(array $allSku) : array
    {
        return AmazonProduct::select('id','sku','store_id')->whereIn('sku', $allSku)->get()->keyBy('sku')->toArray();
    }

    public function existOrderReportData(array $amazonProductId, array $amazonOrderId) : array
    {
        return AmazonOrderReport::select('id', 'product_id', 'amazon_order_id')->whereIn('product_id', $amazonProductId)->whereIn('amazon_order_id', $amazonOrderId)->get()->toArray();
    }
}