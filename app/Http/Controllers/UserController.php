<?php

namespace App\Http\Controllers;

use App\DataTables\UsersDataTable;
use App\Http\Requests\UserProfileUpdateRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\CommonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        $statusArr = ["1"=>"Active", "0"=>"In-Active"];
        $listingCols = $dataTable->listingColumns();
        return $dataTable->render('users.list', compact(['statusArr','listingCols']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($request->password);
        unset($data['password_confirmation']);
        $user = User::create($data);

        if($user)
        {
            $response = [
                'type'   => 'success',
                'status' => 200,
                'message'=>'Add user successfully',
            ];
        }else{
            $response = [
                'type'   => 'fail',
                'status' => 500,
                'message'=>'Something went wrong',
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrfail($id);
        return view('users.edit',compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        User::where('id',$id)->update($request->safe()->except(['email']));

        return [
                'type'   => 'success',
                'status' => 200,
                'message'=>'User update successfully',
            ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        User::where('id', $id)->update([
            'status' => '0',
            // 'deleted_by' => Auth()->user()->id,
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json([
            'type'   => 'success',
            'status' => 200,
            'message'=>'User deleted successfully',
        ]);
    }

    public function updateStatus(Request $request)
    {
        try{
            if($request->id) {
                $updateArr = [
                            'status' => $request->status == '1' ? '0' : '1',
                            // 'updated_by' => auth()->user()->id
                        ];
                
                User::where('id', $request->id)->update($updateArr);

               return $this->sendResponse('Status Updated Successfully.',200);
            } else {
                return $this->sendValidation('Something went wrong, Please try again',400);
            }
        } catch(\Exception $ex) {
            return $this->sendValidation($ex->getMessage(),400);
        }   
    }

    public function columnVisibility(Request $request)
    {
        $response = (new CommonService())->getColumnVisibility($request->fields, config('constants.module_name.users'));
        if(isset($response['status']) && $response['status'] == true){
            return $this->sendResponse('Listing columns updated or created successfully.',200);
        }else{
            return $this->sendValidation($response['message'],400);
        }
    }

    public function profile()
    {
       $user = Auth()->user();
        return view('users.profile',compact('user'));
    }

    public function updateProfile(UserProfileUpdateRequest $request)
    {
        $userProfileArray = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'contact_no' =>  $request->contact_number ? $request->contact_number : null,
            'updated_at' => Carbon::now(),
        ];
       $userUpdate = User::where('id', auth()->user()->id)->update($userProfileArray);
       if($userUpdate){
        return response()->json([
            'type'   => 'success',
            'status' => 200,
            'message'=>'User profile updated successfully',
        ]);
       }else{
        return response()->json([
            'type'   => 'fail',
            'status' => 500,
            'message'=>'Something went wrong',
        ]);
       }
        
    }
}
