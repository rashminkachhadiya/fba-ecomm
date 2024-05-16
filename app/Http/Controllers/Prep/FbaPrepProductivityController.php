<?php

namespace App\Http\Controllers\Prep;

use App\Http\Controllers\Controller;
use App\Models\FbaPrepBoxDetail;
use App\Models\User;
use Illuminate\Http\Request;

class FbaPrepProductivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function index()
    {
        $today = FbaPrepBoxDetail::todayPreppedData($userId = NULL);
        $yesterday = FbaPrepBoxDetail::yesterdayPreppedData($userId = NULL);
        $currentWeek = FbaPrepBoxDetail::currentWeekPreppedData($userId = NULL);
        $lastWeek = FbaPrepBoxDetail::lastWeekPreppedData($userId = NULL);
        $currentMonth = FbaPrepBoxDetail::currentMonthPreppedData($userId = NULL);
        $lastMonth = FbaPrepBoxDetail::lastMonthPreppedData($userId = NULL); 
        $preppedUsers = FbaPrepBoxDetail::get()->pluck('created_by')->toArray();
        $preppedUserIdsArr = array_values(array_unique($preppedUsers));
        $users = User::whereIn('id', $preppedUserIdsArr)->where(['deleted_at' => NULL])->get();
       
        return view(
            'fba_prep.prep_productivity',
            compact(
                'yesterday',
                'today',
                'currentWeek',
                'lastWeek',
                'currentMonth',
                'lastMonth',
                'users',
                'preppedUserIdsArr'
            ));

       return view('fba_prep.prep_productivity');
    }
    public function getPrepData(Request $request){
        $userId = $request->user_id;
        $viewType = $request->view_type;
        $dateRange = $request->daterange;
        $resultArr = FbaPrepBoxDetail::getPrepDashboardGraph($viewType,$dateRange,$userId,'Graph');
        $data = json_encode($resultArr['final_data']);
        $prepUserNames = json_encode(array_unique($resultArr['prepUserNames']));
        return view('fba_prep.prep_unit_graph',compact('data','prepUserNames'));
    }


}
