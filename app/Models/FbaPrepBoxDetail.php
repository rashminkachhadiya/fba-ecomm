<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FbaPrepBoxDetail extends Model
{
    use HasFactory;

    public $guarded = [];

    public function FbaShipmentItem()
    {
        return $this->belongsTo(FbaShipmentItem::class, 'fba_shipment_item_id', 'id');
    }

    //Todays Prepped data counts...
    public static function todayPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
      
        $result = self::select(
            DB::raw("SUM(units) as units_prepped"),
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereDate('created_at', Carbon::today())
        ->first()->toArray();

        return $result;
    }

    //Yesterday Prepped data counts...
    public static function yesterdayPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
        $aDayBefore = date("Y-m-d", strtotime( '-1 days' ) );
        return self::select(
            DB::raw("SUM(units) as units_prepped"), 
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereDate('created_at','=', $aDayBefore)
        ->first()->toArray();
    }

    //Current Week Prepped data counts...
    public static function currentWeekPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
        return self::select(
            DB::raw("SUM(units) as units_prepped"), 
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereBetween('created_at',
            [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]
        )
        ->first()->toArray();
    }

    //Last Week Prepped data counts...
    public static function lastWeekPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
        return self::select(
            DB::raw("SUM(units) as units_prepped"), 
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereBetween('created_at', 
            [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]
        )
        ->first()->toArray();
    }

    //Current Month Prepped data counts...
    public static function currentMonthPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
        return self::select(
            DB::raw("SUM(units) as units_prepped"), 
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereMonth('created_at', Carbon::now()->month)
        ->first()->toArray();
    }

    //Last Month Prepped data counts...
    public static function lastMonthPreppedData($userId){
        if(empty($userId)){ $whereUser = NULL; }else{ $whereUser = array('created_by' => $userId); }
        return self::select(
            DB::raw("SUM(units) as units_prepped"), 
            DB::raw("COUNT(distinct(sku)) as sku_counts")
        )
        ->where($whereUser)
        ->whereBetween('created_at',
            [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]
        )
        ->first()->toArray();
    }

    public static function getPrepDashboardGraph($view_type,$daterange,$user_id,$pageType){
        list($start_date,$end_date) = explode('-', $daterange);
        $start_date = date('Y-m-d',strtotime($start_date));
        $end_date = date('Y-m-d',strtotime($end_date));
        $day_diff = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date));
        
        if($user_id == 'All'){ $whereUser = NULL; }else{ $whereUser = array('fba_prep_box_details.created_by' => $user_id); }
        
        if($view_type == "month")
        {
            $where_data = DB::raw('Month(created_at)');
            $where_select = DB::raw('DATE(created_at) as date');
        }
        else if($view_type == "week")
        {
            $where_data = DB::raw('Week(created_at)');
            $where_select = DB::raw('DATE(created_at) as date');
        }
        else
        {
            $where_data = DB::raw('Date(created_at)');
            $where_select = DB::raw('DATE(created_at) as date');
        }
        if($pageType=='Detail'){
            $whereSku = 'fba_prep_box_details.sku';
        }else{
            $whereSku = NULL;
        }

        $data = DB::table('fba_prep_box_details')
            ->select('fba_shipment_item_id','fba_shipment_id','box_number','units','sku','main_image',$where_select, DB::raw('SUM(units) as unit_counts'),  DB::raw("COUNT(distinct(sku)) as sku_counts"), DB::raw('COUNT(box_number) as box_counts'),'created_by')       
            ->where($whereUser)
            ->whereBetween('created_at',[$start_date.' 00:00:00',$end_date.' 23:59:59'])->orderBy('created_at')
            ->addSelect([
                'userFname' => User::select('name')
                    ->whereColumn('users.id', 'fba_prep_box_details.created_by'),
            ])
            ->addSelect([
                'productTitle' => AmazonProduct::select('title')
                    ->whereColumn('amazon_products.sku', 'fba_prep_box_details.sku'),
                'productFNSKU' => AmazonProduct::select('fnsku')
                    ->whereColumn('amazon_products.sku', 'fba_prep_box_details.sku'),
            ]) 
            ->groupBy($where_data, $whereSku, 'created_by')
            ->get()->toArray();
        if($pageType=='Detail'){
            return $data;
        }
        
        $final_data = [];
        $available_dates = [];
        $totalUnitCounts = 0;
        $dataArr = [];
        $prepUserNames = [];

        //Stack column chart display different view wise...
        if(!empty($data))
        {
            if(!empty($data))
            {
                foreach ($data as $k => $d_value)
                {
                    $userData = User::select('name')->where([
                        'id' => $d_value->created_by, 
                        'deleted_at' => NULL
                    ])
                    ->first()->toArray();
                    $userName = strtolower($userData['name']);
                    $prepUserNames[] = $userName;

                    if($view_type == "day"){
                        $dataArr[$d_value->date][$userName] = $d_value->unit_counts;
                        $dataArr[$d_value->date]["none"] = "0";
                    }
                    if($view_type == "week"){
                        $dataArr[date("W", strtotime($d_value->date))][$userName] = $d_value->unit_counts;
                    }
                    if($view_type == "month"){
                        $dataArr[date("Y-m", strtotime($d_value->date))][$userName] = $d_value->unit_counts;
                    }
                }
            }
        }
        
        //Display Day view wise graph...
        if($view_type == "day"){
            for ($i=$start_date; $i<=$end_date; $i=date('Y-m-d', strtotime("+1 day ".$i)))
            {
                if(!empty($dataArr))
                {
                    $new = [];
                    foreach ($dataArr as $date => $dValue)
                    {
                        if($date==date("Y-m-d", strtotime($i)))
                        {
                            foreach ($dValue as $username => $units)
                            {
                                $new['date'] = date('m/d/Y', strtotime($date));
                                $new[$username] = intval($units);
                                $new["none"] = "0";
                            }
        
                            array_push($available_dates,$date);
                            array_push($final_data,$new); 
                        }
                    }

                    $date = date("Y-m-d", strtotime($i));
                    if(!in_array($date, $available_dates))
                    {
                        $new['date'] = date('m/d/Y', strtotime($date));
                        $new['username'] = 0;
                        array_push($final_data,$new);
                    }
                }
            }
        }

        //Display Week view wise graph...
        if($view_type == "week"){
            for ($i=$start_date; $i<=$end_date; $i=date('Y-m-d', strtotime("+1 day ".$i)))
            {
                if(!empty($dataArr))
                {
                    $new = [];
                    $loopDateWeeek = date("W", strtotime($i));
                    foreach ($dataArr as $weekNumber => $dValue)
                    { 
                        if($loopDateWeeek == $weekNumber){
                        
                            foreach ($dValue as $username => $units)
                            {
                                $new['date'] = "Week: " . $weekNumber;
                                $new[$username] = intval($units);
                                $new["none"] = "0";
                            }
        
                            array_push($available_dates,$weekNumber);
                            array_push($final_data,$new);
                        }
                    }
                }

                $dWeek = date("W", strtotime($i));
                if(!in_array($dWeek, $available_dates))
                {
                    $new['date'] = "Week: " . $dWeek;
                    $new['username'] = 0;
                    array_push($final_data,$new);
                }
            }

            $result = array();
            foreach ($final_data as $key => $value){
                if(!in_array($value, $result))
                    $result[$key]=$value;
            }
            $final_data = array_filter(array_values($result));
        }
        
        //Display Month view wise graph...
        if($view_type == "month"){
            for ($i=$start_date; $i<=$end_date; $i=date('Y-m-d', strtotime("+1 day ".$i)))
            {
                if(!empty($dataArr))
                {
                    $new = [];
                    foreach ($dataArr as $date => $dValue)
                    {
                        if($date==date("Y-m", strtotime($i)))
                        {
                            foreach ($dValue as $username => $units)
                            {
                                $new['date'] = date('M Y', strtotime($date));
                                $new[$username] = intval($units);
                                $new["none"] = "0";
                            }
        
                            array_push($available_dates,$date);
                            array_push($final_data,$new); 
                        }
                    }

                    $date = date("Y-m", strtotime($i));
                    if(!in_array($date, $available_dates))
                    {
                        $new['date'] = date('M Y', strtotime($date));
                        $new['username'] = 0;
                        array_push($final_data,$new);
                    }
                }
            }

            $result = array();
            foreach ($final_data as $key => $value){
                if(!in_array($value, $result))
                    $result[$key]=$value;
            }
            $final_data = array_filter(array_values($result));
        }
       
        $resultArr = [];
        $resultArr['prepUserNames'] = $prepUserNames;
        $resultArr['final_data'] = $final_data;
        return $resultArr;
    }

}
