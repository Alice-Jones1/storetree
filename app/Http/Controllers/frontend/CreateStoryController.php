<?php

namespace App\Http\Controllers\frontend;

use App\Models\Warmup;
use App\Helpers\Helper;
use App\Models\Category;
use App\Models\Question;
use App\Models\Story;
use App\Models\StoryItem;
use App\Models\StoryWarmupItem;
use App\Models\EditStory;
use App\Repositories\VideoParse;
use RealRashid\SweetAlert\Facades\Alert;
use Auth ;
use Facade\Ignition\Support\Packagist\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Storage;



class CreateStoryController extends BaseController
{
    public function __construct() {
        parent::__construct();
    }

    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function step1() {
        return view('frontend.story.step1');
    }

    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function show() {
        return view('frontend.story.show');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function step1Store(Request $request) {
        $rules = [
            'plan' => 'required',
        ];


        $messages = [
//            'title.required' => 'Title is required',
        ];

        $this->validate($request, $rules, $messages);

        $cart = Session::get('cart');
        $cart['plan'] = $request->get('plan');
        $cart['addon'] = $request->get('addon');
        Session::put('cart', $cart);
        return redirect()->route('create-your-story.step-2');
    }

    
    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function step2() {
        $cart = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $categories = Category::where('status', 1)->with('questions')->get();
        if (Auth::check()) {
           if (Auth::user()->plan_purchased != null) {
               $user = Auth::user()->load('stories');
               $stories = $user->stories->toArray();
               $latest_story = end($stories);
               $questions_ids = StoryItem::where('story_id',$latest_story['id'])->pluck('question_id')->toArray();
               return view('frontend.story.step2', compact('categories','questions_ids','cart'));      

            } else {
                return view('frontend.story.step2', compact('categories', 'cart'));
            }
        } else {
            return view('frontend.story.step2', compact('categories', 'cart'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function step2Store(Request $request) {

        $rules = [
            'questions' => 'array|min:1',
        ];


        $messages = [
//            'title.required' => 'Title is required',
        ];

        $this->validate($request, $rules, $messages);
        $cart = Session::get('cart');
        $cart['questions'] = $request->get('questions');
        Session::put('cart', $cart);
        return redirect()->route('create-your-story.step-3');
    }

    
    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function step3() {
        $cart = Session::get('cart');

        if(!$cart) return redirect()->route('create-your-story.step-1');

        if ($cart) {
            if(Auth::user() != null){
                if (Auth::user()->plan_purchased != null) {
                    if ($cart['plan'] < Auth::user()->plan_purchased) {
                        Session::forget('cart');
                        return redirect()->route('create-your-story.step-1')->with('error','You cannot Continue with the below plan of your purchased Plan, Please Start with Current Plan or Upgrade the Plan');                
                    }
                    else{
                        $warmups = Warmup::where('status', 1)->get();
                         $user = Auth::user()->load('stories');
                         $stories = $user->stories->toArray();
                         $latest_story = end($stories);
                         $warmups_ids = StoryWarmupItem::where('story_id',$latest_story['id'])->pluck('warmup_id')->toArray();
                         return view('frontend.story.step3', compact('warmups', 'cart','warmups_ids'));
                    }                 
                }
                else{
                    $warmups = Warmup::where('status', 1)->get();
                    return view('frontend.story.step3', compact('warmups', 'cart'));
                }
            }
            else{
               $warmups = Warmup::where('status', 1)->get();
               return view('frontend.story.step3', compact('warmups', 'cart'));
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function step3Store(Request $request) {

        $rules = [
            'warmups' => 'array|min:1',
        ];


        $messages = [
            // 'title.required' => 'Title is required',
        ];

        $this->validate($request, $rules, $messages);
        $cart = Session::get('cart');
        $cart['warmups'] = $request->get('warmups');
        Session::put('cart', $cart);
        $authuser = auth()->user();

        if($authuser) {
            return redirect()->route('create-your-story.step-4');
        }
        
        Session::put('redirect', route('create-your-story.step-3'));

        return redirect()->route('create-your-story.step-3', ['login' => 1]);
        
    }

    /**
     * Display the about-us page of the site
     *
     * @return \Illuminate\Http\Response
     */
    // full process warmup er jonno
    public function step4() {
        $cart = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $warmups = Warmup::whereIn('id', $cart['warmups'])->orderBy('sort', 'ASC')->get();
		$questions = Warmup::whereIn('id', $cart['warmups'])->orderBy('sort', 'ASC')->get();
        $storyItems         = collect(Session::get('storyItems'));
        $currentQuestion    = [];
        $current            = false;
        $totalStoryUploaded = 0;
		$cart = Session::get('cart');
        $cart['all_questions'] = $questions;
        $cart['all_warmups'] = $warmups;
        Session::put('cart',$cart);

        foreach($questions as $key => $question) {
            // first time dhukce
            if($storyItems) {                
                if(in_array($question->id, $storyItems->pluck('question_id')->toArray())) {
                    $question->class = 'qs_complete';
                    $totalStoryUploaded++;
                }
            }
            if(!$current) {
                if(!$question->class) {
                    $question->class    = 'qs_qurrent';
                    $currentQuestion    = $question;
                    $current            = true;
                    $totalStoryUploaded = 0;
                }
            }
        }

        // all done na hole dhukbe na
        if (count($questions) === $totalStoryUploaded) {
        	$cart = Session::get('cart');
            $cart['warmups'] = 0;
            $qsns = [];
            foreach ($questions as $question){
                $qsns[] = strval($question->id);
            }
            $cart['warmups'] = $qsns;
            Session::put('cart',$cart);
            //whose id?
            return redirect()->route('create-your-story.step-5', $questions[0]->id);
        }
        return view('frontend.story.step4', compact('cart', 'questions', 'currentQuestion', 'storyItems'));
    }

    public function step5() {
        $cart = Session::get('cart');
        // return $cart;
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $questions = Question::whereIn('id', $cart['questions'])->orderBy('sort', 'ASC')->get();
		$warmups = Warmup::whereIn('id', $cart['warmups'])->orderBy('sort', 'ASC')->get();
	    $storyItems         = collect(Session::get('storyItems'));
        $currentQuestion    = [];
        $current            = false;
        $totalStoryUploaded = 0;
		$cart = Session::get('cart');
        $cart['all_questions'] = $questions;
        $cart['all_warmups'] = $warmups;
        Session::put('cart',$cart);
        foreach($questions as $key => $question) {
            // first time dhukce
            if($storyItems) {
                
                if(in_array($question->id, $storyItems->pluck('question_id2')->toArray())) {
                    $question->class = 'qs_complete';
                    $totalStoryUploaded++;
                }
            }

            if(!$current) {
                if(!$question->class) {
                    $question->class    = 'qs_qurrent';
                    $currentQuestion    = $question;
                    $current            = true;
                    $totalStoryUploaded = 0;
                }
            }
        }

        // all done na hole dhukbe na
        if (count($questions) === $totalStoryUploaded) {
        	$cart = Session::get('cart');
            $cart['questions'] = 0;
            $qsns = [];
            foreach ($questions as $question){
                $qsns[] = strval($question->id);
            }
            $cart['questions'] = $qsns;
            Session::put('cart',$cart);
            return redirect()->route('create-your-story.step-6', $questions[0]->id);
        }
        return view('frontend.story.step5', compact('cart', 'questions', 'currentQuestion', 'storyItems'));
    }

    public function step4Preview($id) {
        $storyItems         = collect(Session::get('storyItems'));
        $cart = Session::get('cart');

        if(!$cart) return redirect()->route('create-your-story.step-1');
        $questions=$cart['all_warmups'];
        $storyItems = collect(Session::get('storyItems'));

        if (!$storyItems->where('question_id', $id)->count()) {
            abort(404);    
        }

        $currentQuestion = [];
        $current         = false;
        
        foreach($questions as $key=>$question) {
            if($storyItems) {
                if(in_array($question->id, $storyItems->pluck('question_id')->toArray())) {
                    $question->class = 'qs_complete';
                }
            }

            if(!$current) {
                if($question->id == $id) {
                    $question->class = 'qs_complete qs_qurrent';
                    $question->video = $storyItems->where('question_id', $id)->first()['video'];

                    $currentQuestion = $question; 
                    $current = true;
                }
            }

        }
        return view('frontend.story.step4', compact('cart', 'questions', 'currentQuestion', 'storyItems'));
    }

    public function step5Preview($id) {

        $storyItems         = collect(Session::get('storyItems'));
        $cart = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $questions=$cart['all_questions'];
        $storyItems = collect(Session::get('storyItems'));

        if (!$storyItems->where('question_id2', $id)->count()) {
            abort(404);    
        }
        $currentQuestion = [];
        $current         = false;
        
        foreach($questions as $key=>$question) {
            if($storyItems) {
                if(in_array($question->id, $storyItems->pluck('question_id2')->toArray())) {
                    $question->class = 'qs_complete';
                }
            }

            if(!$current) {
                if($question->id == $id) {
                    $question->class = 'qs_complete qs_qurrent';
                    $question->video = $storyItems->where('question_id2', $id)->first()['video'];
                    $currentQuestion = $question;
                    $current = true;
                }
            }
        }
        return view('frontend.story.step5', compact('cart', 'questions', 'currentQuestion', 'storyItems'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function step6(Request $request) {
    
        $cart       = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        
        $questions = $cart['all_questions'];
    	$warmups = $cart['all_warmups'];
        $storyItems = collect(Session::get('storyItems'));
        $addon = $cart['addon'];

        if ($addon == 1){
            $currentQuestion = [    "title" => "Add on",
                                    "sort" => 5,
                                    "status" => 1,
                                    "category_id" => 1,
                                    "admin_id" => 2,
                                    "created_at" => "2022-04-13 23:10:45",
                                    "updated_at" => "2022-04-13 23:10:45",
                                    "deleted_at" => null,
                                    "class" => "qs_qurrent"
                                ];
                return view('frontend.story.record-addon', compact('currentQuestion'));


        } else {
            
            if (isset($cart['addon_video'])) {
                $addon_charges = 5;
            } else {
                $addon_charges = 0;
            }
            return view('frontend.story.step6', compact('questions', 'storyItems','warmups','addon_charges','cart'));
        }
    }

    public function record_addon(){
        $cart       = Session::get('cart');
        $addon = $cart['addon'] = 1;
        if (isset($cart['addon_video'])){
          return redirect()->route('create-your-story.step-6');
        }
        else{
              $currentQuestion = [    "title" => "Add on",
                                    "sort" => 5,
                                    "status" => 1,
                                    "category_id" => 1,
                                    "admin_id" => 2,
                                    "created_at" => "2022-04-13 23:10:45",
                                    "updated_at" => "2022-04-13 23:10:45",
                                    "deleted_at" => null,
                                    "class" => "qs_qurrent"
                                ];
            return view('frontend.story.record-addon', compact('currentQuestion'));
        }
   }

    // -----------------Updated 20-09-2023 ------------

    public function editstep6($story_id){
        $story_id = decrypt($story_id);
     
        $warmups = StoryWarmupItem::where('story_id',$story_id)->get();
        $questions = StoryItem::where('story_id',$story_id)->get();
        $story = Story::find($story_id);

        return view('frontend.edit-story',compact('warmups','questions','story'));
    }

    public function update_story(Request $request){
        $story_title = $request->story;
        $storyItems = $request->data;
        $story_id = $request->story_id;        
        VideoParse::mergeupdatedChunkVideos($story_title,$story_id,$storyItems);        
        $message_text = 'Your Story has been Edited';
        return response()->json(['success' => true, 'msg' => $message_text]);
    }

    public function edit_step2($id){
    $id = decrypt($id);
        
            $story_id = $id;
            $story = Story::find($id);
            $package = $story->package ; 
            $questions = StoryItem::where('story_id',$id)->get();  
            $question_ids = [];
            foreach ($questions as  $question) {   
                array_push($question_ids,$question->question_id);
            }
            $categories = Category::where('status', 1)->with('questions')->get();
            return view('frontend.edit-questions', compact('story_id','categories','question_ids','package'));

    }

    public function edit_step2Store(Request $request){

        $story_item_array=[];
        $old_story_question = StoryItem::where('story_id',$request->story_id)->pluck('question_id')->toArray();
        
        foreach ($request->questions as $key => $question) {
            if (in_array($question,$old_story_question)) {
            $story_data =   StoryItem::where('story_id',$request->story_id)->where('question_id',$question)->first();
            $story_item_array_data = $question.'_-_storage/'.$story_data->video;
            array_push($story_item_array,$story_item_array_data);
            }
        }
        $storyItems1 = implode("-_-",$story_item_array);
        $edit_story_data = EditStory::where('story_id',$request->story_id)->first();
        $question_ids = implode(",",$request->questions);

        if($edit_story_data){
                $edit_story_data->question_ids = $question_ids ;
                $edit_story_data->storyitems1 = $storyItems1 ;
                $edit_story_data->save();
        }
        else{
                $new_edit_story_data = new EditStory;
                $new_edit_story_data->story_id = $request->story_id ;
                $new_edit_story_data->user_id = Auth::user()->id;
                $new_edit_story_data->storyitems1 = $storyItems1 ;
                $new_edit_story_data->question_ids = $question_ids ;
                $new_edit_story_data->save();
        }


        $story_id = $request->story_id ;
        $allwarmups = Warmup::where('status',1)->get();
        $warmup_ids = StoryWarmupItem::where('story_id',$story_id)->pluck('warmup_id')->toArray();

        return view('frontend.edit-warmups', compact('story_id','allwarmups','warmup_ids'));
    }


    public function edit_step3Store(Request $request){
        $story_item_array=[];
        $old_story_warmup = StoryWarmupItem::where('story_id',$request->story_id)->pluck('warmup_id')->toArray();
        
        foreach ($request->warmups as $key => $warmup) {
            if (in_array($warmup,$old_story_warmup)) {
            $story_data =   StoryWarmupItem::where('story_id',$request->story_id)->where('warmup_id',$warmup)->first();
            $story_item_array_data = $warmup.'_-_storage/'.$story_data->video;
            array_push($story_item_array,$story_item_array_data);
            }
        }

        $storyItems2 = implode("-_-",$story_item_array);

        $edit_story_data = EditStory::where('story_id',$request->story_id)->first();
        $warmup_ids = implode(",",$request->warmups);    


        $story_id = $request->story_id ;

        if($edit_story_data){
                $edit_story_data->warmup_ids = $warmup_ids ;
                $edit_story_data->storyitems2 = $storyItems2 ;
                $edit_story_data->save();
        }
        else{
                $new_edit_story_data = new EditStory;
                $new_edit_story_data->story_id = $request->story_id ;
                $new_edit_story_data->storyitems2 = $storyItems2;
                $new_edit_story_data->user_id = Auth::user()->id;
                $new_edit_story_data->warmup_ids = $warmup_ids ;
                $new_edit_story_data->save();
        }

        $questions = Warmup::whereIn('id', $request->warmups)->orderBy('sort', 'ASC')->get();
        $storyItems = [];

        $warmup_storyItems  = EditStory::where('story_id',$story_id)->first();
        if ($warmup_storyItems->storyitems2 != "") {
        $warmup_items = explode('-_-',$warmup_storyItems->storyitems2);
        
            foreach ($warmup_items as $key => $value) {
                $old_story_items_data = explode('_-_', $value);
            
                $storyItems[] = [
                    "question_id" => $old_story_items_data[0],
                    "video" => $old_story_items_data[1],
                ];
            }

        }
            $currentQuestion    = [];
            $current            = false;
            $totalStoryUploaded = 0;



        $checkstoryItem = [];
            foreach ($storyItems as $singlestoryItem) {
                if (isset($singlestoryItem["question_id"])) {
                    $checkstoryItem[] = $singlestoryItem["question_id"];
                }
            }

            foreach($questions as $key => $question) {
                // first time dhukce
                if($storyItems) {
                    if(in_array($question->id, $checkstoryItem)) {
                        $question->class = 'qs_complete';
                        $totalStoryUploaded++;
                    }
                }

                if(!$current) {
                    if(!$question->class) {
                        $question->class    = 'qs_qurrent';
                        $currentQuestion    = $question;
                        $current            = true;
                        $totalStoryUploaded = 0;
                    }
                }
            }



            if (count($questions) === $totalStoryUploaded) {
                
                $all_questions_ids = $questions->pluck('id')->toArray();
                return redirect()->route('create-your-story.edit_warmup.show',['id' => $all_questions_ids[0],'story_id' => encrypt($story_id)]);
            }
            
        return view('frontend.record_edit_warmup', compact('story_id','questions', 'currentQuestion', 'storyItems'));
    }



    public function edit_step4Preview($id,$story_id){
    $story_id = decrypt($story_id);
        $storyItems = [];

        $warmup_storyItems  = EditStory::where('story_id',$story_id)->first();
        $warmup_items = explode('-_-',$warmup_storyItems->storyitems2);
        
            foreach ($warmup_items as $key => $value) {
                $old_story_items_data = explode('_-_', $value);
            
                $storyItems[] = [
                    "question_id" => $old_story_items_data[0],
                    "video" => $old_story_items_data[1],
                ];
            }
        $questions = Warmup::whereIn('id',explode(',',$warmup_storyItems->warmup_ids))->orderBy('sort', 'ASC')->get();

            $currentQuestion = [];
            $current         = false;

            $checkstoryItem = [];
            foreach ($storyItems as $singlestoryItem) {
                if (isset($singlestoryItem["question_id"])) {
                    $checkstoryItem[] = $singlestoryItem["question_id"];
                }
            }


            foreach($questions as $key=>$question) {
                if($storyItems) {
                    if(in_array($question->id, $checkstoryItem)) {
                        $question->class = 'qs_complete';
                    }
                }

                if(!$current) {
                    if($question->id == $id) {
                        $question->class = 'qs_complete qs_qurrent';

                        foreach ($storyItems as $item) {
                            if ($item["question_id"] == $id) {
                                $question->video = $item["video"];
                                break; // Stop the loop once the desired item is found
                            }
                        }
                        $currentQuestion = $question; 
                        $current = true;
                    }
                }
            }
        
            return view('frontend.record_edit_warmup', compact('questions', 'currentQuestion', 'storyItems','story_id'));
    }


    public function editstep5($story_id){
    $storyItems = [];
        $question_storyItems  = EditStory::where('story_id',$story_id)->first();
        $questions = Question::whereIn('id', explode(',',$question_storyItems->question_ids))->orderBy('sort', 'ASC')->get();

    if ($question_storyItems->storyitems1 != "") {
        $question_items = explode('-_-',$question_storyItems->storyitems1);
            foreach ($question_items as $key => $value) {
                $old_story_items_data = explode('_-_', $value);
            
                $storyItems[] = [
                    "question_id" => $old_story_items_data[0],
                    "video" => $old_story_items_data[1],
                ];
            }
        }

            $checkstoryItem = [];
            foreach ($storyItems as $singlestoryItem) {
                if (isset($singlestoryItem["question_id"])) {
                    $checkstoryItem[] = $singlestoryItem["question_id"];
                }
            }



            $currentQuestion    = [];
            $current            = false;
            $totalStoryUploaded = 0;

            foreach($questions as $key => $question) {
                if($storyItems) {
                    
                    if(in_array($question->id, $checkstoryItem)) {
                        $question->class = 'qs_complete';
                        $totalStoryUploaded++;
                    }
                }

                if(!$current) {
                    if(!$question->class) {
                        $question->class    = 'qs_qurrent';
                        $currentQuestion    = $question;
                        $current            = true;
                        $totalStoryUploaded = 0;
                    }
                }
            }
        if (count($questions) === $totalStoryUploaded) {            
                $all_questions_ids = $questions->pluck('id')->toArray();
                return redirect()->route('create-your-story.edit_question.show',['id' => $all_questions_ids[0],'story_id' => encrypt($story_id)]);
            }
            return view('frontend.record_edit_question', compact('questions', 'currentQuestion', 'storyItems','story_id'));
    }


    public function edit_step5Preview($id,$story_id){
        $story_id = decrypt($story_id);
        $storyItems = [];

        $question_storyItems  = EditStory::where('story_id',$story_id)->first();
        $question_items = explode('-_-',$question_storyItems->storyitems1); 
        foreach ($question_items as $key => $value) {
            $old_story_items_data = explode('_-_', $value);         
                $storyItems[] = [
                    "question_id" => $old_story_items_data[0],
                    "video" => $old_story_items_data[1],
                ];
        }

        $questions = Question::whereIn('id',explode(',',$question_storyItems->question_ids))->orderBy('sort', 'ASC')->get();
        $currentQuestion = [];
        $current         = false;
        $checkstoryItem = [];
        foreach ($storyItems as $singlestoryItem) {
            if (isset($singlestoryItem["question_id"])) {
                $checkstoryItem[] = $singlestoryItem["question_id"];
            }
        }

        foreach($questions as $key=>$question) {
            if($storyItems) {
                if(in_array($question->id, $checkstoryItem)) {
                    $question->class = 'qs_complete';
                }
            }

            if(!$current) {
                if($question->id == $id) {
                    $question->class = 'qs_complete qs_qurrent';

                    foreach ($storyItems as $item) {
                        if ($item["question_id"] == $id) {
                            $question->video = $item["video"];
                            break; // Stop the loop once the desired item is found
                        }
                    }
                    $currentQuestion = $question; 
                    $current = true;
                }
            }
        }
        return view('frontend.record_edit_question', compact('questions', 'currentQuestion', 'storyItems','story_id'));
    }

    public function api_hit($video){

        $headerss = array('x-api-key:ABrmaqtYxw5yxq2fFBNwYAW7Px7Am5LCbhFhqdp8',
                            'Content-Type:application/json'                 
                        );
        $video_api = 'https://api.shotstack.io/stage/render/'.$video;
        $finalresponse = Curl::to($video_api)
        ->withHeaders($headerss)
        ->get();  
        $fdecoded_json_data = json_decode($finalresponse, true);
        return  $fdecoded_json_data;

    }

    public function get_story_video(Request $request){

        $video = $request->data['video'];
        $api_status = $this->api_hit($video);    
        if ($api_status['response']['status'] == "done") {
            $video_url = $api_status['response']['url'];
            $desiredFile = 'merged-video/' . Auth::user()->id . '/' . date('Y-m-d') . '/' . rand(99999, 9999999) . time() . '.mp4';
            $fileContents = file_get_contents($video_url);
            if ($fileContents !== false) {
                    Storage::disk('public')->put($desiredFile, $fileContents);
                    $story = Story::where('user_id',auth()->user()->id)->orderBy('id', 'desc')->first();
                    $story->video = $desiredFile;
                    $story->status = 1;
                    $story->save();
            }

            return response()->json(['success'=>true,'message'=>'Video is Rendered Successfully','video'=>$desiredFile]);
        } else {
            return response()->json(['success'=>false,'message'=>'Video is not Rendered Yet Please Wait']);
        }
    }

}
