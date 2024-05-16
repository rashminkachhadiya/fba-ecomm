<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InactiveAmazonProductLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function insertInactiveProductLogForInactive($productId)
    {
        $todaysDate = date('Y-m-d');

        $checkIsRowExist = self::where([
            ['amazon_product_id', $productId],
            ['end_date', null]
        ])->first();

        // If row is not there then add the row with empty end date
        if (!$checkIsRowExist) {
            self::create([
                'amazon_product_id' => $productId,
                'start_date' => $todaysDate,
                'end_date' => null
            ]);
        }
    }

    public static function insertInactiveProductLogForActive($productId)
    {
        $todaysDate = date('Y-m-d');

        $checkIsRowExist = self::where([
            ['amazon_product_id', $productId],
            ['end_date', null]
        ])->first();

        // If row is not there then add the row with empty end date
        if ($checkIsRowExist) {

            // If start date and end date are same then delete that record because we are going to exclude start date and end date
            // So there is no mean to keep that record
            if ($checkIsRowExist->start_date == $todaysDate) {
                $checkIsRowExist->delete();
            }

            $checkIsRowExist->update([
                'end_date' => $todaysDate
            ]);
        }
    }
}
