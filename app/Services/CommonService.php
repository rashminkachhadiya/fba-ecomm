<?php

namespace App\Services;

use App\Models\AmazonProduct;
use App\Models\MasterListingColumn;
use App\Models\QtyLog;
use Carbon\Carbon;
use Batch;

class CommonService {
    public function getLisingColumns($userId, $moduleName)
    {
        return MasterListingColumn::where(['user_id' => $userId, 'module_name' => $moduleName])->value('columns');
    }

    public function getColumnVisibility($fields, $moduleName) : array
    {
        $flag = [];
        
        try{
            MasterListingColumn::updateOrCreate(
                [
                    'user_id' => auth()->user()->id,
                    'module_name' => $moduleName
                ],
                ['columns' => $fields]
            );
            $flag['status'] = true;

        } catch(\Exception $ex) {
            $flag['status'] = false;
            $flag['message'] = $ex->getMessage();
        }
        
        return $flag;
    }

    public function checkShipmentIdExpired($createdAt) : bool
    {
        $response = false;

        $createdDateTime = Carbon::parse($createdAt)->format('Y-m-d H:i:s');
        $date = Carbon::now()->subDays(2)->format('Y-m-d H:i:s');

        if ($createdDateTime < $date) {
            $response = true;
        }

        return $response;
    }

    public function totalFBAQty(array $allQty) : int
    {
        [$qty, $inboundWorkingQty, $inboundShippedQty, $inboundReceivingQty, $reservedQty] = $allQty;
        return ($qty + ($inboundWorkingQty + $inboundShippedQty + $inboundReceivingQty)) - $reservedQty;
    }

    public function calculteSuggestedShipmentQty(array $parameters) : int
    {
        // (Target qty on hands days(day of stock holding) + Local lead time) * ROS - Current Amazon inventory
        [[$targetQty, $leadTime], $ros30, $fbaQty] = $parameters;
        $suggestedShipmentQty = (($targetQty + $leadTime) * $ros30) - $fbaQty;
        return ($suggestedShipmentQty > 0) ? $suggestedShipmentQty : 0;
    }

    public static function tableImageZoom($imgUrl,$largeImageUrl='',$isTitleSearch = '',$is_show_DealIcon = '', $value='')
    {
        $imgUrl = empty($imgUrl) ? asset('media/no-image.jpeg') : $imgUrl;
        $largeImageUrl = empty($largeImageUrl) ? $imgUrl : $largeImageUrl;
        
        $data = '<a data-fslightbox="lightbox-basic" href="' . $largeImageUrl . '" class="magnific border overflow-hidden d-flex align-items-center justify-content-center border-gray-400  rounded w-50px h-50px border d-flex" target="_blank">
                    <img src="' . $imgUrl . '" data-original="' . $imgUrl . '" border="0" class="img-rounded zoom-image max-w-100px max-h-100%" align="center" style="image-orientation: from-image;overflow: hidden;">
                </a>';

        if ($isTitleSearch == 1)
        {
            $data .= '<div class="bg-primary py-1 rounded text-white fs-8 mt-1 d-flex align-items-center ps-2"><i class="fa-light fa-file-magnifying-glass text-white me-1"></i> <span>TS</span></div>';
        }

        if ($is_show_DealIcon == 1)
        {
            $data .= '<div class="bg-danger py-1 rounded text-white fs-8 mt-1 d-flex align-items-center ps-1"><a href="javascript:void(0)" class="text-white" data-supplier_id="'.$value->supplier_id.'" data-record_id="'.$value->id.'" onclick="showingDealDateRange(this,'. $value->upc .')"><i class="fa-solid fa-percent text-white ms-1 me-1"></i><span> Deal</span></a></div>';
        }
        return $data;
    }

    public static function getAPackFormatedValue($aPack)
    {
        $aPackValue = explode('.', $aPack);

        $decimalValue = isset($aPackValue[1]) ? $aPackValue[1] : 0;

        if (!empty($decimalValue) && $decimalValue != '000')
        {
            return number_format($aPack, 3);
        } else {
            return (int)$aPack;
        }
    }

    /**
     * productDetail = [id, previous_qty, updated_qty, pack_of],
     */
    public function storeQtyLog(array $productDetail, $comment = '', $isMulti = false)
    {
        try {
            if($isMulti)
            {
                if(!empty($productDetail))
                {
                    Batch::insert(new QtyLog, array_keys($productDetail[0]), $productDetail);
                    return true;
                }
            }else{
                $qtyLog = QtyLog::create([
                    'amazon_product_id' => $productDetail['id'],
                    'previous_qty' => $productDetail['previous_qty'],
                    'updated_qty' => $productDetail['updated_qty'],
                    'comment' => $comment,
                    'updated_by' => auth()->user()->id,
                    'created_at' => now()
                ]);
                
                if($qtyLog)
                {
                    AmazonProduct::where('id', $productDetail['id'])->update([
                        'sellable_units' => floor($qtyLog->updated_qty / $productDetail['pack_of']),
                        'wh_qty' => $productDetail['updated_qty'],
                    ]);
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAmazonProduct($productId)
    {
        return AmazonProduct::where('id', $productId)->select('wh_qty','pack_of')->first();
    }
}