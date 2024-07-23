<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FamilyTree;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Rules\dobRequired;

class RegisterController extends Controller
{
    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }
    
    /**
     * Handle a registration request for the application and sent mail to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
    	$rules = [
                'first_name'    => 'required|string|max:255',
                'last_name'     => 'required|string|max:255',
                'email'        => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users'),
                ],
                // 'email'=>'required|email',
                'password'     => 'required|min:6|confirmed',
                'country_id'   => 'required',
                'postal_code'  => 'required',
                // 'dob'  => 'required',
                'day' => new dobRequired, 
                'connected_period' => 'required',
                'gender' => 'required',
                //'terms' => 'required',
            ];


            $messages = [
               'gender.required' => 'Gender is required',
            ];

            $this->validate($request, $rules, $messages);
    
        $user=User::where('email',strtolower(trim($request->email)))->first();

        if ($request->day < 10) {
            $formattedDay = sprintf('%02d', $request->day);
        } else {
            $formattedDay = $request->day;
        }

        if ($request->month < 10) {
            $formattedMonth = sprintf('%02d', $request->month);
        } else {
            $formattedMonth = $request->month;
        }

        $dob = $formattedDay."-".$formattedMonth."-".$request->year;
        $dateOfBirth = date_format(date_create( $dob),'Y-m-d');



        if(empty($user)) {
            
            
            $user = new User;
            $user->first_name =  $request->first_name ;
            $user->last_name =  $request->last_name ;
            $user->connected_period =  $request->connected_period ;
            $user->dob =  $dateOfBirth;
            $user->postal_code =  $request->postal_code ;
            $user->country_id =  $request->country_id ;
            $user->email =  $request->email ;
            $user->password =  bcrypt($request->get('password'));
            $user->status =  1 ;
            $user->gender =  strtolower($request->gender);
            $user->verified = 1;
            $user->save();


            
            $relativeInfo=new FamilyTree();

            $relativeInfo->first_name=$request->first_name;

            $relativeInfo->last_name=$request->last_name;

            $relativeInfo->email=strtolower(trim($request->email));

            $relativeInfo->gender=strtolower($request->gender);

            $relativeInfo->user_id=$user->id;

            $relativeInfo->dob= $dateOfBirth;

            $relativeInfo->status=1;
             $relativeInfo->living=1;
            $relativeInfo->created_at=Carbon::now();

            $relativeInfo->save();

             // dd($user);
            
            auth()->loginUsingId($user->id);

            return response()->json(
                [
                    'status' => 'success',
                    'user'   => $user,
                    'redirect' => Session::get('redirect') ? Session::get('redirect') : route('profile'),
                ]
            );
        }
        else{

            if($user->verified!=1){
                $rules = [
                        'first_name'    => 'required|string|max:255',
                        'last_name'     => 'required|string|max:255',
                        // 'email'        => [
                        //     'required',
                        //     'email',
                        //     'max:255',
                        //     Rule::unique('users'),
                        // ],
                        'email'=>'required|email',
                        'password'     => 'required|min:6|confirmed',
                        'country_id'   => 'required',
                        'postal_code'  => 'required',
                        // 'dob'  => 'required',
                        'connected_period' => 'required',
                        'gender' => 'required',
                        //'terms' => 'required',
                    ];


                $messages = [
                   'gender.required' => 'Gender is required',
                ];


                // dd($request->all());
                // $this->validate($request, $rules, $messages);
               
                $input = $request->all();
                $input['password'] = bcrypt($request->get('password'));
                $input['dob'] =date_format(date_create( $request->get('dob')),'Y-m-d'); //Carbon::createFromFormat('m-d-Y', $request->get('dob'))->format('Y-m-d');
                $input['status'] = 1;
                $input['verified'] = 1;
                $input['gender'] = strtolower($request->gender);

                // dd($input);
                $user_id=$user->id;
                $user = $user->update($input);

                 // dd($user_id);
                
                $relativeInfo=FamilyTree::where('user_id',$user_id)->first();

                $relativeInfo->first_name=$request->first_name;

                $relativeInfo->last_name=$request->last_name;

                $relativeInfo->email=strtolower(trim($request->email));

                $relativeInfo->gender=strtolower($request->gender);

                $relativeInfo->user_id=$user_id;

                $relativeInfo->dob=$dateOfBirth;

                $relativeInfo->status=1;

                $relativeInfo->created_at=Carbon::now();

                $flag=$relativeInfo->save();
                
                auth()->loginUsingId($user_id);

                return response()->json(
                    [
                        'status' => 'success',
                        'user'   => $user,
                        'redirect' => Session::get('redirect') ? Session::get('redirect') : route('profile'),
                    ]
                );
            }
        }
    }
}
