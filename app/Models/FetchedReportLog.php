<?php

namespace App\Models;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mavinoo\Batch\Common\Common;

class FetchedReportLog extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'report_source', 'report_type', 'report_type_name', 'report_frequency', 'status', 'report_url', 'cron_start', 'cron_end']; 

    public $timestamps = false;

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /*
        @Description    : Function to insert or update fetched report log entry
        @Author         : Sanjay Chabhadiya
        @Input          : Field Array
        @Output         : return id
        @Date           : 09-03-2021
    */    

    public static function fetchReportLog($data = [])
    {
        if(!empty($data['id'])) {
            //update case
            self::where('id', $data['id'])->update([
                'cron_end' => CommonHelper::getInsertedDateTime(),
                'status' => trim($data['status']),
                'report_url' => trim($data['report_url'])
            ]);

            return $data['id'];
        } else {
            //insert or update
            //check if report type exist if not then create
            $stored = self::select('id')
                ->where('store_id', $data['store_id'])
                ->where('report_type', $data['report_type'])
                ->where('report_source', $data['report_source'])
                ->first();

            if(!empty($stored) && !empty($stored->id)) {
                $stored->update([
                    'cron_start' => CommonHelper::getInsertedDateTime(),
                    'report_type_name' => trim($data['report_type_name']),
                    'report_frequency' => trim($data['report_frequency'])
                ]);
            } else {
                $data += [
                    'cron_start' => CommonHelper::getInsertedDateTime(),
                    'report_url' => !empty($data['report_url']) ? trim($data['report_url']) : '',
                ];
                $stored = self::create($data);
            }

            return $stored;
        }
    }
}
