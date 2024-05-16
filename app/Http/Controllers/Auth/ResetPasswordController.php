<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Auth;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function store(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email,status,1,deleted_at,NULL',
        ],[
            'email.required' => 'Email address is required',
            'email.exists' => 'User is deleted or not activated.',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        if ($status == Password::RESET_LINK_SENT) {
          return redirect()->back()->with('success','Reset password link sent to registerd email address!');
        } else {
           return redirect()->back()->with('error',__($status));
        }
        
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'password_confirmation' => 'required|same:password',
        ]);
        
        $user = User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user_data = Auth::user();
            return redirect()->intended(route('home'));
        } else {
            return redirect()->back()->with('error','Something went wrong!')->withInput();
        }
    }
}
