<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Warmup;
use App\Models\StoryWarmupItem;
use App\Models\StoryItem;
use App\Models\EditStory;
use App\Models\AddonVideo;
use Storage;
use Illuminate\Support\Facades\Auth;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Support\Facades\Session;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class VideoRecordingController extends Controller
{
    protected function word_chunk($str, $len = 76, $end = "\n")
    {
        $pattern = '~.{1,' . $len . '}~u'; // like "~.{1,76}~u"
        $str = preg_replace($pattern, '$0' . $end, $str);
        return rtrim($str, $end);
    }

    public function store(Request $request)
    {
        $request->validate([
            'video' => "required|mimetypes:video/*",
            'question_id' => "required"
        ]);

        if($request->warmup=="true"){
            $question   = Warmup::findOrFail($request->question_id);
        }
        else{
            $question   = Question::findOrFail($request->question_id);
        }

        $size =  $request->file('video')->getSize(); // in bytes


        $storyItems = (array) Session::get('storyItems');
        $video_storage_link = "chunk-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";

        $text = $this->word_chunk($question->title);

        $command = "text='$text': fontcolor=black: fontsize=16: box=1: boxcolor=white@0.4: boxborderw=6: x=(w-text_w)/2:y=h-th-70:";

        FFMpeg::open($request->file('video'))
        ->addFilter(function (VideoFilters $filters) use ($command) {
            return $filters->custom("drawtext=$command");
        })
            ->export()
            ->toDisk('public')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($video_storage_link);


        $isNewItem = true;
        foreach ($storyItems as $key => $storyItem) {
            if($request->warmup == "true" && isset($storyItem['question_id'])){
                if ($storyItem['question_id'] == $request->question_id) {
                    $storyItems[$key]['video'] = "storage/" . $video_storage_link;
    
                    $isNewItem = false;
                    break;
                }
            }
            if($request->warmup=="false" && isset($storyItem['question_id2'])){
                if ($storyItem['question_id2'] == $request->question_id) {
                    $storyItems[$key]['video'] = "storage/" . $video_storage_link;
    
                    $isNewItem = false;
                    break;
                }
            }
        }


        if ($isNewItem) {
            if($request->warmup=="true"){
                $storyItems[] = [
                    'question_id' => $request->question_id,
                    'video' => "storage/" . $video_storage_link
                ];
            }
            if($request->warmup=="false"){
                $storyItems[] = [
                    'question_id2' => $request->question_id,
                    'video' => "storage/" . $video_storage_link
                ];
            }
        }

        Session::put('storyItems', $storyItems);

        return response()->json([
            "msg" => $request->file('video')
        ], 200);
    }


    public function store_Rerecord(Request $request){
        
        $request->validate([
            'video' => "required|mimetypes:video/*",
            'question_id' => "required"
        ]);
        
        if($request->warmup=="true"){
            $question   = Warmup::findOrFail($request->question_id);
            $storyItem = StoryWarmupItem::where('story_id',$request->stid)->where('warmup_id',$request->question_id)->first();

        }
        else{
            $question   = Question::findOrFail($request->question_id);
            $storyItem = StoryItem::where('story_id',$request->stid)->where('question_id',$request->question_id)->first();

        }

        $video_storage_link = "chunk-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";

        $text = $this->word_chunk($question->title);
         $command = "text='$text': fontcolor=black: fontsize=16: box=1: boxcolor=white@0.4: boxborderw=6: x=(w-text_w)/2:y=h-th-70:";

        FFMpeg::open($request->file('video'))
        //// for live
        ->addFilter(function (VideoFilters $filters) use ($command) {
            return $filters->custom("drawtext=$command");
        })
            ->export()
            ->toDisk('public')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($video_storage_link);
        
        $editstoryItem = EditStory::where('story_id',$request->stid)->first();

        
        if ($storyItem) {
            $storyItem->video = $video_storage_link;
            $storyItem->save();
        } else {
           if ($request->warmup=="true") {
            $storyItem_ids = StoryWarmupItem::where('story_id',$request->stid)->pluck('warmup_id')->toArray();
            $diff = array_diff($storyItem_ids,explode(',',$editstoryItem->warmup_ids));
            foreach ($diff as $key => $value) {
                if ($request->question_id != $value) {
                     $newstoryItem = StoryWarmupItem::where('story_id',$request->stid)->where('warmup_id',$value)->first();
                     $newstoryItem->warmup_id = $request->question_id;
                     $newstoryItem->video = $video_storage_link;
                     $newstoryItem->save();
                }
                break;
            }
           } else {
            $storyItem_ids = StoryItem::where('story_id',$request->stid)->pluck('question_id')->toArray();
            $diff = array_diff($storyItem_ids,explode(',',$editstoryItem->question_ids));
            foreach ($diff as $key => $value) {
                if ($request->question_id != $value) {
                     $newstoryItem = StoryItem::where('story_id',$request->stid)->where('question_id',$value)->first();
                     $newstoryItem->question_id = $request->question_id;
                     $newstoryItem->video = $video_storage_link;
                     $newstoryItem->save();

                     $story_item_array=[];
                     $old_story_question = StoryItem::where('story_id',$request->stid)->pluck('question_id')->toArray();

                     foreach ($old_story_question as $key => $question) {
                           $story_data =   StoryItem::where('story_id',$request->stid)->where('question_id',$question)->first();
                           $story_item_array_data = $question.'_-_storage/'.$story_data->video;
                           array_push($story_item_array,$story_item_array_data);
                     }

                     $storyItems1 = implode("-_-",$story_item_array);
                     $edit_story_data = EditStory::where('story_id',$request->stid)->first();
                     $edit_story_data->storyitems1 = $storyItems1 ;
                     $edit_story_data->save();
                }
                break;
            }
           }           
        }

            return response()->json([
                "msg" => 'Successfully uploaded.'
            ], 200);

    }






public function storeaddon(Request $request)
    {
        
        $cart       = Session::get('cart');
        $cart['addon'] = 0;

        $request->validate([
            'video' => "required|mimetypes:video/*",
        ]);

        $size =  $request->file('video')->getSize(); // in bytes

        $video_storage_link = "temp-addon-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";
    
        FFMpeg::open($request->file('video'))
            ->export()
            ->toDisk('public')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($video_storage_link);


            
        $cart['addon_video'] = $video_storage_link;
        Session::put('cart', $cart);
        return response()->json([
            "msg" => 'Successfully uploaded.'
        ], 200);
    }



    public function editaddon($id){
       $addon_video = AddonVideo::find($id);

        $storyItem = array("question_id" =>  1,
                    "video" => $addon_video['video']
                );

        $current = false;

        $question =array(   "id" => 1,
                    "title" => "Addon",
                    "sort" => 5,
                    "status" => 1,
                    "category_id" => 1,
                    "admin_id" => 2,
                    "created_at" => "2022-04-13 23:10:45",
                    "updated_at" => "2022-04-13 23:10:45",
                    "deleted_at" => null,
                    "class" => "qs_complete"
                );


        if(!$current) {
            if( $question['id'] == $storyItem['question_id']) {
                $question['class'] = 'qs_complete qs_qurrent';
                $question['video'] = 'storage/'.$addon_video['video'];
                $currentQuestion = $question;
                $current = true;
            }
        }

        $video_id = $id;
        return view('frontend.story.edit_addon', compact( 'question', 'currentQuestion', 'storyItem','video_id'));
}


public function storeeditAddon(Request $request)
    {

      $request->validate([
        'video' => "required|mimetypes:video/*",
      ]);

    $video_storage_link = "addon-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";
    
    FFMpeg::open($request->file('video'))
        ->export()
        ->toDisk('public')
        ->inFormat(new \FFMpeg\Format\Video\X264)
        ->save($video_storage_link);

        $add_on = AddonVideo::find($request->video_id);
        $add_on->video = $video_storage_link;
        $add_on->save();
        
        
        return response()->json([
            "msg" => 'Successfully uploaded.',
            "add_on" => true
        ], 200);
    }
}


