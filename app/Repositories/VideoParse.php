<?php
namespace App\Repositories;

use App\Models\Story;
use App\Models\StoryItem;
use App\Models\StoryWarmupItem;
use App\Helpers\Helper;
use App\Models\Question;
use App\Models\Warmup;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Response;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use App\Models\FamilyTree;
use Ixudra\Curl\Facades\Curl;


class VideoParse
{
    public static function mergeChunkVideos(int $payment_log_id, $cart, $storyItems)
    {        

    	$cart = Session::get('cart');
        
        $questions = $cart['all_questions']; 
        $warmups= $cart['all_warmups'];
        $createStoryItems = [];
        $createStoryWarmupItems = []; 

        $warmups  = Warmup::whereIn('id', $cart['warmups'])->orderBy('sort', 'ASC')->get();
        $questions  = Question::whereIn('id', $cart['questions'])->orderBy('sort', 'ASC')->get();


        foreach ($warmups as $key => $warmup){
            $story = $storyItems->where('question_id', $warmup->id)->first();
             $createStoryWarmupItems[$key]['video'] = str_replace('storage/', "", $story['video']);
             $createStoryWarmupItems[$key]['warmup_id'] = $warmup->id;
        }


        foreach ($questions as $key => $question){
             $story = $storyItems->where('question_id2', $question->id)->first();
             $createStoryItems[$key]['video'] = str_replace('storage/', "", $story['video']);
             $createStoryItems[$key]['question_id'] = $question->id;
        }


        $data =  
        '{"timeline": {
              "tracks": [
                {
                    "clips": [
                      {
                        "asset": {
                          "type": "video",
                          "src": "https://storeetree.com/storage/formatted/mainchunks/in.mp4"
                        },
                        "start" : 0,
                        "length" : 7
                      }
                    ]
                  }                 
                ]
            },
            "output": {
              "format": "mp4",
              "resolution": "sd"
            }
        }';
       
      $dataArray = json_decode($data, true);
  

      $start = 7;
      $memory_num = 0;

        foreach ($storyItems as $key => $storyItem) {

                

              if (isset($storyItem['question_id'])) {
                 $question_video = 'warmup/'.$storyItem['question_id'].'.m4v';
              }
              if (isset($storyItem['question_id2'])) {
                 $question_video = 'question/'.$storyItem['question_id2'].'.m4v';
              }

            $duration =  FFMpeg::fromDisk('public')
            ->open($question_video)
            ->getDurationInSeconds();

            // $duration = intval(gmdate("s", $duration));
  
            $questionclip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$question_video,
                                    ],
                                    "start" => $start,
                                    "length" => $duration,
                                ]
                            ]
                        ];

            $dataArray["timeline"]["tracks"][] = $questionclip;
           
            $start += $duration;


            $video = str_replace('storage/', '', $storyItem['video']);
            $duration =  FFMpeg::fromDisk('public')
            ->open($video)
            ->getDurationInSeconds();

            // $duration = intval(gmdate("s", $duration));


  
            $newClip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$video,
                                    ],
                                    "start" => $start,
                                    "length" => $duration,
                                ]
                            ]
                        ];
            $dataArray["timeline"]["tracks"][] = $newClip;
           
            $start += $duration;



            if ($cart['plan'] != 1) { 
                    if ($cart['plan'] == 2) {     
                        if (($key+1)%3 == 0) {
                            $decade_video = 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4';
                              $decade_duration =  FFMpeg::fromDisk('public')
                                                  ->open($decade_video)
                                                 ->getDurationInSeconds();
                                    // $decade_duration = intval(gmdate("s", $decade_duration));  
                                    $decade_clip = [ 
                                                    "clips" => 
                                                    [
                                                        [       "asset" => [
                                                                "type" => "video",
                                                                "src" => 'https://storeetree.com/storage/'.$decade_video,
                                                        ],
                                                            "start" => $start,
                                                            "length" => $decade_duration,
                                                        ]
                                                    ]
                                                ];

                                    $dataArray["timeline"]["tracks"][] = $decade_clip;           
                                    $start += $decade_duration;
                                    $memory_num++ ;
                        }
                    }

                    if ($cart['plan'] == 3) {
                        if (($key+1)%4 == 0) {
                            $decade_video = 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4';

                              $decade_duration =  FFMpeg::fromDisk('public')
                                                  ->open($decade_video)
                                                 ->getDurationInSeconds();
                                    // $decade_duration = intval(gmdate("s", $decade_duration));  
                                    $decade_clip = [ 
                                                    "clips" => 
                                                    [
                                                        [       "asset" => [
                                                                "type" => "video",
                                                                "src" => 'https://storeetree.com/storage/'.$decade_video,
                                                        ],
                                                            "start" => $start,
                                                            "length" => $decade_duration,
                                                        ]
                                                    ]
                                                ];

                                    $dataArray["timeline"]["tracks"][] = $decade_clip;           
                                    $start += $decade_duration;
                                    $memory_num++ ;
                        }
                        
                    }
                }
            } 

                   $outro_video = 'formatted/mainchunks/in.mp4';
                              $outro_videoduration =  FFMpeg::fromDisk('public')
                                                  ->open($outro_video)
                                                 ->getDurationInSeconds();
                                    // $outro_videoduration = intval(gmdate("s", $outro_videoduration));  

            $closeclip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$outro_video,
                                    ],
                                    "start" => $start,
                                    "length" => $outro_videoduration,
                                ]
                            ]
                        ];

            $dataArray["timeline"]["tracks"][] = $closeclip;
           
            $start += $outro_videoduration;

        $updatedData = json_encode($dataArray,JSON_PRETTY_PRINT);
       

        $headers = array('Content-Type:application/json',
                'Accept:application/json',
                'x-api-key:ABrmaqtYxw5yxq2fFBNwYAW7Px7Am5LCbhFhqdp8',
                'Host:api.shotstack.io'
               );


        $response = Curl::to('https://api.shotstack.io/stage/render')
        ->withHeaders($headers)
        ->withData($updatedData)
        ->post();

        $decoded_json_data = json_decode($response, true);
          
        $video_id = $decoded_json_data['response']['id'];


       //  $aws_video = static::get_video($video_id,$payment_log_id);


     
       //  $url = $aws_video;

       // $desiredFile = 'merged-video/' . Auth::user()->id . '/' . date('Y-m-d') . '/' . rand(99999, 9999999) . time() . '.mp4';

       //  $fileContents = file_get_contents($url);

       //  if ($fileContents !== false) {
       //      // Save the downloaded file to the desired location
       //      Storage::disk('public')->put($desiredFile, $fileContents);

            
       //  } else {
       //      dd("Failed to download the file.");
       //  }

       

        $all_title = [];

        foreach ($warmups as $key => $warmup){
            array_unshift($all_title,$warmup['title']);
        }
        foreach ($questions as $key => $question){
            array_unshift($all_title,$question['title']);
        }
        $questionss = implode('---',$all_title);
         $story = Story::create([
                                    'photo' => Auth::user()->photo,
                                    'video' => $video_id,
                                    'status' => 0,
                                    'package' => $cart['plan'],
                                    'payment_log_id' => $payment_log_id,
                                    'user_id' => auth()->user()->id,
                                    'all_questions' => $questionss
                                ]);

        $story->storyItems()->createMany($createStoryItems);
        $story->storyWarmupItems()->createMany($createStoryWarmupItems);   

        Session::forget(['cart', 'storyItems']);
        // return $aws_video;




    
        // //$questions  = Question::whereIn('id', $cart['questions'])->orderBy('sort', 'ASC')->get();

        // // Checking if any extra story uploaded then remove them.
        // // $extraStory = array_diff($storyItems->pluck('question_id')->toArray(), $cart['questions']);
        // // if ($extraStory) {
        // //     Helper::bulkMediaDelete($storyItems->whereIn('question_id', $extraStory)->toArray());
        // //     $storyItems = collect(Session::get('storyItems'));
        // // }

        // // $createStoryWarmupItems = array_map(function ($item){
        // //     return ['warmup_id' => $item];
        // // }, $cart['warmups']);
        // $mergeableVideos  = [];
        // $createStoryItems = [];
        // $createStoryWarmupItems = [];
		// $all_title = [];

        // foreach ($warmups as $key => $warmup){
        //     $story = $storyItems->where('question_id', $warmup->id)->first();
        //     // if ($key == 0) {
        //     //     $mergeableVideos[] = $createStoryWarmupItems[$key]['video'] = str_replace('storage/', "", "formatted/mainchunks/intro.mp4");
        //     // }
        //     $mergeableVideos[] = $createStoryWarmupItems[$key]['video'] = str_replace('storage/', "", $story['video']);
        //     // $createStoryWarmupItems[$key]['question_id'] = $question->id;
        //     $createStoryWarmupItems[$key]['warmup_id'] = $warmup->id;
        // 	array_unshift($all_title,$warmup['title']);
        // }
        //         $memory_num = 0;

        // foreach ($questions as $key => $question){
        //     $story = $storyItems->where('question_id2', $question->id)->first();
        //     $mergeableVideos[] = $createStoryItems[$key]['video'] = str_replace('storage/', "", $story['video']);
        //     // if($cart['plan'] != 1){
        //     //     if($cart['plan'] == 2){
        //     //         if($key%2 == 1){
                        
        //     //             if(!(in_array(Auth::user()->connected_period, array(1,12,13)))){                        
        //     //                 $mergeableVideos[] = $createStoryItems[$key]['video'] = str_replace('storage/', "", 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4');
        //     //                 $memory_num = $memory_num + 1;
        //     //            }
        //     //         }
        //     //     }
        //     //     if($cart['plan'] == 3){
        //     //         if($key%3 == 1){
        //     //             if(!(in_array(Auth::user()->connected_period, array(1,12,13)))){
        //     //             $mergeableVideos[] = $createStoryItems[$key]['video'] = str_replace('storage/', "", 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4');
        //     //             $memory_num = $memory_num + 1;
        //     //         }
        //     //     }
        //     //     }
        //     // }
        //     // if (count($questions)-1 == $key) {
        //     //     $mergeableVideos[] = $createStoryItems[$key]['video'] = str_replace('storage/', "", "mainchunks/outro.mp4");
        //     // }
        //     $createStoryItems[$key]['question_id'] = $question->id;
        // 	array_unshift($all_title,$question['title']);
        // }
        
        // $input_audio  = static::getAudioMusic();
        // //$video_storage_link = "merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";
        // $video_storage_link = "merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() ."final.mp4";
        // static::addBackgroundMusic($video_storage_link, $input_audio, $mergeableVideos);
        
        // //$tmp_video_storage_link = "merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() ."final.mp4";

		// $questionss = implode('---',$all_title);
        // $story = Story::create([
        //     'photo' => Auth::user()->photo,
        //     'video' => $video_storage_link,// $tmp_video_storage_link,
        //     'package' => $cart['plan'],
        //     'payment_log_id' => $payment_log_id,
        //     'user_id' => auth()->user()->id,
        // 	'all_questions' => $questionss
        // ]);
        // $story->storyItems()->createMany($createStoryItems);
        // $story->storyWarmupItems()->createMany($createStoryWarmupItems);        

        //     //WE DON'T REQUIRE AS IT IS JUST COPYING $video_storage_link TO $tmp_video_storage_link WITH NO CHANGE
        //     // FFMpeg::fromDisk('public')
        //     // ->open($video_storage_link)
        //     // ->export()
        //     // ->save($tmp_video_storage_link);

        // Session::forget(['cart', 'storyItems']);
        // //unlink(Storage::disk('public')->path($video_storage_link));

    
     }


