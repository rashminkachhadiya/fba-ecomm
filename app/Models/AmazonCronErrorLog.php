<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonCronErrorLog extends Model
{
    use HasFactory;

    protected $fillable = ['store_id','batch_id','module','submodule','error_content'];

    public static function logError($error_arr)
    {
        return self::create( $error_arr );
    }
}
