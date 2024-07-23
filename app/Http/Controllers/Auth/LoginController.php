<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    /**
     * Handle a login request to the application and check if the user is active / not, also generate resent link for inactive user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];


        $messages = [
//            'title.required' => 'Title is required',
        ];

        $this->validate($request, $rules, $messages);

        $credentials = $request->except(['_token']);

        if (auth()->attempt($credentials)) {
            if ( auth::user()->status == 0){
                        Auth::logout();
                        session()->forget('cart');
                        session()->forget('storyItems');
                        session()->forget('redirect');

                        $errors = 'Your Account has been deactivated';

                        return response()->json(
                            [
                                'status' => 'invalid',
                                'message' => 'The given data was invalid.',
                                'errors' => $errors,
                            ]
                         );
            }
            else{
                 if ($request->password == 'default123') {
           
                        return response()->json(
                            [
                                'status' => 'success',
                                'message' => 'Your Password is too Weak, Please update your password to a strong one. ',
                                'redirect' => Session::get('redirect') ? Session::get('redirect') : route('view.update_password'),
                            ]
                        );  
                    }
                    else{
                        return response()->json(
                            [
                                'status' => 'success',
                                'redirect' => Session::get('redirect') ? Session::get('redirect') : route('profile'),
                            ]
                        );  
                    } 
                }
        }
        else {
            $user = User::where('email', $request->email)->first();
            if(!$user) {
                $errors = 'Your Email seems incorrect.';
            }elseif($user->email_verified_at==''){
                $errors = 'Your Email is not verified.';
            } else {
                $errors = 'Your Password seems incorrect.';
            }
            return response()->json(
                [
                    'status' => 'invalid',
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ]
            );
        }
    }

    public function logout() {

        Auth::logout();
		session()->forget('cart');
        session()->forget('storyItems');
    	session()->forget('redirect');
        return redirect()->route('home');
    }
}