public static function saveAddon($temp_addon){
// dd($temp_addon);
    $video_storage_link = "addon-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";
    
    FFMpeg::fromDisk('public')
            ->open($temp_addon)
            ->export()
            ->toDisk('public')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($video_storage_link);

            return $video_storage_link;
}

    public static function getAudioMusic()
    {
        $files = Storage::disk('public')->files('audio');
        $family_tree = FamilyTree::with('user')->find(Auth::user()->id);
        $connected_period = $family_tree->user->connected_period;
        $audio_index = array_search("audio/".$connected_period.".mp3", $files);
        $audio = Storage::disk('public')->path($files[$audio_index]);
        return $audio;
    }

    public static function mergeVideo(array $mergeableVideos)
    {
        // dd('rignt',$mergeableVideos);
        
        // Start Merging Chunk Videos ----------------------
        
            $tmp_video_storage_link = "tmp-merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() . ".mp4";

            FFMpeg::fromDisk('public')
                ->open($mergeableVideos)
                ->export()
                ->concatWithoutTranscoding()
                ->save($tmp_video_storage_link);

        // End Merging Chunk Videos ---------------------- 



        // Start Adding Logo to Video ----------------------
 
        
            // $overlayImagePath = Storage::disk('public')->path('logo/new_logo_1.png');

            // $logo_video_storage_link = "tmp-merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() ."logo.mp4";
            
            // $video = FFMpeg::fromDisk('public')->open($tmp_video_storage_link);
            // $image = imagecreatefrompng($overlayImagePath);
            // $overlayWidth = imagesx($image);
            // $overlayHeight = imagesy($image);
            // $overlayDuration = 1440;
            // $endTime = TimeCode::fromSeconds(0);
            // $startTime = TimeCode::fromSeconds(-$overlayDuration);
            // $videoFilter = "movie={$overlayImagePath} [watermark]; [in][watermark] overlay=10:10:enable='between(t,0,{$overlayDuration})' [out]";
            // $video->filters()->custom($videoFilter, array('watermark'), 'out');



            // -------------- Changing Resolution to (3840x2160) ------------------

            // $video->addFilter(function (VideoFilters $filters)  {
            //     $filters->resize(new \FFMpeg\Coordinate\Dimension(3840, 2160));
            // });


            // -------------- Changing FrameRate to (60) ------------------

            // $video->addFilter(function (VideoFilters $filters) use ($frameRate) {
            //     $filters->framerate($frameRate,60);
            // });


            // $video->export()
            // ->toDisk('public')
            // ->inFormat(new \FFMpeg\Format\Video\X264)
            // ->save($logo_video_storage_link);

        // End Adding Logo to Video ----------------------


        // Start Merging Animations Videos with Story Video ----------------------


            // $final_video_storage_link = "tmp-merged-video/" . Auth::user()->id . "/" . 
            // date('Y-m-d') . "/" . rand(99999, 9999999) . time() ."final.mp4";

            // $head_video = 'mainchunks/head.mp4';
            // $tail_video = 'mainchunks/tail.mp4';
            // $new_merge = [$head_video,$logo_video_storage_link,$tail_video];
     
            // FFMpeg::fromDisk('public')
            // ->open($new_merge)
            // ->export()
            // ->concatWithoutTranscoding()
            // ->save($final_video_storage_link);

          
        // End Merging Animations Videos with Story Video ----------------------
        
        return Storage::disk('public')->path($tmp_video_storage_link);
    
    }

    public static function addBackgroundMusic($output_video, $input_audio, array $mergeableVideos)
    {

        $tmp_video_storage_link = static::mergeVideo($mergeableVideos);
        
        $dir_name               = pathinfo($output_video)['dirname'];

        $output_video_path      = Storage::disk('public')->path($output_video);

        if(!Storage::disk('public')->exists($dir_name)){
            Storage::disk('public')->makeDirectory($dir_name);
        }

        //THIS IS SETTING AUDIO WITH VIDEO FILTERS , TO CHECK IF VIDEO IS SHORTER THAN AUDIO 
        shell_exec(env('FFMPEG_BINARIES') . ' -i ' . $tmp_video_storage_link . ' -stream_loop -1 -i ' . $input_audio . ' -c:v copy -filter_complex "[0:a]aformat=fltp:44100:stereo,apad[0a];[1]aformat=fltp:44100:stereo,volume=0.08[1a];[0a][1a]amerge[a]" -map 0:v -map "[a]" -ac 2 -shortest ' . $output_video_path);


        // //No Audio SETTING or VIDEO FILTERS 
        // shell_exec(env('FFMPEG_BINARIES') . ' -i ' . $tmp_video_storage_link . ' -c:v copy ' . $output_video_path);


        // //Updated Audio SETTING or VIDEO FILTERS  (08/09/2023)
         // shell_exec(env('FFMPEG_BINARIES') . ' -i ' . $tmp_video_storage_link . ' -stream_loop -1 -i ' . $input_audio . ' -c:v copy -filter_complex "[0:a]aformat=fltp:44100:stereo[0a];[1]aformat=fltp:44100:stereo,volume=0.08[1a];[1a]adelay=10000|10000[1a_sync];[0a][1a_sync]amerge[a]" -map 0:v -map "[a]" -ac 2 -shortest ' . $output_video_path);

        
        //unlink($tmp_video_storage_link);
    }

    public static function mergeupdatedChunkVideos($story_title,$story_id,$storyItems){


 $data =  
        '{"timeline": {
              "tracks": [
                {
                    "clips": [
                      {
                        "asset": {
                          "type": "video",
                          "src": "https://storeetree.com/storage/formatted/mainchunks/in.mp4"
                        },
                        "start" : 0,
                        "length" : 7
                      }
                    ]
                  }                 
                ]
            },
            "output": {
              "format": "mp4",
              "resolution": "sd"
            }
        }';
       
      $dataArray = json_decode($data, true);
  

      $start = 7;
        $all_title = [];
            $memory_num = 0;

        foreach ($storyItems as $key => $storyItem) {


              if (isset($storyItem['question_id'])) {
                 $question_video = 'warmup/'.$storyItem['question_id'].'.m4v';
                 $question_title = Warmup::find($storyItem['question_id']);
                 array_unshift($all_title,$question_title['title']);

              }
              if (isset($storyItem['question_id2'])) {
                 $question_video = 'question/'.$storyItem['question_id2'].'.m4v';
                 $question_title = Question::find($storyItem['question_id2']);
                 array_unshift($all_title,$question_title['title']);
              }

            $duration =  FFMpeg::fromDisk('public')
            ->open($question_video)
            ->getDurationInSeconds();

            // $duration = intval(gmdate("s", $duration));
  
            $questionclip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$question_video,
                                    ],
                                    "start" => $start,
                                    "length" => $duration,
                                ]
                            ]
                        ];

            $dataArray["timeline"]["tracks"][] = $questionclip;
           
            $start += $duration;



            $video = str_replace('storage/', '', $storyItem['video']);
            $duration =  FFMpeg::fromDisk('public')
            ->open($video)
            ->getDurationInSeconds();

            // $duration = intval(gmdate("s", $duration));


  
            $newClip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$video,
                                    ],
                                    "start" => $start,
                                    "length" => $duration,
                                ]
                            ]
                        ];
            $dataArray["timeline"]["tracks"][] = $newClip;
           
            $start += $duration;

            $cart_plan =  Auth::user()->plan_purchased ;


                if ($cart_plan != 1) { 
                    if ($cart_plan == 2) {     
                        if (($key+1)%3 == 0) {
                            $decade_video = 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4';
                              $decade_duration =  FFMpeg::fromDisk('public')
                                                  ->open($decade_video)
                                                 ->getDurationInSeconds();
                                    // $decade_duration = intval(gmdate("s", $decade_duration));  
                                    $decade_clip = [ 
                                                    "clips" => 
                                                    [
                                                        [       "asset" => [
                                                                "type" => "video",
                                                                "src" => 'https://storeetree.com/storage/'.$decade_video,
                                                        ],
                                                            "start" => $start,
                                                            "length" => $decade_duration,
                                                        ]
                                                    ]
                                                ];

                                    $dataArray["timeline"]["tracks"][] = $decade_clip;           
                                    $start += $decade_duration;
                                    $memory_num++ ;
                        }
                    }

                    if ($cart_plan == 3) {
                        if (($key+1)%4 == 0) {
                            $decade_video = 'memories/connected/'.config('constants.CONNECTED_PERIODS.' . Auth::user()->connected_period).'/questions/question'.$memory_num.'.mp4';

                              $decade_duration =  FFMpeg::fromDisk('public')
                                                  ->open($decade_video)
                                                 ->getDurationInSeconds();
                                    // $decade_duration = intval(gmdate("s", $decade_duration));  
                                    $decade_clip = [ 
                                                    "clips" => 
                                                    [
                                                        [       "asset" => [
                                                                "type" => "video",
                                                                "src" => 'https://storeetree.com/storage/'.$decade_video,
                                                        ],
                                                            "start" => $start,
                                                            "length" => $decade_duration,
                                                        ]
                                                    ]
                                                ];

                                    $dataArray["timeline"]["tracks"][] = $decade_clip;           
                                    $start += $decade_duration;
                                    $memory_num++ ;
                        }
                        
                    }
                }
        } 


        $outro_video = 'formatted/mainchunks/in.mp4';
                              $outro_videoduration =  FFMpeg::fromDisk('public')
                                                  ->open($outro_video)
                                                 ->getDurationInSeconds();
                                    // $outro_videoduration = intval(gmdate("s", $outro_videoduration));  

            $closeclip = [ 
                            "clips" => 
                            [
                                [
                                    "asset" => [
                                        "type" => "video",
                                        "src" => 'https://storeetree.com/storage/'.$outro_video,
                                    ],
                                    "start" => $start,
                                    "length" => $outro_videoduration,
                                ]
                            ]
                        ];

            $dataArray["timeline"]["tracks"][] = $closeclip;
           
            $start += $outro_videoduration;

        $updatedData = json_encode($dataArray,JSON_PRETTY_PRINT);
        // dd($updatedData);
        $headers = array('Content-Type:application/json',
                'Accept:application/json',
                'x-api-key:ABrmaqtYxw5yxq2fFBNwYAW7Px7Am5LCbhFhqdp8',
                'Host:api.shotstack.io'
               );


        $response = Curl::to('https://api.shotstack.io/stage/render')
        ->withHeaders($headers)
        ->withData($updatedData)
        ->post();

        $decoded_json_data = json_decode($response, true);


        $video_id = $decoded_json_data['response']['id'];
        $questionss = implode('---',$all_title);
        $story = Story::find($story_id);
        $story->video = $video_id;
        $story->status = 0;
        $story->all_questions = $questionss;
        $story->save();


        // $aws_video = static::get_video($video_id);


     
       //  $url = $aws_video;

       // $desiredFile = 'merged-video/' . Auth::user()->id . '/' . date('Y-m-d') . '/' . rand(99999, 9999999) . time() . '.mp4';

       //  $fileContents = file_get_contents($url);

       //  if ($fileContents !== false) {
       //      // Save the downloaded file to the desired location
       //      Storage::disk('public')->put($desiredFile, $fileContents);

            
       //  } else {
       //      dd("Failed to download the file.");
       //  }

       

        // $all_title = [];

        // foreach ($warmups as $key => $warmup){
        //     array_unshift($all_title,$warmup['title']);
        // }
        // foreach ($questions as $key => $question){
        //     array_unshift($all_title,$question['title']);
        // }
        // $questionss = implode('---',$all_title);
        //   $story_data   = Story::find($story_id);
        //     $story_data->video = $desiredFile;
        //      $story_data->all_questions = $questionss;
        //     $story_data->save();

        // $story->storyItems()->createMany($createStoryItems);
        // $story->storyWarmupItems()->createMany($createStoryWarmupItems);   

        Session::forget(['cart', 'storyItems']);
        // return $aws_video;












































    //    $input_audio  = static::getAudioMusic();
    //    // dd($input_audio);
    //    $old_video = $story_title;




    //     if(Storage::disk('public')->exists($old_video)){
    //         Storage::disk('public')->delete($old_video);
    //     }
       
    //    $video_storage_link = "merged-video/" . Auth::user()->id . "/" . date('Y-m-d') . "/" . rand(99999, 9999999) . time() ."edited.mp4";
            

    //    $storyWarmupItems = StoryWarmupItem::where('story_id',$story_id)->get();


    //    $storyQuestionItems = StoryItem::where('story_id',$story_id)->get();
    //     dd($storyWarmupItems);


    //     $mergeableVideos =  [];
    //     // $mergeableVideos =  ['formatted/mainchunks/intro.mp4'];

    //     $createStoryItems = [];

    // foreach ($storyWarmupItems as $key => $storyItem) {
    //    $mergeableVideos[] = $storyItem->video;
    // }
    // foreach ($storyQuestionItems as $key => $storyItem) {
    //    $mergeableVideos[] = $storyItem->video;
    // }
    // // array_push($mergeableVideos, 'formatted/mainchunks/intro.mp4');

    //         $story_data   = Story::find($story_id);
    //         $story_data->video = $video_storage_link;
    //         $story_data->save();
            
    //         static::addBackgroundMusic($video_storage_link, $input_audio, $mergeableVideos);        
    }


    public static function get_video($video_id){
               
        $headerss = array('x-api-key:ABrmaqtYxw5yxq2fFBNwYAW7Px7Am5LCbhFhqdp8',
                              'Content-Type:application/json'                 
                             );

        $video_api = 'https://api.shotstack.io/stage/render/'.$video_id;
        $finalresponse = Curl::to($video_api)
        ->withHeaders($headerss)
        ->get();

         $fdecoded_json_data = json_decode($finalresponse, true);
         $api_status = $fdecoded_json_data['response']['status'];
         if($api_status != "done"){
              $aws_video = static::get_video($video_id);
         }
         else{
            $video_url = $fdecoded_json_data['response']['url'];
        
            return $video_url;

         }
            return $aws_video;

        }
}