<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FamilyTree;
use App\Models\AddonVideo;
use App\Models\Story;


use Session;
use Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ixudra\Curl\Facades\Curl;


class ProfileController extends BaseController
{
    public function __construct() {
        parent::__construct();
    }
    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user_videos = DB::table('stories')->where('user_id',Auth::user()->id)->get();

            $user = Auth::user()->load('stories');
            $stories = $user->stories->toArray();
            $user_video = end($stories);
            $addon_videos = AddonVideo::where('user_id',Auth::user()->id)->get();
            $addon_videos_array = $addon_videos->toArray();
            $addon_video = end($addon_videos_array);
            
        return view('frontend.profile',compact('user_video','addon_video'));
    }

    public function view_delete_account(){
        return view('frontend.delete-account');
    }

    public function delete_account(Request $request){
            

            $rules = [
            'current_password' => 'required|confirmed',
            'current_password_confirmation' => 'required',
        ];


        $messages = [
            'current_password.required' => 'Please Enter Current Password ',
            'current_password.confirmed' => '   Current Password is not matched with Confirm Password',
            'current_password_confirmation.required' => 'Please Confirm the Password '
        ];

        $this->validate($request, $rules, $messages);


        $user = Auth::user();
        $currentPassword = $request->current_password;
    
            if (Hash::check($currentPassword, $user->password)) {
                $user->status = 0;
                $user->save();
                 Auth::logout();
                session()->forget('cart');
                session()->forget('storyItems');
                session()->forget('redirect');
                return redirect()->route('home');
                
            } 
            else {
            // The current password is incorrect
            return back()->withErrors(['current_password' => 'The Current Password is incorrect']);
        }
    }

    public function view_delete_videos(){
            return view('frontend.delete-videos');
    }

    public function delete_videos(Request $request){

            $rules = [
                    'current_password' => 'required|confirmed',
                    'current_password_confirmation' => 'required',
             ];


            $messages = [
                'current_password.required' => 'Please Enter Current Password ',
                'current_password.confirmed' => '   Current Password is not matched with Confirm Password',
                'current_password_confirmation.required' => 'Please Confirm the Password '
            ];

            $this->validate($request, $rules, $messages);


            $user = Auth::user();
            $currentPassword = $request->current_password;
    
            if (Hash::check($currentPassword, $user->password)) {
                
                $stories = Story::where('user_id',$user->id)->get();
                $addons = AddonVideo::where('user_id',$user->id)->get();
 
                if (count($stories) != 0) {
                    
                    foreach ($stories as $key => $story) {
                      $story->delete();
                    }

                    if (count($addons) != 0) {                    
                        foreach ($addons as $key => $addon) {
                         $addon->delete();
                        }
                    } 

                   return redirect()->route('view.delete_videos')->with("success","Story and Addon Videos Deleted");
                } 
                else {
                 return redirect()->route('view.delete_videos')->with("warning","You Don't have any Videos");
                }
            } 
            else {
            return back()->withErrors(['current_password' => 'The Current Password is incorrect']);
        }
    }


    public function previewStory() {
        if(Auth::check()){
            $user = Auth::user()->load('stories');
            $stories = $user->stories->toArray();
            $latest_story = end($stories);
            return view('frontend.preview-story', ['story' => $latest_story]);
        }
        else{
            return view('frontend.preview-story'); 
        }
    }

    public function updateProfile (Request $request)
    {

        DB::beginTransaction();
        $authuser=Auth::user();
        $rules = [
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'country_id'   => 'required',
            'postal_code'  => 'required',
            'dob'  => 'required',
            'connected_period' => 'required',
            'gender' => 'required',
            //'terms' => 'required',
        ];


        $messages = [
           'gender.required' => 'Gender is required',
        ];

        $this->validate($request, $rules, $messages);


        $input=[];
        $input['dob'] =date_format(date_create( $request->get('dob')),'Y-m-d'); //Carbon::createFromFormat('m-d-Y', $request->get('dob'))->format('Y-m-d');
        $input['status'] = 1;
        $input['gender'] = $request->gender;
        $input['updated_at'] = Carbon::now();
        $user = User::find($authuser->id);
        $user->first_name=$request->first_name;
        $user->last_name=$request->last_name;
        $user->country_id=$request->country_id;
        $user->postal_code=$request->postal_code;
        $user->dob=$request->dob;
        $user->connected_period=$request->connected_period;
        $user->gender=$request->gender;

        $familyTree=FamilyTree::where('user_id',$authuser->id)->first();
        $familyTree->first_name=$request->first_name;
        $familyTree->last_name=$request->last_name;
        $familyTree->dob=$request->dob;
        $familyTree->email=strtolower($request->email);
        $familyTree->gender=$request->gender;
        $familyTree->updated_at=Carbon::now();
        
        if( $user->save() && $familyTree->save()){
            DB::commit();
            Session::flash("errMsg","Profile Updated Successfully.");
        }
        else{
            DB::rollBack();
            Session::flash('errMsg',"Failed To Update Profile.Plase Try Again.");
        }

        return redirect()->back();
    }

    public function photo_store(Request $request){
        $folderPath ='user/photo/';
        $image_parts = explode(";base64,", $request->image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . uniqid() . '.' .$image_type;
        file_put_contents(Storage::disk('public')->path($file), $image_base64);
        $user = User::where('id',Auth::user()->id)->first();
        $user->photo= $file;
            if($user->save()){
                return response()->json(['success'=>true,'message'=>"Successfully save your profile photo"]);
            }
        return response()->json(['success'=>false,'message'=>"Something went wrong"]);   
    }



    public function view_update_password(){
        return view('frontend.update-password');  
    }

    public function update_password(Request $request){
        
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ];


        $messages = [
            'current_password.required' => 'Please Enter Current Password ',
            'new_password.required' => 'Please Enter New Password ',
            'new_password.min' => 'Minimum 8 Characters are Required for New Password',
            'new_password.confirmed' => 'New Password is not matched with Confirm Password',
            'new_password_confirmation.required' => 'Please Confirm the Password '
        ];

        $this->validate($request, $rules, $messages);


        $user = Auth::user();
        $currentPassword = $request->current_password;
        $newPassword = $request->new_password;
    
        if (Hash::check($currentPassword, $user->password)) {
            // The current password is correct
            $user->update([
                'password' => Hash::make($newPassword),
            ]);
    
            return redirect()->route('profile')->with('success', 'Password updated successfully');
        } else {
            // The current password is incorrect
            return back()->withErrors(['current_password' => 'The Current Password is incorrect']);
        }


    }
}
