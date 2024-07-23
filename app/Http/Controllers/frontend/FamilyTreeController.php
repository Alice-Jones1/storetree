<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Http\Request;
use App\Models\Relation;
use App\Models\FamilyTree;
use App\Models\Story;
use App\Models\User;
use App\Models\AddonVideo;
use App\Mail\RegisterationEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use URL;
use DB;
use Mail;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use App\Rules\dobRequired;

class FamilyTreeController extends BaseController
{
    public function __construct() {
        parent::__construct();
    }
    /**
     * Display the blogs page of the site
     *
     * @return \Illuminate\Http\Response
     */
    public function processData($dataInfo,$relation_id=0)
    {
        $data = [
                    'id'=>$dataInfo->id,
                    'name'=>$dataInfo->first_name .' '. $dataInfo->last_name,
                    'gender'=>$dataInfo->gender,
                    'img'=>($dataInfo->gender=='male') ? URL::to('/').'/images/frontend/photo_man.png':URL::to('/').'/images/frontend/photo_female.png',
                    'relation_id'=>$relation_id,
                    'user_id'=>$dataInfo->user_id,
                    'living' => $dataInfo->living
                ];

                (!is_null($dataInfo->pid)) ? $data['pids']=[$dataInfo->pid]:'';
                (!is_null($dataInfo->fid)) ? $data['fid']=$dataInfo->fid:'';
                (!is_null($dataInfo->mid)) ? $data['mid']=$dataInfo->mid:'';
        return $data;
    }

    public function searchUser(Request $request){
        $user = User::where('first_name', 'LIKE', '%'.$request->search_value.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$request->search_value.'%')
                    ->orWhere('email', 'LIKE', '%'.$request->search_value.'%')->get();

        if (count($user) != 0) {
            return response()->json(['msg'=>'Success','data'=>$user]);   
        }
        else{
            return response()->json(['msg'=>'Error','data'=>'No Records Found']);
        }
    }


    public function index(Request $request)
    {
        $datas=[];
        $currentUser=FamilyTree::where('user_id',auth()->user()->id)->first();
      
        if(!empty($currentUser)) {
     
            //self  info
            $data=$this->processData($currentUser, 13);
            array_push($datas, $data);
            
            if(!empty($currentUser->fatherInfo)){
                //father info
                $data=$this->processData($currentUser->fatherInfo,9);
                array_push($datas,$data);

                if(!empty($currentUser->fatherInfo->fatherInfo)){
                    //parental grand father info
                    $data=$this->processData($currentUser->fatherInfo->fatherInfo,1);
                    array_push($datas,$data);

                }
                if(!empty($currentUser->fatherInfo->motherInfo)){
                    //parental grand mother info
                    $data=$this->processData($currentUser->fatherInfo->motherInfo,2);
                    array_push($datas,$data);

                }
            }
            if(!empty($currentUser->motherInfo)){
                //mother info
              
                $data=$this->processData($currentUser->motherInfo,10);
                
                array_push($datas,$data);
                if(!empty($currentUser->motherInfo->fatherInfo)){
                    //maternal grand father info
                    $data=$this->processData($currentUser->motherInfo->fatherInfo,3);
                    array_push($datas,$data);

                }
                if(!empty($currentUser->motherInfo->motherInfo)){
                    //maternal grand mother info
                    $data=$this->processData($currentUser->motherInfo->motherInfo,4);
                    array_push($datas,$data);
                    
                }
            }
            if(!empty($currentUser->partnerInfo)){
                //partner info              
                $data=$this->processData($currentUser->partnerInfo,$currentUser->partnerInfo->relation_id);
               
                array_push($datas,$data);
                if(!empty($currentUser->partnerInfo->fatherInfo)){
                    // father in law info
                    $data=$this->processData($currentUser->partnerInfo->fatherInfo,11);
                    array_push($datas,$data);
                    
                    if(!empty($currentUser->partnerInfo->fatherInfo->fatherInfo)){
                    //parental grand father in law info
                        $data=$this->processData($currentUser->partnerInfo->fatherInfo->fatherInfo,5);
                        array_push($datas,$data);

                    }
                    if(!empty($currentUser->partnerInfo->fatherInfo->motherInfo)){
                    //parental grand mother in law info
                        $data=$this->processData($currentUser->partnerInfo->fatherInfo->motherInfo,6);
                        array_push($datas,$data);

                    }

                }
                if(!empty($currentUser->partnerInfo->motherInfo)){
                    // mother in law info
                    $data=$this->processData($currentUser->partnerInfo->motherInfo,12);
                    array_push($datas,$data);

                    if(!empty($currentUser->partnerInfo->motherInfo->fatherInfo)){
                    // maternal grand father in law info
                        $data=$this->processData($currentUser->partnerInfo->motherInfo->fatherInfo,5);
                        array_push($datas,$data);

                    }
                    if(!empty($currentUser->partnerInfo->motherInfo->motherInfo)){
                    // maternal grand mother in law info
                        $data=$this->processData($currentUser->partnerInfo->motherInfo->motherInfo,6);
                        array_push($datas,$data);

                    } 
                }

                if(!is_null($currentUser->partnerInfo->fid) || !is_null($currentUser->partnerInfo->mid)){
                    if(!is_null($currentUser->partnerInfo->fid) && !is_null($currentUser->partnerInfo->mid)){
                        // father in law and mother in both exists
                        $childrens = $this->getChildrens($currentUser->partnerInfo->fatherInfo->id);
                        foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16);
                            array_push($datas,$data);
                        }
                    }
                    else
                        if(!is_null($currentUser->partnerInfo->fid)){
                            $childrens = $this->getChildrens($currentUser->partnerInfo->fatherInfo->id);
                            foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16);
                            array_push($datas,$data);
                        }
                    }
                    else
                        if(!is_null($currentUser->partnerInfo->mid)){
                            $childrens = $this->getChildrens($currentUser->partnerInfo->motherInfo->id);
                            foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16);
                            array_push($datas,$data);
                        }
                    }
                }
            }
            // cut kora  self info er pore
                $childrens = $this->getChildrens($currentUser->id);
                            
                foreach($childrens as $key=>$childInfo){                
                    if($childInfo->gender=='male'){
                        $data=$this->processData($childInfo,19+($key/10));
                        array_push($datas,$data); 
                        if($childInfo->pid != null){
                            $sonspartner = $childInfo->partnerInfo()->first();
                            $relationID = 0;
                            if ($sonspartner->relation_id == 14) { $relationID = 20;  } //wife       
                            if ($sonspartner->relation_id == 26) { $relationID = 28;  } //Husband       
                            if ($sonspartner->relation_id == 27) { $relationID = 29;  } //Partner

                            $data=$this->processData($sonspartner,$relationID+($key/10));

                            array_push($datas,$data); 
                            
                            foreach($childInfo->childInfosForFather()->get() as $gkey=>$grandChildInfo){

                                if($grandChildInfo->gender=='male'){                         
                                    $data=$this->processData($grandChildInfo,24+($key/10)+($gkey/100));
                                    array_push($datas,$data);
                                    if($grandChildInfo->pid != null){

                                        $grandSonsPartner = $grandChildInfo->partnerInfo()->first();
                                    

                                        $relationID = 0;
                                        if ($grandSonsPartner->relation_id == 14) { $relationID = 32;  } //wife       
                                        if ($grandSonsPartner->relation_id == 26) { $relationID = 33;  } //Husband       
                                        if ($grandSonsPartner->relation_id == 27) { $relationID = 34;  } //Partner

                                        $data=$this->processData($grandSonsPartner,$relationID+($key/10)+($gkey/100));
                                        array_push($datas,$data); 
                                    }

                                }
                                if($grandChildInfo->gender=='female'){   

                                    $data=$this->processData($grandChildInfo,25+($key/10)+($gkey/100));
                                    array_push($datas,$data);
                                    if($grandChildInfo->pid != null){
                                        $grandDaughtersPartner = $grandChildInfo->partnerInfo()->first();
                                        $relationID = 0;

                                        if ($grandDaughtersPartner->relation_id == 14) { $relationID = 36;  } //wife       
                                        if ($grandDaughtersPartner->relation_id == 26) { $relationID = 35;  } //Husband       
                                        if ($grandDaughtersPartner->relation_id == 27) { $relationID = 37;  } //Partner

                                        $data=$this->processData($grandDaughtersPartner,$relationID+($key/10)+($gkey/100));
                                        array_push($datas,$data); 
                                    }
                                }
                            }
                        }
                    }

                    if($childInfo->gender=='female'){
                        $data=$this->processData($childInfo,21+($key/10));
                        array_push($datas,$data); 
                        if($childInfo->pid != null){
                            $daughterspartner = $childInfo->partnerInfo()->first(); 

                            $relationID = 0;
                            if ($daughterspartner->relation_id == 14) { $relationID = 30;  } //wife       
                            if ($daughterspartner->relation_id == 26) { $relationID = 22;  } //Husband       
                            if ($daughterspartner->relation_id == 27) { $relationID = 31;  } //Partner

                            $data=$this->processData($daughterspartner,$relationID+($key/10));
                            array_push($datas,$data); 

                        foreach($childInfo->childInfosForMother()->get() as $gkey=>$grandChildInfo){

                            if($grandChildInfo->gender=='male'){                         
                                $data=$this->processData($grandChildInfo,24+($key/10)+($gkey/100));
                                array_push($datas,$data);
                                if($grandChildInfo->pid != null){
                                    $grandSonsPartner = $grandChildInfo->partnerInfo()->first();
                                    $relationID = 0;
                                    if ($grandSonsPartner->relation_id == 14) { $relationID = 32;  } //wife       
                                    if ($grandSonsPartner->relation_id == 26) { $relationID = 33;  } //Husband       
                                    if ($grandSonsPartner->relation_id == 27) { $relationID = 34;  } //Partner

                                    $data=$this->processData($grandSonsPartner,$relationID+($key/10)+($gkey/100));
                                    array_push($datas,$data); 
                                }
                            }
                            if($grandChildInfo->gender=='female'){
                        
                                                 
                                $data=$this->processData($grandChildInfo,25+($key/10)+($gkey/100));
                                array_push($datas,$data);
                                if($grandChildInfo->pid != null){
                                    $grandDaughtersPartner = $grandChildInfo->partnerInfo()->first();
                                    $relationID = 0;
                                    if ($grandDaughtersPartner->relation_id == 14) { $relationID = 36;  } //wife       
                                    if ($grandDaughtersPartner->relation_id == 26) { $relationID = 35;  } //Husband       
                                    if ($grandDaughtersPartner->relation_id == 27) { $relationID = 37;  } //Partner

                                    $data=$this->processData($grandDaughtersPartner,$relationID+($key/10)+($gkey/100));
                                    array_push($datas,$data); 
                                }
                            }
                        }
                    }
                }

                }

            if(!is_null($currentUser->fid) || !is_null($currentUser->mid)){
                if(!is_null($currentUser->fid) && !is_null($currentUser->mid)){
                    // father and mother both exists
                    $childrens = $this->getChildrens($currentUser->fatherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){
                   
                        //sibling infos
                        $data=$this->processData($siblingInfo,15+($key/10));
                        array_push($datas,$data);
                        
                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo, 16);
                            array_push($datas,$data);
                        }
                    }
                }
                else
                    if(!is_null($currentUser->fid)){
                        $childrens = $this->getChildrens($currentUser->fatherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){
                   
                        //sibling  infos
                        $data=$this->processData($siblingInfo, 15+($key/10));
                        array_push($datas,$data);

                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo,16);
                            array_push($datas,$data);
                        }
                    }
                }
                else
                    if(!is_null($currentUser->mid)){
                        $childrens = $this->getChildrens($currentUser->motherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){
                    
                        //sibling  infos
                        $data=$this->processData($siblingInfo, 15+($key/10));
                        array_push($datas,$data);

                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo,16);
                            array_push($datas,$data);
                        }
                    }
                }
            }
            
        }

        $new_datas=[];
        $patnerFlag = false;
        $fatherFlag = false;
        $motherFlag = false;
        $fatherInLawFlag = false;
        $motherInLawFlag = false;
        $paternal_grand_fatherFlag = false;
        $paternal_grand_motherFlag = false;
        $maternal_grand_fatherFlag = false;
        $maternal_grand_motherFlag = false;
        foreach ($datas as $key => $value) {
            if($value['user_id'] == Auth::user()->id){
                $value['relation_id'] = 13;  
            }
            if($value['relation_id']==1){
                 $value['title'] = "Paternal Grand Father";
                 $paternal_grand_fatherFlag = true;
                 $paternal_grand_father_id = $value['id'];
            }
            if($value['relation_id']==2){
                 $value['title'] = "Paternal Grand Mother";
                 $paternal_grand_motherFlag = true;
                 $paternal_grand_mother_id = $value['id'];
            }
            if($value['relation_id']==3){
                 $value['title'] = "Maternal Grand Father";
                 $maternal_grand_fatherFlag = true;
                 $maternal_grand_father_id = $value['id'];
            }
            if($value['relation_id']==4){
                 $value['title'] = "Maternal Grand Mother";
                 $maternal_grand_motherFlag = true;
                 $maternal_grand_mother_id = $value['id'];
            }
            if($value['relation_id']==5){
                $value['title'] = "Grand Father In Law";
                $maternal_grand_fatherFlag = true;
                $maternal_grand_father_id = $value['id'];
           }
           if($value['relation_id']==6){
                $value['title'] = "Grand Mother In Law";
                $maternal_grand_motherFlag = true;
                $maternal_grand_mother_id = $value['id'];
           }
            
            if($value['relation_id']==9){
                 $value['title'] = "Father";
                 $fatherFlag = true;
                 $father_id = $value['id'];
            }
            if($value['relation_id']==10){
                 $value['title'] = "Mother";
                 $motherFlag = true;
                 $mother_id = $value['id'];
            }
            if($value['relation_id']==11){
                 $value['title'] = "Father in law";
                 $fatherInLawFlag = true;
                 $father_in_law_id = $value['id'];
            }
            if($value['relation_id']==12){
                 $value['title'] = "Mother in law";
                 $motherInLawFlag = true;
                 $mother_in_law_id = $value['id'];
            }
            if($value['relation_id']==13){
                 $value['title'] = "Self";
                 $patner_id = $value['id'];
            }
            if(intval($value['relation_id'])==14){
                 $value['title'] = "Wife";
                 $patnerFlag = true;                 
            }
             if(intval($value['relation_id'])==15){
                  if($value['gender']=='male'){
                    $value['title'] = "Brother";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister";
                }
            }
             if(intval($value['relation_id'])==17){
                  if($value['gender']=='male'){
                    $value['title'] = "Brother";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister";
                }
            }
            if(intval($value['relation_id'])==16){
                if($value['gender']=='male'){
                    $value['title'] = "Brother-in-law";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister-in-law";
                }
            }
            if(intval($value['relation_id'])==18){
                 if($value['gender']=='male'){
                    $value['title'] = "Brother-in-law";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister-in-law";
                }
            }
           
            if(intval($value['relation_id'])==19){
                if($value['gender']=='male'){
                     if(!isset($value['mid'])){
                        $value['mid'] = -14;
                    }
                    $value['title'] = "Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Daughter";
                }
            }
            if(intval($value['relation_id'])== 20){ 
                    $value['title'] = "Son's Wife";
                      
            }

            if(intval($value['relation_id'])==21){
                if(!isset($value['mid'])){
                    $value['mid'] = -14;
                } 
                    $value['title'] = "Daughter";              
            }

            if(intval($value['relation_id'])==22){
                $value['title'] = "Daughter's Husband";
                  
            }


            if(intval($value['relation_id'])==24){
                if($value['gender']=='male'){
                    $value['title'] = "Grand Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Grand Daughter";
                }
            }


            if(intval($value['relation_id'])==25){
                if($value['gender']=='male'){
                    $value['title'] = "Grand Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Grand Daughter";
                }
            }
            if(intval($value['relation_id'])==26){
                $value['title'] = "Husband";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==27){
                $value['title'] = "Partner";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==28){
                $value['title'] = "Son's Husband";
            }
            if(intval($value['relation_id'])==29){
                $value['title'] = "Son's Partner";  
            }
            if(intval($value['relation_id'])==30){
                $value['title'] = "Daughter's Wife"; 
            }
            if(intval($value['relation_id'])==31){
                $value['title'] = "Daughter's Partner";
            }
            if(intval($value['relation_id'])==32){
                $value['title'] = "Grand Son's Wife";
            }
            if(intval($value['relation_id'])==33){
                $value['title'] = "Grand Son's Husband";
            }
            if(intval($value['relation_id'])==34){
                $value['title'] = "Grand Son's Partner";
            }
            if(intval($value['relation_id'])==35){
                $value['title'] = "Grand Daughter's Husband";
            }
            if(intval($value['relation_id'])==36){
                $value['title'] = "Grand Daughter's Wife";
            }
            if(intval($value['relation_id'])==37){
                $value['title'] = "Grand Daughter's Partner";
            }
            
            $value['photo'] = $value['img'];
            // temp user image show
            if($value['relation_id']==13){
                $value['title'] = "Self";
                if (Auth::user()->photo != null) {
                    $value['photo'] = URL('storage/'.Auth::user()->photo);
                }
                else{
                    $value['photo'] = $value['img'];
                }
           }
            $value['addr'] = $value['living'];
           
            $value['cn'] = "";
            unset($value->dob);
            unset($value->first_name);
            unset($value->last_name);
            unset($value->email);
            unset($value->user_id);
            unset($value->status);
            unset($value->created_at);
            unset($value->updated_at);
            unset($value->deleted_at);
            array_unshift($new_datas,$value);
        }
        //patner not exists
        if(!$patnerFlag){
            $d_data = [];
            $d_data['id'] = -14;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 14;  
            $d_data['title'] = 'Partner';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($patner_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // mother not exists
        if(($fatherFlag && !$motherFlag)){
            $d_data = [];
            $d_data['id'] = -10;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 10;  
            $d_data['title'] = 'Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // father not exists
        if(!$fatherFlag && $motherFlag){
            $d_data = [];
            $d_data['id'] = -9;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 9;  
            $d_data['title'] = 'Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // mother in law not exists
        if(($fatherInLawFlag && !$motherInLawFlag)){
            $d_data = [];
            $d_data['id'] = -12;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 12;  
            $d_data['title'] = 'Mother In Law';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($father_in_law_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // father in law not exists
        if(!$fatherInLawFlag && $motherInLawFlag){
            $d_data = [];
            $d_data['id'] = -11;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 11;  
            $d_data['title'] = 'Father In Law'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($mother_in_law_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // paternal grand mother not exists
        if(($paternal_grand_fatherFlag && !$paternal_grand_motherFlag)){
            $d_data = [];
            $d_data['id'] = -2;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 2;  
            $d_data['title'] = 'Paternal Grand Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($paternal_grand_father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        //paternal grandfather not exists
        if(!$paternal_grand_fatherFlag && $paternal_grand_motherFlag){
            $d_data['id'] = -1;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 1;  
            $d_data['title'] = 'Paternal Grand Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($paternal_grand_mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // maternal grand mother not exists
        if(($maternal_grand_fatherFlag && !$maternal_grand_motherFlag)){
            $d_data = [];
            $d_data['id'] = -4;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 4;  
            $d_data['title'] = 'Maternal Grand Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($maternal_grand_father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        //maternal grandfather not exists
        if(!$maternal_grand_fatherFlag && $maternal_grand_motherFlag){
            $d_data['id'] = -3;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 3;  
            $d_data['title'] = 'Maternal Grand Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($maternal_grand_mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        $main_person =  $datas[0];
        $friends_ids = FamilyTree::where('user_id', auth()->user()->id)->first();
        $friends = explode(",",$friends_ids->frnid); 
        return view('frontend.family-trees.vue_family_tree',compact(['new_datas','main_person','friends']));
    }
    

    public function treeindex(Request $request)
    {
        $imp_id = $request->id;
        $datas=[];
        $currentUser=FamilyTree::where('user_id',$request->id)->first();
        $searchedUser=FamilyTree::where('id',$request->id)->first();
        $searched_m_User=User::where('id',$request->id)->first();
      
        if(!empty($currentUser)) {
     
            //self  info
            $data=$this->processData($currentUser, 13);
            array_push($datas, $data);
            
            if(!empty($currentUser->fatherInfo)){
                //father info
                $data=$this->processData($currentUser->fatherInfo,9);
                array_push($datas,$data);

                if(!empty($currentUser->fatherInfo->fatherInfo)){
                    //parental grand father info
                    $data=$this->processData($currentUser->fatherInfo->fatherInfo,1);
                    array_push($datas,$data);

                }
                if(!empty($currentUser->fatherInfo->motherInfo)){
                    //parental grand mother info
                    $data=$this->processData($currentUser->fatherInfo->motherInfo,2);
                    array_push($datas,$data);

                }
            }
            if(!empty($currentUser->motherInfo)){
                //mother info
              
                $data=$this->processData($currentUser->motherInfo,10);
                
                array_push($datas,$data);
                if(!empty($currentUser->motherInfo->fatherInfo)){
                    //maternal grand father info
                    $data=$this->processData($currentUser->motherInfo->fatherInfo,3);
                    array_push($datas,$data);
                }
                if(!empty($currentUser->motherInfo->motherInfo)){
                    //maternal grand mother info
                    $data=$this->processData($currentUser->motherInfo->motherInfo,4);
                    array_push($datas,$data);
                    
                }
            }
            if(!empty($currentUser->partnerInfo)){
                //partner info
                   $data=$this->processData($currentUser->partnerInfo,$currentUser->partnerInfo->relation_id);

                array_push($datas,$data);
                if(!empty($currentUser->partnerInfo->fatherInfo)){
                    // father in law info
                    $data=$this->processData($currentUser->partnerInfo->fatherInfo,11);
                    array_push($datas,$data);
                    
                    if(!empty($currentUser->partnerInfo->fatherInfo->fatherInfo)){
                    //parental grand father in law info
                        $data=$this->processData($currentUser->partnerInfo->fatherInfo->fatherInfo,5);
                        array_push($datas,$data);

                    }
                    if(!empty($currentUser->partnerInfo->fatherInfo->motherInfo)){
                    //parental grand mother in law info
                        $data=$this->processData($currentUser->partnerInfo->fatherInfo->motherInfo,6);
                        array_push($datas,$data);

                    }

                }
                if(!empty($currentUser->partnerInfo->motherInfo)){
                    // mother in law info
                    $data=$this->processData($currentUser->partnerInfo->motherInfo,12);
                    array_push($datas,$data);

                    if(!empty($currentUser->partnerInfo->motherInfo->fatherInfo)){
                    // maternal grand father in law info
                        $data=$this->processData($currentUser->partnerInfo->motherInfo->fatherInfo,5);
                        array_push($datas,$data);

                    }
                    if(!empty($currentUser->partnerInfo->motherInfo->motherInfo)){
                    // maternal grand mother in law info
                        $data=$this->processData($currentUser->partnerInfo->motherInfo->motherInfo,6);
                        array_push($datas,$data);

                    } 
                }

                if(!is_null($currentUser->partnerInfo->fid) || !is_null($currentUser->partnerInfo->mid)){
                    if(!is_null($currentUser->partnerInfo->fid) && !is_null($currentUser->partnerInfo->mid)){
                        // father in law and mother in both exists
                        $childrens = $this->getChildrens($currentUser->partnerInfo->fatherInfo->id);
                        foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                    else
                        if(!is_null($currentUser->partnerInfo->fid)){
                            $childrens = $this->getChildrens($currentUser->partnerInfo->fatherInfo->id);
                            foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                    else
                        if(!is_null($currentUser->partnerInfo->mid)){
                            $childrens = $this->getChildrens($currentUser->partnerInfo->motherInfo->id);
                            foreach($childrens as $key=>$siblingInfo){
                            //sibling in law infos
                            $data=$this->processData($siblingInfo,16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                }
            }
            // cut kora  self info er pore
                $childrens = $this->getChildrens($currentUser->id);
                            
                foreach($childrens as $key=>$childInfo){                
                    if($childInfo->gender=='male'){
                        $data=$this->processData($childInfo,19+($key/10));
                        array_push($datas,$data); 
                        if($childInfo->pid != null){
                            $sonspartner = $childInfo->partnerInfo()->first();
                            $relationID = 0;
                            if ($sonspartner->relation_id == 14) { $relationID = 20;  } //wife       
                            if ($sonspartner->relation_id == 26) { $relationID = 28;  } //Husband       
                            if ($sonspartner->relation_id == 27) { $relationID = 29;  } //Partner

                            $data=$this->processData($sonspartner,$relationID+($key/10));

                            array_push($datas,$data); 
                            
                            foreach($childInfo->childInfosForFather()->get() as $gkey=>$grandChildInfo){

                                if($grandChildInfo->gender=='male'){                         
                                    $data=$this->processData($grandChildInfo,24+($key/10)+($gkey/100));
                                    array_push($datas,$data);
                                    if($grandChildInfo->pid != null){

                                        $grandSonsPartner = $grandChildInfo->partnerInfo()->first();
                                    

                                        $relationID = 0;
                                        if ($grandSonsPartner->relation_id == 14) { $relationID = 32;  } //wife       
                                        if ($grandSonsPartner->relation_id == 26) { $relationID = 33;  } //Husband       
                                        if ($grandSonsPartner->relation_id == 27) { $relationID = 34;  } //Partner

                                        $data=$this->processData($grandSonsPartner,$relationID+($key/10)+($gkey/100));
                                        array_push($datas,$data); 
                                    }

                                }
                                if($grandChildInfo->gender=='female'){   

                                    $data=$this->processData($grandChildInfo,25+($key/10)+($gkey/100));
                                    array_push($datas,$data);
                                    if($grandChildInfo->pid != null){
                                        $grandDaughtersPartner = $grandChildInfo->partnerInfo()->first();
                                        $relationID = 0;
                            
                                        if ($grandDaughtersPartner->relation_id == 14) { $relationID = 36;  } //wife       
                                        if ($grandDaughtersPartner->relation_id == 26) { $relationID = 35;  } //Husband       
                                        if ($grandDaughtersPartner->relation_id == 27) { $relationID = 37;  } //Partner

                                        $data=$this->processData($grandDaughtersPartner,$relationID+($key/10)+($gkey/100));
                                        array_push($datas,$data); 
                                    }
                                }
                            }
                        }
                    }

                    if($childInfo->gender=='female'){
                        $data=$this->processData($childInfo,21+($key/10));
                        array_push($datas,$data); 
                        if($childInfo->pid != null){
                            $daughterspartner = $childInfo->partnerInfo()->first(); 

                            $relationID = 0;
                            if ($daughterspartner->relation_id == 14) { $relationID = 30;  } //wife       
                            if ($daughterspartner->relation_id == 26) { $relationID = 22;  } //Husband       
                            if ($daughterspartner->relation_id == 27) { $relationID = 31;  } //Partner

                            $data=$this->processData($daughterspartner,$relationID+($key/10));
                            array_push($datas,$data); 
                        $childrens = $this->getChildrens($childInfo->id);
                            foreach($childrens as $key=>$grandChildInfo){
                        foreach($childInfo->childInfosForMother()->get() as $gkey=>$grandChildInfo){

                            if($grandChildInfo->gender=='male'){                         
                                $data=$this->processData($grandChildInfo,24+($key/10)+($gkey/100));
                                array_push($datas,$data);
                                if($grandChildInfo->pid != null){
                                    $grandSonsPartner = $grandChildInfo->partnerInfo()->first();
                                    $relationID = 0;
                                    if ($grandSonsPartner->relation_id == 14) { $relationID = 32;  } //wife       
                                    if ($grandSonsPartner->relation_id == 26) { $relationID = 33;  } //Husband       
                                    if ($grandSonsPartner->relation_id == 27) { $relationID = 34;  } //Partner

                                    $data=$this->processData($grandSonsPartner,$relationID+($key/10)+($gkey/100));
                                    array_push($datas,$data); 
                                }
                            }
                            if($grandChildInfo->gender=='female'){
                        
                                                 
                                $data=$this->processData($grandChildInfo,25+($key/10)+($gkey/100));
                                array_push($datas,$data);
                                if($grandChildInfo->pid != null){
                                    $grandDaughtersPartner = $grandChildInfo->partnerInfo()->first();
                                    $relationID = 0;
                                    if ($grandDaughtersPartner->relation_id == 14) { $relationID = 36;  } //wife       
                                    if ($grandDaughtersPartner->relation_id == 26) { $relationID = 35;  } //Husband       
                                    if ($grandDaughtersPartner->relation_id == 27) { $relationID = 37;  } //Partner

                                    $data=$this->processData($grandDaughtersPartner,$relationID+($key/10)+($gkey/100));
                                    array_push($datas,$data); 
                                }
                            }
                        }
                    }
                    }
                }

                }

            if(!is_null($currentUser->fid) || !is_null($currentUser->mid)){
                if(!is_null($currentUser->fid) && !is_null($currentUser->mid)){
                    // father and mother both exists
                    $childrens = $this->getChildrens($currentUser->fatherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){
                   
                        //sibling infos
                        $data=$this->processData($siblingInfo,15+($key/10));
                        array_push($datas,$data);
                        
                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo, 16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                }
                else
                    if(!is_null($currentUser->fid)){
                        $childrens = $this->getChildrens($currentUser->fatherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){                   
                        //sibling  infos
                        $data=$this->processData($siblingInfo, 15+($key/10));
                        array_push($datas,$data);

                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo,16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                }
                else
                    if(!is_null($currentUser->mid)){
                        $childrens = $this->getChildrens($currentUser->motherInfo->id);
                    foreach($childrens as $key=>$siblingInfo){
                    
                        //sibling  infos
                        $data=$this->processData($siblingInfo, 15+($key/10));
                        array_push($datas,$data);

                        if(!empty($siblingInfo->partnerInfo)){
                            // for sibling partern info
                            $data=$this->processData($siblingInfo->partnerInfo,16+($key/10));
                            array_push($datas,$data);
                        }
                    }
                }
            }
            
        }

        $new_datas=[];
        $patnerFlag = false;
        $fatherFlag = false;
        $motherFlag = false;
        $fatherInLawFlag = false;
        $motherInLawFlag = false;
        $paternal_grand_fatherFlag = false;
        $paternal_grand_motherFlag = false;
        $maternal_grand_fatherFlag = false;
        $maternal_grand_motherFlag = false;

        
        foreach ($datas as $key => $value) {
            if($value['user_id'] == $imp_id){
                $value['relation_id'] = 13;  
            }
            if($value['relation_id']==1){
                 $value['title'] = "Paternal Grand Father";
                 $paternal_grand_fatherFlag = true;
                 $paternal_grand_father_id = $value['id'];
            }
            if($value['relation_id']==2){
                 $value['title'] = "Paternal Grand Mother";
                 $paternal_grand_motherFlag = true;
                 $paternal_grand_mother_id = $value['id'];
            }
            if($value['relation_id']==3){
                 $value['title'] = "Maternal Grand Father";
                 $maternal_grand_fatherFlag = true;
                 $maternal_grand_father_id = $value['id'];
            }
            if($value['relation_id']==4){
                 $value['title'] = "Maternal Grand Mother";
                 $maternal_grand_motherFlag = true;
                 $maternal_grand_mother_id = $value['id'];
            }
            if($value['relation_id']==5){
                $value['title'] = "Grand Father In Law";
                $maternal_grand_fatherFlag = true;
                $maternal_grand_father_id = $value['id'];
           }
           if($value['relation_id']==6){
                $value['title'] = "Grand Mother In Law";
                $maternal_grand_motherFlag = true;
                $maternal_grand_mother_id = $value['id'];
           }
            
            if($value['relation_id']==9){
                 $value['title'] = "Father";
                 $fatherFlag = true;
                 $father_id = $value['id'];
            }
            if($value['relation_id']==10){
                 $value['title'] = "Mother";
                 $motherFlag = true;
                 $mother_id = $value['id'];
            }
            if($value['relation_id']==11){
                 $value['title'] = "Father in law";
                 $fatherInLawFlag = true;
                 $father_in_law_id = $value['id'];
            }
            if($value['relation_id']==12){
                 $value['title'] = "Mother in law";
                 $motherInLawFlag = true;
                 $mother_in_law_id = $value['id'];
            }
            if($value['relation_id']==13){
                 $value['title'] = "Self";
                 $patner_id = $value['id'];
            }
            if(intval($value['relation_id'])==14){

                 $value['title'] = "Wife";
                 $patnerFlag = true;                 
            }
             if(intval($value['relation_id'])==15){
                  if($value['gender']=='male'){
                    $value['title'] = "Brother";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister";
                }
            }
             if(intval($value['relation_id'])==17){
                  if($value['gender']=='male'){
                    $value['title'] = "Brother";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister";
                }
            }
            if(intval($value['relation_id'])==16){
                if($value['gender']=='male'){
                    $value['title'] = "Brother-in-law";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister-in-law";
                }
            }
            if(intval($value['relation_id'])==18){
                 if($value['gender']=='male'){
                    $value['title'] = "Brother-in-law";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Sister-in-law";
                }
            }
           
            if(intval($value['relation_id'])==19){
                if($value['gender']=='male'){
                    $value['title'] = "Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Daughter";
                }
            }
            if(intval($value['relation_id'])== 20){ 
                    $value['title'] = "Son's Wife";
                    $patnerFlag = true;   
            }

            if(intval($value['relation_id'])==21){
                    $value['title'] = "Daughter";              
            }

            if(intval($value['relation_id'])==22){
                $value['title'] = "Daughter's Husband";
                 $patnerFlag = true;   
            }


            if(intval($value['relation_id'])==24){
                if($value['gender']=='male'){
                    $value['title'] = "Grand Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Grand Daughter";
                }
            }

            if(intval($value['relation_id'])==25){
                if($value['gender']=='male'){
                    $value['title'] = "Grand Son";
                }
                elseif($value['gender']=='female'){
                    $value['title'] = "Grand Daughter";
                }
            }
            if(intval($value['relation_id'])==26){
                $value['title'] = "Husband";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==27){
                $value['title'] = "Partner";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==28){
                $value['title'] = "Son's Husband";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==29){
                $value['title'] = "Son's Partner";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==30){
                $value['title'] = "Daughter's Wife";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==31){
                $value['title'] = "Daughter's Partner";
                 $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==32){
                $value['title'] = "Grand Son's Wife";
                $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==33){
                $value['title'] = "Grand Son's Husband";
                $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==34){
                $value['title'] = "Grand Son's Partner";
                $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==35){
                $value['title'] = "Grand Daughter's Husband";
                $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==36){
                $value['title'] = "Grand Daughter's Wife";
                $patnerFlag = true;   
            }
            if(intval($value['relation_id'])==37){
                $value['title'] = "Grand Daughter's Partner";
                $patnerFlag = true;   
            }

            $value['photo'] = $value['img'];
            // temp user image show
            if($value['relation_id']==13){
                $value['title'] = "Self";
                if ($searched_m_User->photo != null) {
                    $value['photo'] = URL('storage/'.$searched_m_User->photo);
                }
                else{
                    $value['photo'] = $value['img'];
                }
           }
            $value['addr'] = $value['living'];
           
            $value['cn'] = "";
            unset($value->dob);
            unset($value->first_name);
            unset($value->last_name);
            unset($value->email);
            unset($value->user_id);
            unset($value->status);
            unset($value->created_at);
            unset($value->updated_at);
            unset($value->deleted_at);
            array_unshift($new_datas,$value);
        }
        //patner not exists
        if(!$patnerFlag){
            $d_data = [];
            $d_data['id'] = -14;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 14;  
            $d_data['title'] = 'Partner';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($patner_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // mother not exists
        if(($fatherFlag && !$motherFlag)){
            $d_data = [];
            $d_data['id'] = -10;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 10;  
            $d_data['title'] = 'Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // father not exists
        if(!$fatherFlag && $motherFlag){
            $d_data = [];
            $d_data['id'] = -9;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 9;  
            $d_data['title'] = 'Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // mother in law not exists
        if(($fatherInLawFlag && !$motherInLawFlag)){
            $d_data = [];
            $d_data['id'] = -12;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 12;  
            $d_data['title'] = 'Mother In Law';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($father_in_law_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // father in law not exists
        if(!$fatherInLawFlag && $motherInLawFlag){
            $d_data = [];
            $d_data['id'] = -11;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 11;  
            $d_data['title'] = 'Father In Law'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($mother_in_law_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // paternal grand mother not exists
        if(($paternal_grand_fatherFlag && !$paternal_grand_motherFlag)){
            $d_data = [];
            $d_data['id'] = -2;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 2;  
            $d_data['title'] = 'Paternal Grand Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($paternal_grand_father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        //paternal grandfather not exists
        if(!$paternal_grand_fatherFlag && $paternal_grand_motherFlag){
            $d_data['id'] = -1;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 1;  
            $d_data['title'] = 'Paternal Grand Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($paternal_grand_mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        // maternal grand mother not exists
        if(($maternal_grand_fatherFlag && !$maternal_grand_motherFlag)){
            $d_data = [];
            $d_data['id'] = -4;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'female';  
            $d_data['relation_id'] = 4;  
            $d_data['title'] = 'Maternal Grand Mother';  
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_female.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($maternal_grand_father_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        //maternal grandfather not exists
        if(!$maternal_grand_fatherFlag && $maternal_grand_motherFlag){
            $d_data['id'] = -3;  
            $d_data['name'] = "Not Available";  
            $d_data['gender'] = 'male';  
            $d_data['relation_id'] = 3;  
            $d_data['title'] = 'Maternal Grand Father'; 
            $d_data['photo'] = URL::to('/').'/images/frontend/photo_man.png';
            $d_data['addr'] = "";
            $d_data['cn'] = "";
            foreach ($new_datas as $key => $value) {
                if($maternal_grand_mother_id == $value['id']){
                    $new_datas[$key]['pids'][0] = $d_data['id'];
                    $d_data['pids'][0] = $value['id'];
                    $d_data['addr'] = 1;
                }
            }
            array_unshift($new_datas,$d_data);
        }
        $main_person =  $datas[0];
        $friends_ids = FamilyTree::where('user_id', $request->id)->first();
        $friends = explode(",",$friends_ids->frnid); 
        return view('frontend.family-trees.vue_family_trees',compact(['new_datas','main_person','friends']));
    }






    /**
     * Handle a registration request for the application and sent mail to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {    
        try{
            if($request->living == 1){
                $rules = [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'relation_email'     => 'required',
                    'relation_id'     => 'required',
                    'day' => new dobRequired,
                    'gender' => 'required',
                    'living' => 'required'
                   
                ];
            }
            else{
                $rules = [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'relation_id'     => 'required',
                    'day' => new dobRequired,
                    'gender' => 'required',
                    'living' => 'required'
                   
                ];
            }
            $messages = [
                //            'title.required' => 'Title is required',
                        ];

            $this->validate($request, $rules, $messages);

            $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Failed To Add Family Member.',
            ];

            // male female check
      
            //male check
            if($request->gender=="female" && in_array($request->relation_id,[1,3,5,7,9,10,15,16,19,22,24,28,33,35])){
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "Please add a male user.",
                ];
            }
            //female check
            if($request->gender=="male" && in_array($request->relation_id,[2,4,6,8,11,12,17,18,20,21,25,30,32,36])){
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "Please add a female user.",
                ];
            }

            //user Male -> Request->Wife check
            if($request->relation_id == 14 && $request->gender=="male"){
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "Please add a female user.",
                ];
            }

            //user Female -> Request->Husband check
            if($request->relation_id == 26 && $request->gender=="female"){
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "Please add a male user.",
                ];
            }


            // check relation if he/she already connected or not =>start
            $new_email = $request->relation_email ?? substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0,10).'@storeetree.com';

            // user data in familytree
            $u = FamilyTree::where('user_id', Auth::user()->id)->first();
            // user id in damily tree
            $uid = FamilyTree::where('user_id',  Auth::user()->id)->pluck('id')[0];
            // 1.father mother patner
            


            // get user's Father/Mother/Partner Data
            $fa_mot_pat = FamilyTree::leftjoin('family_trees as father', 'father.id', '=', 'family_trees.fid')
                ->leftjoin('family_trees as mother', 'mother.id', '=', 'family_trees.mid')
                ->leftjoin('family_trees as patner', 'patner.id', '=', 'family_trees.pid')
                ->where('family_trees.user_id',  Auth::user()->id)
                ->first([
                    'father.email as father',
                    'father.id as father_id',
                    'mother.email as mother',
                    'mother.id as mother_id',
                    'patner.email as patner',
                    'patner.id as patner_id',
                ]);

            
            // request email == Father Email Check
            //1.1 father
            if ($fa_mot_pat->father == $new_email) {
                //You added him as a father 
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "You already added him as a father .",
                ];
            }
       
            // 1.1.0   get user's  paternal grand father and grand mother data
            $paternal_grand_father_mother = FamilyTree::leftjoin('family_trees as grand_father', 'grand_father.id', '=', 'family_trees.fid')
            ->leftjoin('family_trees as grand_mother', 'grand_mother.id', '=', 'family_trees.mid')
            ->where('family_trees.id', $fa_mot_pat->father_id)
            ->first([
                'grand_father.email as grand_father',
                'grand_father.id as grand_father_id',
                'grand_mother.email as grand_mother',
                'grand_mother.id as grand_mother_id',
            ]);


            // if user's  paternal grand father and grand mother Exists
            if ($paternal_grand_father_mother) {
                // 1.1.1 paternal grand father 
                // request email ==  GrandFather Email Check
                if ($paternal_grand_father_mother->grand_father == $new_email) {
                    //You added him as a father 
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as a paternal grand father.",
                    ];
                }
                // 1.1.2 paternal grand mother
                // request email ==  GrandMother Email Check
                if ($paternal_grand_father_mother->grand_mother == $new_email) {
                    //You added him as a father 
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added her as a paternal grand mother.",
                    ];
                }
            }
            //1.2 mother
            // request email == Mother Email Check
            if ($fa_mot_pat->mother == $new_email) {
                //You added her as a mother 
                return [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => "You already added her as a mother.",
                ];
            }
            

            // 1.2.0   get user's  maternal grand father and  grand mother  data
            $maternal_grand_father_mother = FamilyTree::leftjoin('family_trees as grand_father', 'grand_father.id', '=', 'family_trees.fid')
            ->leftjoin('family_trees as grand_mother', 'grand_mother.id', '=', 'family_trees.mid')
            ->where('family_trees.id', $fa_mot_pat->mother_id)
            ->first([
                'grand_father.email as grand_father',
                'grand_father.id as grand_father_id',
                'grand_mother.email as grand_mother',
                'grand_mother.id as grand_mother_id',
            ]);
   
            // if user's  maternal grand father and  grand mother  exists
            if ($maternal_grand_father_mother) {
                // 1.2.1 maternal grand father 
                // request email == maternal Grand Father Email Check
                if ($maternal_grand_father_mother->grand_father == $new_email) {
                    //You added him as a father 
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as a maternal grand father.",
                    ];
                }
                // 1.2.2 maternal grand mother
                // request email == maternal GrandMother Email Check
                if ($maternal_grand_father_mother->grand_mother == $new_email) {
                    //You added him as a father 
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as a maternal grand mother.",
                    ];
                }
            }


            if ($u->pid) {
                $partner = FamilyTree::where('id',$u->pid)->first();
                if ($partner->relation_id == 14 && $fa_mot_pat->patner == $new_email) {
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added her as Wife.",
                    ];
                }
                if ($partner->relation_id == 26 && $fa_mot_pat->patner == $new_email) {
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as Husband.",
                    ];
                }
                if ($partner->relation_id == 27 && $fa_mot_pat->patner == $new_email) {
                    if ($partner->gender == 'male') {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added him as Partner.",
                        ];
                    } else {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added her as Partner.",
                        ];
                    }
                    
                }
            }

            //1.3.0 child
            // if auth user mail
            // User's Child Check
            if ($u->gender == 'male') {
                $childs = FamilyTree::where('family_trees.fid', $uid)
                    ->get([
                        'email',
                        'id',
                        'gender'
                    ]);
            }

            // if auth user female
            // User's Child Check
            if ($u->gender == 'female') {
                $childs = FamilyTree::where('family_trees.mid', $uid)
                    ->get([
                        'email',
                        'id',
                        'gender'
                    ]);
            }

            // if Child Exists
            $child = [];
            $i = 0;
            foreach ($childs as $key => $value) {
                $key_name = 'child' . $i++;
                $child[$key_name] = $value->email;
                $child[$key_name . 'id'] = $value->id;

                // request->email ==  Child email Check
                if ($value->email == $new_email && $value->gender == 'male') {
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as a child.",
                    ];
                }
                // request->email ==  Child email Check
                if ($value->email == $new_email &&  $value->gender == 'female') {
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added her as a child.",
                    ];
                }

                // 1.3.1 grand son and daughter for son 

                // if Child male
                if ($value->gender == 'male') {

                    $grand_son_daughters = FamilyTree::where('family_trees.fid', $value->id)
                        ->get([
                            'email',
                            'id',
                            'gender'
                        ]);
                    foreach ($grand_son_daughters as $key => $value2) {

                        if ($value2->email == $new_email && $value2->gender == 'female') {
                            return [
                                'errMsgFlag' => true,
                                'msgFlag' => false,
                                'msg' => "You already added her as a grand daughter.",
                            ];
                        }
                        if ($value2->email == $new_email && $value2->gender == 'male') {
                            return [
                                'errMsgFlag' => true,
                                'msgFlag' => false,
                                'msg' => "You already added him as a grand son.",
                            ];
                        }
                    }
                }
                // 1.3.2 grand son and daughter for daughter 
                if ($value->gender == 'female') {
                    $grand_son_daughters = FamilyTree::where('family_trees.mid', $value->id)
                        ->get([
                            'email',
                            'id',
                            'gender'
                        ]);
                    foreach ($grand_son_daughters as $key => $value2) {
                        if ($value2->email == $new_email && $value2->gender == 'female') {
                            return [
                                'errMsgFlag' => true,
                                'msgFlag' => false,
                                'msg' => "You already added her as a grand daughter.",
                            ];
                        }
                        if ($value2->email == $new_email && $value2->gender == 'male') {
                            return [
                                'errMsgFlag' => true,
                                'msgFlag' => false,
                                'msg' => "You already added him as a grand son.",
                            ];
                        }
                    }
                }
            }
   
            // mother in law
            if($u->pid){
                $mother_in_law = FamilyTree::leftjoin('family_trees as mother_in_law', 'mother_in_law.id', '=', 'family_trees.mid')
                ->where('family_trees.id', $u->pid)->first([
                    'mother_in_law.id',
                    'mother_in_law.email'
                ]);
         
                if ($mother_in_law->email == $new_email) {
                    //You added her as a mother in law
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added her as a mother in law.",
                    ];
                }
            }
    

            if($u->pid){
                $father_in_law = FamilyTree::leftjoin('family_trees as father_in_law', 'father_in_law.id', '=', 'family_trees.fid')
                ->where('family_trees.id', $u->pid)->first([
                    'father_in_law.id',
                    'father_in_law.email'
                ])->first();
                if ($father_in_law->email == $new_email) {
                    //You added her as a father in law
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "You already added him as a father in law.",
                    ];
                }

                if($father_in_law){
                   
                    
                    $brother_sister_laws = FamilyTree::where('family_trees.fid', $father_in_law->id)
                    ->where('family_trees.id', '<>', $u->pid)
                    ->get([
                        'email',
                        'id',
                        'gender'
                    ]);
    
                 foreach ($brother_sister_laws as $key => $value) {
                    if ($value->email == $new_email && $value->gender == 'female') {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added her as a sisthher in law.",
                        ];
                    }
                    if ($value->email == $new_email && $value->gender == 'male') {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added him as a brother in law.",
                        ];
                    }
                }
                }
                else{
                    return [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => "Firstly add father in law.",
                    ];
                }

            }
            // father in law
            if($u->fid){
                $brother_sisters = FamilyTree::where('family_trees.fid', $u->fid)
                ->where('family_trees.id', '<>', $u->id)
                ->get([
                    'email',
                    'id',
                    'gender'
                ]);

                foreach ($brother_sisters as $key => $value) {
                    if ($value->email == $new_email && $value->gender == 'female') {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added her as a sister.",
                        ];
                    }
                    if ($value->email == $new_email && $value->gender == 'male') {
                        return [
                            'errMsgFlag' => true,
                            'msgFlag' => false,
                            'msg' => "You already added him as a brother.",
                        ];
                    }
                }
            }



            // ---------------------------Friend Check --------------
            if ($u->frnid) {
          
                $all_friends = explode(",",$u->frnid);
                
                foreach ($all_friends as $key => $friend) {

                        $user_friend = FamilyTree::where('id', $friend)->first();
                        $friend_email = $user_friend->email;
                        $friend_gender = $user_friend->gender;

                        if ( $friend_email == $request->relation_email) {
                            if ($friend_gender == "female") {
                                return [
                                    'errMsgFlag' => true,
                                    'msgFlag' => false,
                                    'msg' => "You already added her as a friend.",
                                ];
                            }
                            else {
                                return [
                                    'errMsgFlag' => true,
                                    'msgFlag' => false,
                                    'msg' => "You already added him as a friend.",
                                ];
                            }
                        }

                }

            }

            // check relation if he/she already connected or not =>end
                
               if($request->relation_id==1)
                    $responseData=$this->AddPaternalGrandFather($request);

                if($request->relation_id==2)
                    $responseData=$this->AddPaternalGrandMother($request);

                if($request->relation_id==3)
                    $responseData=$this->AddMaternalGrandFather($request);

                if($request->relation_id==4)
                    $responseData=$this->AddMaternalGrandMother($request);

                if($request->relation_id==5)
                    $responseData=$this->AddPaternalGrandFatherInLaw($request);

                if($request->relation_id==6)
                    $responseData=$this->AddPaternalGrandMotherInLaw($request);

                if($request->relation_id==7)
                    $responseData=$this->AddMaternalGrandFatherInLaw($request);

                if($request->relation_id==8)
                    $responseData=$this->AddMaternalGrandMotherInLaw($request);


                if($request->relation_id==9)
                    $responseData=$this->AddFather($request);

                if($request->relation_id==10)
                    $responseData=$this->AddFatherInLaw($request);

                if($request->relation_id==11)
                    $responseData=$this->AddMother($request);

                if($request->relation_id==12)
                    $responseData=$this->AddMotherInLaw($request);

                // if($request->relation_id==13)
                //     $responseData=$this->AddPaternalGrandFather($request);

                if($request->relation_id==14)
                    $responseData=$this->AddWife($request);

                if($request->relation_id==15)
                    $responseData=$this->AddBrother($request);

                if($request->relation_id==16)
                    $responseData=$this->AddBrotherInLaw($request);

                if($request->relation_id==17)
                    $responseData=$this->AddSister($request);

                if($request->relation_id==18)
                    $responseData=$this->AddSisterInLaw($request);


                if($request->relation_id==19)
                    $responseData=$this->AddSon($request);

                if($request->relation_id==20)
                    $responseData=$this->AddSonsWife($request);

                if($request->relation_id==21)
                    $responseData=$this->AddDaughter($request);


                if($request->relation_id==22)
                    $responseData=$this->AddDaughterHusband($request);

                if($request->relation_id==23)
                    $responseData=$this->AddFriend($request);

                if($request->relation_id==24)
                    $responseData=$this->AddGrandSon($request);

                if($request->relation_id==25)
                    $responseData=$this->AddGrandDaughter($request);

                if($request->relation_id==26)
                    $responseData=$this->AddHusband($request);

                if($request->relation_id==27)
                $responseData=$this->AddPartner($request);

                if($request->relation_id==28)
                $responseData=$this->AddSonsHusband($request);

                if($request->relation_id==29)
                $responseData=$this->AddSonsPartner($request);

                if($request->relation_id==30)
                    $responseData=$this->AddDaughterWife($request);

                if($request->relation_id==31)
                    $responseData=$this->AddDaughterPartner($request);
                    
                if($request->relation_id==32)
                $responseData=$this->AddGrandSonsWife($request);

                if($request->relation_id==33)
                $responseData=$this->AddGrandSonsHusband($request);

                if($request->relation_id==34)
                $responseData=$this->AddGrandSonsPartner($request);

                if($request->relation_id==35)
                $responseData=$this->AddGrandDaughtersHusband($request);

                if($request->relation_id==36)
                $responseData=$this->AddGrandDaughtersWife($request);

                if($request->relation_id==37)
                $responseData=$this->AddGrandDaughtersPartner($request);

                return response()->json($responseData);
        }
        catch(Exception $err){
            DB::rollBack();

            return response()->json(
                        [
                            'status'       => 'error3',
                        ]
                    );
        }
        
    }

    public function addAsNewFamilyTree($request,$newUser)
    {
        $isRelativeInfoExist=FamilyTree::where('email',strtolower($request->relation_email))->first();

        if(empty($isRelativeInfoExist)){

            $relativeInfo=new FamilyTree();

            $relativeInfo->first_name=$request->first_name;

            $relativeInfo->last_name=$request->last_name;

            $relativeInfo->email=$newUser->email;//strtolower(trim($request->relation_email));

            $relativeInfo->gender=$request->gender;

            $relativeInfo->user_id=$newUser->id;

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

            $relativeInfo->dob = $dateOfBirth;

            $relativeInfo->relation_id=$request->relation_id;

            $relativeInfo->status=1;

            $relativeInfo->living=$request->living;

            $relativeInfo->created_at=Carbon::now();

            $unique_password = 'default123';

            if($relativeInfo->save())
                $maildata = [
                    'mainuser' => Auth::user()->first_name." ".Auth::user()->last_name,
                    'username' => $request->first_name." ".$request->last_name,
                    'email' => $newUser->email,
                    'password' => $unique_password,
                ];
        
                Mail::to($request->relation_email)->send(new RegisterationEmail($maildata));
                return $relativeInfo;            
        }
        else{


            $relativeInfo=FamilyTree::find($isRelativeInfoExist->id);

            $relativeInfo->first_name=$request->first_name;

            $relativeInfo->last_name=$request->last_name;

            $relativeInfo->email=$newUser->email;//strtolower(trim($request->relation_email));

            $relativeInfo->gender=$request->gender;

            $relativeInfo->user_id=$newUser->id;

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

            $relativeInfo->dob = $dateOfBirth;

            $relativeInfo->relation_id=$request->relation_id;

            $relativeInfo->status=1;

            $relativeInfo->living=$request->living;

            $relativeInfo->created_at=Carbon::now();

            $unique_password = 'default123';

            if($relativeInfo->save())
                $maildata = [
                    'mainuser' => Auth::user()->first_name." ".Auth::user()->last_name,
                    'username' => $request->first_name." ".$request->last_name,
                    'email' => $newUser->email,
                    'password' => $unique_password,
                ];
        
                Mail::to($request->relation_email)->send(new RegisterationEmail($maildata));
                return $relativeInfo;
        }

    }


    public function addAsNewUser($request,$relatedUser)
    {        
        
        $main_user = User::where('email',$relatedUser->email)->first();

        if(!is_null($request->relation_email)){
            $relation_email=strtolower(trim($request->relation_email));
        }
        else{
            $relation_email=substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0,10).'@storeetree.com';
        }
        $isUserExist=User::where('email',strtolower(trim($request->relation_email)))->first();
        if (empty($isUserExist)) {
            
            $userInfo=new User();          
            $userInfo->first_name=$request->first_name;
            $userInfo->last_name=$request->last_name;
            $userInfo->email=$relation_email;//strtolower(trim($request->relation_email));
            $userInfo->gender=$request->gender;

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
            $userInfo->dob = $dateOfBirth;
            $unique_password = 'default123';
            $userInfo->password=bcrypt($unique_password);
            $userInfo->country_id=$main_user->country_id;
            $userInfo->connected_period=$main_user->connected_period;
            $userInfo->status=1;
            $userInfo->verified=0;
            $userInfo->created_at=Carbon::now();
            if($userInfo->save()){
                return $userInfo;
            }
            else{
                return false;
            }
        }
        else
            return $isUserExist;
    }


    public function isFamilyTreeExist($request)
    {
        $familyTree=FamilyTree::where('email',strtolower($request->relation_email))->first();

        if (!empty($familyTree)) 
            return $familyTree;
        else
            return false;
    }
    public function AddPaternalGrandFather($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->fid)){

                $fatherInfo=FamilyTree::where('id',$myFamilyTree->fid)->first();
                if (!empty($fatherInfo)) {

                   if(is_null($fatherInfo->fid)){

                        $parentalGrandFatherInfo=$this->isFamilyTreeExist($request);

                        if($parentalGrandFatherInfo!=false){

                            $fatherInfo->fid=$parentalGrandFatherInfo->id;

                            if($fatherInfo->save()){

                                if(!is_null($fatherInfo->mid))
                                    $this->connectPartner($fatherInfo->fid,$fatherInfo->mid);

                                return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Family Member Added Successfully.',
                                    ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                        }
                        else{
                            $newUser=$this->addAsNewUser($request,$fatherInfo);
                            if($newUser!=false){
                                
                                $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                                if($newFamilyTree!=false){

                                    $fatherInfo->fid=$newFamilyTree->id;

                                    if($fatherInfo->save()){

                                        if(!is_null($fatherInfo->mid))
                                            $this->connectPartner($fatherInfo->fid,$fatherInfo->mid);

                                        return [
                                            'errMsgFlag'=>false,
                                            'msgFlag'=>true,
                                            'msg'=>'Family Member Added Successfully.',
                                        ];
                                    }
                                    else{
                                        return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Family Member Please Try Again.',
                                        ];
                                    }
                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Family Member Please Try Again.',
                                    ];
                                }

                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                            
                        }

                   }
                   else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Please Add Your Father As A Member First.',
                        ];
                   }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add Your Father As A Member First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add Your Father As A Member First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddPaternalGrandMother($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->fid)){

                $fatherInfo=FamilyTree::where('id',$myFamilyTree->fid)->first();

                if (!empty($fatherInfo)) {

                    if (!is_null($fatherInfo->fid)) {

                       if(is_null($fatherInfo->mid)){

                            $parentalGrandMotherInfo=$this->isFamilyTreeExist($request);

                            if($parentalGrandMotherInfo!=false){

                                $fatherInfo->mid=$parentalGrandMotherInfo->id;

                                if($fatherInfo->save()){

                                    if(!is_null($fatherInfo->fid))
                                        $this->connectPartner($fatherInfo->fid,$fatherInfo->mid);

                                    return [
                                            'errMsgFlag'=>false,
                                            'msgFlag'=>true,
                                            'msg'=>'Family Member Added Successfully.',
                                        ];
                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Family Member Please Try Again.',
                                    ];
                                }
                            }
                            else{
                                
                                $newUser=$this->addAsNewUser($request,$fatherInfo);

                                if($newUser!=false){
                                    
                                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                                    if($newFamilyTree!=false){

                                        $fatherInfo->mid=$newFamilyTree->id;

                                        if($fatherInfo->save()){

                                            if(!is_null($fatherInfo->fid))
                                                $this->connectPartner($fatherInfo->fid,$fatherInfo->mid);

                                            return [
                                                'errMsgFlag'=>false,
                                                'msgFlag'=>true,
                                                'msg'=>'Family Member Added Successfully.',
                                            ];
                                        }
                                        else{
                                            return [
                                                'errMsgFlag'=>true,
                                                'msgFlag'=>false,
                                                'msg'=>'Failed To Add Family Member Please Try Again.',
                                            ];
                                        }
                                    }
                                    else{
                                        return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Family Member Please Try Again.',
                                        ];
                                    }

                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Family Member Please Try Again.',
                                    ];
                                }
                                
                            }

                       }
                       else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Your Grand Mother info already added.',
                            ];
                        }
                   } 
                    else {
                        return [
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Please Add Your Grand Father in Family Tree First',
                                ];
                    }                   
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add Your Father As A Member First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add Your Father As A Member First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddMaternalGrandFather($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->mid)){

                $motherInfo=FamilyTree::where('id',$myFamilyTree->mid)->first();

                if (!empty($motherInfo)) {

                   if(is_null($motherInfo->fid)){

                        $maternalGrandFatherInfo=$this->isFamilyTreeExist($request);

                        if($maternalGrandFatherInfo!=false){

                            $motherInfo->fid=$maternalGrandFatherInfo->id;

                            if($fatherInfo->save()){

                                if(!is_null($motherInfo->mid))
                                    $this->connectPartner($motherInfo->fid,$motherInfo->mid);

                                return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Family Member Added Successfully.',
                                    ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                        }
                        else{
                            
                            $newUser=$this->addAsNewUser($request,$myFamilyTree);

                            if($newUser!=false){
                                
                                $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                                if($newFamilyTree!=false){

                                    $motherInfo->fid=$newFamilyTree->id;

                                    if($motherInfo->save()){

                                        if(!is_null($motherInfo->mid))
                                            $this->connectPartner($motherInfo->fid,$motherInfo->mid);

                                        return [
                                            'errMsgFlag'=>false,
                                            'msgFlag'=>true,
                                            'msg'=>'Family Member Added Successfully.',
                                        ];
                                    }
                                    else{
                                        return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Family Member Please Try Again.',
                                        ];
                                    }
                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Family Member Please Try Again.',
                                    ];
                                }

                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                            
                        }

                   }
                   else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Please Add Your Father As A Member First.',
                        ];
                   }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add Your Father As A Member First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add Your Father As A Member First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddMaternalGrandMother($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->mid)){

                $motherInfo=FamilyTree::where('id',$myFamilyTree->mid)->first();

                if (!empty($motherInfo)) {

                   if(is_null($motherInfo->mid)){

                        $maternalGrandMotherInfo=$this->isFamilyTreeExist($request);

                        if($maternalGrandMotherInfo!=false){

                            $motherInfo->mid=$maternalGrandMotherInfo->id;

                            if($motherInfo->save()){

                                if(!is_null($motherInfo->fid))
                                    $this->connectPartner($motherInfo->fid,$motherInfo->mid);

                                return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Family Member Added Successfully.',
                                    ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                        }
                        else{
                            
                            $newUser=$this->addAsNewUser($request,$myFamilyTree);

                            if($newUser!=false){
                                
                                $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                                if($newFamilyTree!=false){

                                    $motherInfo->mid=$newFamilyTree->id;

                                    if($motherInfo->save()){

                                        if(!is_null($motherInfo->fid))
                                            $this->connectPartner($motherInfo->fid,$motherInfo->mid);

                                        return [
                                            'errMsgFlag'=>false,
                                            'msgFlag'=>true,
                                            'msg'=>'Family Member Added Successfully.',
                                        ];
                                    }
                                    else{
                                        return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Family Member Please Try Again.',
                                        ];
                                    }
                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Family Member Please Try Again.',
                                    ];
                                }

                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Family Member Please Try Again.',
                                ];
                            }
                            
                        }

                   }
                   else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Please Add Your Father As A Member First.',
                        ];
                   }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add Your Father As A Member First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add Your Father As A Member First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddFather($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(is_null($myFamilyTree->fid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $fatherInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($fatherInfo!=false) {

                        $myFamilyTree->fid=$fatherInfo->id;

                        if($myFamilyTree->save()){

                            if(!is_null($myFamilyTree->mid))
                                $this->connectPartner($fatherInfo->id,$myFamilyTree->mid);

                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Father Info Added Successfully.',
                            ];
                        }
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your father.',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Father.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'You have already added your father.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'You have already added your father.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddMother($request)
    {

        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();
    
        if(!empty($myFamilyTree)) {

             if(!is_null($myFamilyTree->fid)){

                    if(is_null($myFamilyTree->mid)){
                        
                        $newUser=$this->addAsNewUser($request,$myFamilyTree);
                        
                        if ($newUser!=false) {
                            
                            $motherInfo=$this->addAsNewFamilyTree($request,$newUser);

                            if ($motherInfo!=false) {

                                $myFamilyTree->mid=$motherInfo->id;

                                if($myFamilyTree->save()){

                                    if(!is_null($myFamilyTree->fid))
                                        $this->connectPartner($myFamilyTree->fid,$motherInfo->id);

                                     return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Mother info Added Successfully.',
                                    ];
                                }
                                else{
                                     return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'You have already added your mother info.',
                                    ];
                                }
                            }
                            else{
                                 return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add mother info.',
                                ];
                            }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your mother info.',
                            ];
                        }

                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'You have already added your mother info.',
                        ];
                    }
                }
                 else{ 
                     return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Add Father in Family Tree  First',
                            ];

                }     
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddPartner($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(is_null($myFamilyTree->pid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $partnerInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($partnerInfo!=false) {

                        $myFamilyTree->pid=$partnerInfo->id;

                        if($myFamilyTree->save()){
                            $this->connectPartner($myFamilyTree->id,$partnerInfo->id);
                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Partner info Added Successfully.',
                            ];
                        }
     
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your partner info.',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add partner info.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'You have already added your partner info.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'You have already added your partner info.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddWife($request){
       

        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(is_null($myFamilyTree->pid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $partnerInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($partnerInfo!=false) {

                        $myFamilyTree->pid=$partnerInfo->id;
                        $myFamilyTree->relation_id=14;

                        if($myFamilyTree->save()){
                            $this->connectPartner($myFamilyTree->id,$partnerInfo->id);
                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Wife info Added Successfully.',
                            ];
                        }
     
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your Wife info.',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Wife info.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'You have already added your Wife info.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'You have already added your Wife info.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddHusband($request)
    {
        
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(is_null($myFamilyTree->pid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $partnerInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($partnerInfo!=false) {

                        $myFamilyTree->pid=$partnerInfo->id;

                        if($myFamilyTree->save()){
                            $this->connectPartner($myFamilyTree->id,$partnerInfo->id);
                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Husband info Added Successfully.',
                            ];
                        }
     
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your Husband info.',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Husband info.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'You have already added your Husband info.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'You have already added your Husband info.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddFatherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(is_null($partnerInfo->fid)){

                        $newUser=$this->addAsNewUser($request,$myFamilyTree);

                        if ($newUser!=false) {
                            
                            $fatherInfo=$this->addAsNewFamilyTree($request,$newUser);

                            if ($fatherInfo!=false) {

                                $partnerInfo->fid=$fatherInfo->id;

                                if($partnerInfo->save()){

                                    if(!is_null($partnerInfo->mid))
                                        $this->connectPartner($partnerInfo->fid,$partnerInfo->mid);

                                     return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Father In Law info Added Successfully.',
                                    ];
                                }
                                else{
                                     return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Father In Law',
                                    ];
                                }
                            }
                            else{
                                 return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Father In Law',
                                ];
                            }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your Father In Law',
                            ];
                        }


                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Father In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    public function AddPaternalGrandFatherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->fid)){
                        
                        $partnerFatherInfo=FamilyTree::find($partnerInfo->fid);

                        if (!empty($partnerFatherInfo)) {
                        
                                $newUser=$this->addAsNewUser($request,$partnerFatherInfo);

                                if ($newUser!=false) {
                                    
                                    $parentalGrandFatherInLawInfo=$this->addAsNewFamilyTree($request,$newUser);

                                    if ($parentalGrandFatherInLawInfo!=false) {

                                        $partnerFatherInfo->fid=$parentalGrandFatherInLawInfo->id;

                                        if($partnerFatherInfo->save()){

                                            if(!is_null($partnerFatherInfo->mid))
                                                $this->connectPartner($partnerFatherInfo->fid,$partnerFatherInfo->mid);

                                             return [
                                                'errMsgFlag'=>false,
                                                'msgFlag'=>true,
                                                'msg'=>'Father In Law info Added Successfully.',
                                            ];
                                        }
                                        else{
                                             return [
                                                'errMsgFlag'=>true,
                                                'msgFlag'=>false,
                                                'msg'=>'Failed To Add Father In Law',
                                            ];
                                        }
                                    }
                                    else{
                                         return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Father In Law',
                                        ];
                                    }

                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'You have already added your Father In Law',
                                    ];
                                }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Add Father In Law First.',
                            ];
                        }

                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Father In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    public function AddPaternalGrandMotherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->fid)){
                        
                        $partnerFatherInfo=FamilyTree::find($partnerInfo->fid);

                        if (!empty($partnerFatherInfo)) {
                        
                                $newUser=$this->addAsNewUser($request,$partnerFatherInfo);

                                if ($newUser!=false) {
                                    
                                    $parentalGrandMotherInLawInfo=$this->addAsNewFamilyTree($request,$newUser);

                                    if ($parentalGrandMotherInLawInfo!=false) {

                                        $partnerFatherInfo->mid=$parentalGrandMotherInLawInfo->id;

                                        if($partnerFatherInfo->save()){

                                            if(!is_null($partnerFatherInfo->fid))
                                                $this->connectPartner($partnerFatherInfo->fid,$partnerFatherInfo->mid);

                                             return [
                                                'errMsgFlag'=>false,
                                                'msgFlag'=>true,
                                                'msg'=>'Father In Law info Added Successfully.',
                                            ];
                                        }
                                        else{
                                             return [
                                                'errMsgFlag'=>true,
                                                'msgFlag'=>false,
                                                'msg'=>'Failed To Add Father In Law',
                                            ];
                                        }
                                    }
                                    else{
                                         return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Father In Law',
                                        ];
                                    }

                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'You have already added your Father In Law',
                                    ];
                                }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Add Father In Law First.',
                            ];
                        }

                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Father In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddMotherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(is_null($partnerInfo->mid)){

                        $newUser=$this->addAsNewUser($request,$partnerInfo);

                        if ($newUser!=false) {
                            
                            $motherInfo=$this->addAsNewFamilyTree($request,$newUser);

                            if ($motherInfo!=false) {

                                $partnerInfo->mid=$motherInfo->id;

                                if($partnerInfo->save()){

                                    if(!is_null($partnerInfo->fid))
                                        $this->connectPartner($partnerInfo->fid,$partnerInfo->mid);

                                     return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Mother In Law info Added Successfully.',
                                    ];
                                }
                                else{
                                     return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Mother In Law',
                                    ];
                                }
                            }
                            else{
                                 return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Mother In Law',
                                ];
                            }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your mother In Law',
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Mother In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddMaternalGrandFatherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->mid)){
                        
                        $partnerMotherInfo=FamilyTree::find($partnerInfo->mid);

                        if (!empty($partnerMotherInfo)) {
                        
                                $newUser=$this->addAsNewUser($request,$partnerMotherInfo);

                                if ($newUser!=false) {
                                    
                                    $maternalGrandFatherInLawInfo=$this->addAsNewFamilyTree($request,$newUser);

                                    if ($maternalGrandFatherInLawInfo!=false) {

                                        $partnerMotherInfo->fid=$maternalGrandFatherInLawInfo->id;

                                        if($partnerMotherInfo->save()){

                                            if(!is_null($partnerMotherInfo->mid))
                                                $this->connectPartner($partnerMotherInfo->fid,$partnerMotherInfo->mid);

                                             return [
                                                'errMsgFlag'=>false,
                                                'msgFlag'=>true,
                                                'msg'=>'Father In Law info Added Successfully.',
                                            ];
                                        }
                                        else{
                                             return [
                                                'errMsgFlag'=>true,
                                                'msgFlag'=>false,
                                                'msg'=>'Failed To Add Father In Law',
                                            ];
                                        }
                                    }
                                    else{
                                         return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Father In Law',
                                        ];
                                    }

                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'You have already added your Father In Law',
                                    ];
                                }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Add Father In Law First.',
                            ];
                        }

                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Father In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    public function AddMaternalGrandMotherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->mid)){
                        
                        $partnerMotherInfo=FamilyTree::find($partnerInfo->mid);

                        if (!empty($partnerMotherInfo)) {
                        
                                $newUser=$this->addAsNewUser($request,$partnerMotherInfo);

                                if ($newUser!=false) {
                                    
                                    $metarnalGrandMotherInLawInfo=$this->addAsNewFamilyTree($request,$newUser);

                                    if ($metarnalGrandMotherInLawInfo!=false) {

                                        $partnerMotherInfo->mid=$metarnalGrandMotherInLawInfo->id;

                                        if($partnerMotherInfo->save()){

                                            if(!is_null($partnerMotherInfo->fid))
                                                $this->connectPartner($partnerMotherInfo->fid,$partnerMotherInfo->mid);

                                             return [
                                                'errMsgFlag'=>false,
                                                'msgFlag'=>true,
                                                'msg'=>'Father In Law info Added Successfully.',
                                            ];
                                        }
                                        else{
                                             return [
                                                'errMsgFlag'=>true,
                                                'msgFlag'=>false,
                                                'msg'=>'Failed To Add Father In Law',
                                            ];
                                        }
                                    }
                                    else{
                                         return [
                                            'errMsgFlag'=>true,
                                            'msgFlag'=>false,
                                            'msg'=>'Failed To Add Father In Law',
                                        ];
                                    }

                                }
                                else{
                                    return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'You have already added your Father In Law',
                                    ];
                                }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Add Father In Law First.',
                            ];
                        }

                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Father In Law Is Already Added.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddBrother($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->fid) || !is_null($myFamilyTree->mid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $brotherInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($brotherInfo!=false) {

                        $brotherInfo=FamilyTree::find($brotherInfo->id);

                        $brotherInfo->fid=$myFamilyTree->fid;

                        $brotherInfo->mid=$myFamilyTree->mid;

                        if($brotherInfo->save()){

                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Brother Info Added Successfully.',
                            ];
                        }
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Failed To Add Brother Info',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Brother Info.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Failed To Add Brother Info.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Parents First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    public function AddSister($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->fid) || !is_null($myFamilyTree->mid)){
                
                $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {
                    
                    $sisterInfo=$this->addAsNewFamilyTree($request,$newUser);

                    if ($sisterInfo!=false) {

                        $sisterInfo=FamilyTree::find($sisterInfo->id);

                        $sisterInfo->fid=$myFamilyTree->fid;

                        $sisterInfo->mid=$myFamilyTree->mid;

                        if($sisterInfo->save()){

                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Sister Info Added Successfully.',
                            ];
                        }
                        else{
                             return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'Failed To Add Sister Info',
                            ];
                        }
                    }
                    else{
                         return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Sister Info.',
                        ];
                    }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Failed To Add Sister Info.',
                    ];
                }

            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Parents First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddSon($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            $newUser=$this->addAsNewUser($request,$myFamilyTree);

            if ($newUser!=false) {
                
                $sonInfo=$this->addAsNewFamilyTree($request,$newUser);
                
                if($sonInfo!=false){
                    $sonInfo=FamilyTree::find($sonInfo->id);

                    if($myFamilyTree->gender=='male'){
                        $sonInfo->fid=$myFamilyTree->id;

                        if(!is_null($myFamilyTree->pid))
                            $sonInfo->mid=$myFamilyTree->pid;

                    }
                    if($myFamilyTree->gender=='female'){
                        
                        $sonInfo->mid=$myFamilyTree->id;

                        if(!is_null($myFamilyTree->pid))
                            $sonInfo->fid=$myFamilyTree->pid;
                    }

                    if($sonInfo->save()){
                        return [
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Son Info Added Successfully.',
                        ];
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Son Info.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Failed To Add Son Info.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Failed To Add Son Info.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddDaughter($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            $newUser=$this->addAsNewUser($request,$myFamilyTree);

            if ($newUser!=false) {
                
                $daughterInfo=$this->addAsNewFamilyTree($request,$newUser);
                
                if($daughterInfo!=false){
                    $daughterInfo=FamilyTree::find($daughterInfo->id);
                    
                    if($myFamilyTree->gender=='male'){
                        $daughterInfo->fid=$myFamilyTree->id;

                        if(!is_null($myFamilyTree->pid))
                            $daughterInfo->mid=$myFamilyTree->pid;

                    }
                    if($myFamilyTree->gender=='female'){
                        
                        $daughterInfo->mid=$myFamilyTree->id;

                        if(!is_null($myFamilyTree->pid))
                            $daughterInfo->fid=$myFamilyTree->pid;
                    }

                    if($daughterInfo->save()){
                        return [
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Daughter Info Added Successfully.',
                        ];
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>'Failed To Add Daughter Info.',
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Failed To Add Daughter Info.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Failed To Add Daughter Info.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    public function AddDaughterHusband($request)
    {
        
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $daughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($daughterInfo)){
                if(is_null($daughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$daughterInfo);

                  if($newUser!=false) {
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $daughterInfo->pid=$newFamilyTree->id;
                        

                        if($daughterInfo->save()){
                            
                            $daughterHusbandInfo=FamilyTree::find($newFamilyTree->id);

                            $daughterHusbandInfo->pid=$daughterInfo->id;
                            $daughterHusbandInfo->relation_id=26;                          

                            if($daughterHusbandInfo->save()){
                                $this->connectPartner($daughterInfo->id , $daughterInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Daughter's Husband Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Daughter's Husband.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Daughter's Husband.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Husband.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Husband.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Daughter's Husband Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddDaughterWife($request)
    {
        
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $daughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($daughterInfo)){
                if(is_null($daughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$daughterInfo);

                  if($newUser!=false) {
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $daughterInfo->pid=$newFamilyTree->id;
                        

                        if($daughterInfo->save()){
                            
                            $daughterWifeInfo=FamilyTree::find($newFamilyTree->id);

                            $daughterWifeInfo->pid=$daughterInfo->id;
                            $daughterWifeInfo->relation_id=14;                          

                            if($daughterWifeInfo->save()){
                                $this->connectPartner($daughterInfo->id , $daughterInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Daughter's Wife Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Daughter's Wife.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Daughter's Wife.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Wife.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Wife.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Daughter's Wife Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddDaughterPartner($request)
    {
        
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $daughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($daughterInfo)){
                if(is_null($daughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$daughterInfo);

                  if($newUser!=false) {
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $daughterInfo->pid=$newFamilyTree->id;
                        

                        if($daughterInfo->save()){
                            
                            $daughterPartnerInfo=FamilyTree::find($newFamilyTree->id);
                            $daughterPartnerInfo->pid=$daughterInfo->id;
                            $daughterPartnerInfo->relation_id=27;                          

                            if($daughterPartnerInfo->save()){
                                $this->connectPartner($daughterInfo->id , $daughterInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Daughter's Partner Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Daughter's Partner.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Daughter's Partner.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Partner.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Daughter's Partner.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Daughter's Wife Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddSonsWife($request)
    {
        
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $sonInfo=FamilyTree::find($request->connect_with);

           if(!empty($sonInfo)){
                if(is_null($sonInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$sonInfo);

                  if($newUser!=false) {
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $sonInfo->pid=$newFamilyTree->id;

                        if($sonInfo->save()){
                            
                            $sonWifeInfo=FamilyTree::find($newFamilyTree->id);

                            $sonWifeInfo->pid=$sonInfo->id;
                            $sonWifeInfo->relation_id=14;

                            if($sonWifeInfo->save()){
                                $this->connectPartner($sonInfo->id , $sonInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Son's Wife Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Son's Wife.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Son's Wife.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Wife.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Wife.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Son's Wife Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddSonsHusband($request)
    {
               
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $sonInfo=FamilyTree::find($request->connect_with);
           if(!empty($sonInfo)){
                if(is_null($sonInfo->pid)){
                  $newUser=$this->addAsNewUser($request,$sonInfo);
      
                  if($newUser!=false) { 
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $sonInfo->pid=$newFamilyTree->id;

                        if($sonInfo->save()){
                            
                            $sonHusbandInfo=FamilyTree::find($newFamilyTree->id);

                            $sonHusbandInfo->pid=$sonInfo->id;
                            $sonHusbandInfo->relation_id=26;

                            if($sonHusbandInfo->save()){
                                $this->connectPartner($sonInfo->id , $sonInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Son's Husband Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Son's Husband.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Son's Husband.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Husband.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Husband.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Son's Husband Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddSonsPartner($request)
    {               
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $sonInfo=FamilyTree::find($request->connect_with);
           if(!empty($sonInfo)){
                if(is_null($sonInfo->pid)){
                  $newUser=$this->addAsNewUser($request,$sonInfo);
      
                  if($newUser!=false) { 
                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $sonInfo->pid=$newFamilyTree->id;

                        if($sonInfo->save()){
                            
                            $sonPartnerInfo=FamilyTree::find($newFamilyTree->id);

                            $sonPartnerInfo->pid=$sonInfo->id;
                            $sonPartnerInfo->relation_id=27;

                            if($sonPartnerInfo->save()){
                                $this->connectPartner($sonInfo->id , $sonInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Son's Partner Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Son's Partner.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Son's Partner.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Partner.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Son's Partner.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Son's Partner Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandSon($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $childInfo=FamilyTree::find($request->connect_with);

           if(!empty($childInfo)){
            
              $newUser=$this->addAsNewUser($request,$childInfo);

              if($newUser!=false) {
               
                $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                if($newFamilyTree!=false){
                        
                 $grandChildInfo=FamilyTree::find($newFamilyTree->id);
                    

                    if($childInfo->gender=='male'){
                        $grandChildInfo->fid=$childInfo->id;
                        if(!is_null($childInfo->mid))
                            $grandChildInfo->mid=$childInfo->pid;
                    }

                    if($childInfo->gender=='female'){
                        $grandChildInfo->mid=$childInfo->id;
                        if(!is_null($childInfo->mid))
                            $grandChildInfo->fid=$childInfo->pid;
                    }

                    if($grandChildInfo->save()){
                        return [
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>"Grand Son Added Successfully.",
                        ];
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son.",
                        ];
                    }
                   
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Failed To Add Grand Son.",
                    ];
                }
              }
              else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Failed To Add Grand Son.",
                    ];
              }
               
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Child First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandSonsWife($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandSonInfo=FamilyTree::find($request->connect_with);

           if(!empty($grandSonInfo)){
                if(is_null($grandSonInfo->pid)){
                    
                  $newUser=$this->addAsNewUser($request,$grandSonInfo);
                  
                  if($newUser!=false) {
        
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandSonInfo->pid=$newFamilyTree->id;

                        if($grandSonInfo->save()){
                            
                            $grandSonWifeInfo=FamilyTree::find($newFamilyTree->id);

                            $grandSonWifeInfo->pid=$grandSonInfo->id;
                            $grandSonWifeInfo->relation_id=14;
                            if($grandSonWifeInfo->save()){
                                $this->connectPartner($grandSonInfo->id , $grandSonInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Son's Wife Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Son's Wife.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Son's Wife.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Wife.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Wife.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Son's Wife Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandSonsHusband($request)
    {
        // First get connected with 
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandSonInfo=FamilyTree::find($request->connect_with);
    
           if(!empty($grandSonInfo)){
                if(is_null($grandSonInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$grandSonInfo);
        
                  if($newUser!=false) {
                     
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandSonInfo->pid=$newFamilyTree->id;

                        if($grandSonInfo->save()){
                            
                            $grandSonHusbandInfo=FamilyTree::find($newFamilyTree->id);

                            $grandSonHusbandInfo->pid=$grandSonInfo->id;
                            $grandSonHusbandInfo->relation_id=26;
                            if($grandSonHusbandInfo->save()){
                                $this->connectPartner($grandSonInfo->id , $grandSonInfo->pid);

                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Son's Husband Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Son's Husband.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Son's Husband.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Husband.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Husband.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Son's Husband Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandSonsPartner($request)
    {
        

        // First get connected with 
        $currentUser=auth()->user();

      
        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandSonInfo=FamilyTree::find($request->connect_with);
       
           if(!empty($grandSonInfo)){
                if(is_null($grandSonInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$grandSonInfo);
        
                  if($newUser!=false) {
        
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandSonInfo->pid=$newFamilyTree->id;

                        if($grandSonInfo->save()){
                            
                            $grandSonPartnerInfo=FamilyTree::find($newFamilyTree->id);

                            $grandSonPartnerInfo->pid=$grandSonInfo->id;
                            $grandSonPartnerInfo->relation_id=27;
                            if($grandSonPartnerInfo->save()){
                                $this->connectPartner($grandSonInfo->id , $grandSonInfo->pid);

                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Son's Partner Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Son's Partner.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Son's Partner.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Partner.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Son's Partner.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Son's Partner Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Son First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandDaughter($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $childInfo=FamilyTree::find($request->connect_with);

           if(!empty($childInfo)){
            
              $newUser=$this->addAsNewUser($request,$childInfo);

              if($newUser!=false) {
               
                $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                if($newFamilyTree!=false){
                        
                    $grandChildInfo=FamilyTree::find($newFamilyTree->id);
                    
                    if($childInfo->gender=='male'){
                        $grandChildInfo->fid=$childInfo->id;
                        if(!is_null($childInfo->mid))
                            $grandChildInfo->mid=$childInfo->pid;
                    }

                    if($childInfo->gender=='female'){
                        $grandChildInfo->mid=$childInfo->id;
                        if(!is_null($childInfo->mid))
                            $grandChildInfo->fid=$childInfo->pid;
                    }

                    if($grandChildInfo->save()){
                        return [
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>"Grand Daughter Added Successfully.",
                        ];
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter.",
                        ];
                    }
                   
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Failed To Add Grand Daughter.",
                    ];
                }
              }
              else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Failed To Add Grand Daughter.",
                    ];
              }
               
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Child First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddGrandDaughtersHusband($request)
    {
       

        // First get connected with 
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandDaughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($grandDaughterInfo)){
                if(is_null($grandDaughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$grandDaughterInfo);
  

                  if($newUser!=false) {

                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandDaughterInfo->pid=$newFamilyTree->id;

                        if($grandDaughterInfo->save()){
                            
                            $grandDaughtersHusbandInfo=FamilyTree::find($newFamilyTree->id);

                            $grandDaughtersHusbandInfo->pid=$grandDaughterInfo->id;
                            $grandDaughtersHusbandInfo->relation_id=26;
                            if($grandDaughtersHusbandInfo->save()){
                                $this->connectPartner($grandDaughterInfo->id , $grandDaughterInfo->pid);

                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Daughter's Husband Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Daughter's Husband.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Daughter's Husband.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Husband.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Husband.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Daughter's Husband Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }
    
    public function AddGrandDaughtersWife($request)
    {
       

        // First get connected with 
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandDaughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($grandDaughterInfo)){
                if(is_null($grandDaughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$grandDaughterInfo);
  

                  if($newUser!=false) {

                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandDaughterInfo->pid=$newFamilyTree->id;

                        if($grandDaughterInfo->save()){
                            
                            $grandDaughtersWifeInfo=FamilyTree::find($newFamilyTree->id);

                            $grandDaughtersWifeInfo->pid=$grandDaughterInfo->id;
                            $grandDaughtersWifeInfo->relation_id=14;


                            if($grandDaughtersWifeInfo->save()){
                                $this->connectPartner($grandDaughterInfo->id , $grandDaughterInfo->pid);

                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Daughter's Wife Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Daughter's Wife.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Daughter's Wife.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Wife.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Wife.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Daughter's Wife Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }    

    public function AddGrandDaughtersPartner($request)
    {
        // First get connected with 
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

           $grandDaughterInfo=FamilyTree::find($request->connect_with);

           if(!empty($grandDaughterInfo)){
                if(is_null($grandDaughterInfo->pid)){
                  
                  $newUser=$this->addAsNewUser($request,$grandDaughterInfo);
  

                  if($newUser!=false) {

                   
                    $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                    if($newFamilyTree!=false){
                        
                        $grandDaughterInfo->pid=$newFamilyTree->id;

                        if($grandDaughterInfo->save()){
                            
                            $grandDaughtersPartnerInfo=FamilyTree::find($newFamilyTree->id);

                            $grandDaughtersPartnerInfo->pid=$grandDaughterInfo->id;
                            $grandDaughtersPartnerInfo->relation_id=27;


                            if($grandDaughtersPartnerInfo->save()){
                                $this->connectPartner($grandDaughterInfo->id , $grandDaughterInfo->pid);
                                return [
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>"Grand Daughter's Partner Added Successfully.",
                                ];
                            }
                            else{
                                return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>"Failed To Add Grand Daughter's Partner.",
                                ];
                            }
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Add Grand Daughter's Partner.",
                            ];
                        }
                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Partner.",
                        ];
                    }
                  }
                  else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Failed To Add Grand Daughter's Partner.",
                        ];
                  }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>"Grand Daughter's Partner Already Exists.",
                    ];
                }
           }
           else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Add Your Grand Daughter First.',
                ];
           }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddBrotherInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->fid) || !is_null($partnerInfo->mid)){

                        $newUser=$this->addAsNewUser($request,$partnerInfo);

                        if ($newUser!=false) {
                            
                            $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                            if ($newFamilyTree!=false) {

                                $brotherInLawInfo=FamilyTree::find($newFamilyTree->id);

                                if(!is_null($partnerInfo->fid))
                                    $brotherInLawInfo->fid=$partnerInfo->fid;

                                if(!is_null($partnerInfo->mid))
                                    $brotherInLawInfo->mid=$partnerInfo->mid;

                                if($brotherInLawInfo->save()){
                                    $this->connectPartner($brotherInLawInfo->id , $brotherInLawInfo->pid);
                                     return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Brother In Law info Added Successfully.',
                                    ];
                                }
                                else{
                                     return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Brother In Law',
                                    ];
                                }
                            }
                            else{
                                 return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Brother In Law',
                                ];
                            }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your Brother In Law',
                            ];
                        }


                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Please Add Your Partner Parent's First",
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddSisterInLaw($request)
    {
        $currentUser=auth()->user();

        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            if(!is_null($myFamilyTree->pid)){
                
                $partnerInfo=FamilyTree::find($myFamilyTree->pid);

                if(!empty($partnerInfo)) {

                    if(!is_null($partnerInfo->fid) || !is_null($partnerInfo->mid)){

                        $newUser=$this->addAsNewUser($request,$partnerInfo);

                        if ($newUser!=false) {
                            
                            $newFamilyTree=$this->addAsNewFamilyTree($request,$newUser);

                            if ($newFamilyTree!=false) {

                                $brotherInLawInfo=FamilyTree::find($newFamilyTree->id);

                                if(!is_null($partnerInfo->fid))
                                    $brotherInLawInfo->fid=$partnerInfo->fid;

                                if(!is_null($partnerInfo->mid))
                                    $brotherInLawInfo->mid=$partnerInfo->mid;

                                if($brotherInLawInfo->save()){
                                    $this->connectPartner($brotherInLawInfo->id, $brotherInLawInfo->pid);
                                     return [
                                        'errMsgFlag'=>false,
                                        'msgFlag'=>true,
                                        'msg'=>'Sister In Law info Added Successfully.',
                                    ];
                                }
                                else{
                                     return [
                                        'errMsgFlag'=>true,
                                        'msgFlag'=>false,
                                        'msg'=>'Failed To Add Sister In Law',
                                    ];
                                }
                            }
                            else{
                                 return [
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>'Failed To Add Sister In Law',
                                ];
                            }

                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>'You have already added your Sister In Law',
                            ];
                        }


                    }
                    else{
                        return [
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>"Please Add Your Partner Parent's First",
                        ];
                    }
                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'Please Add  Your Partner First.',
                    ];
                }
            }
            else{
                return [
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>'Please Add  Your Partner First.',
                ];
            }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function AddFriend($request)
    {
        $currentUser=auth()->user();
        $myFamilyTree=FamilyTree::where('user_id',$currentUser->id)->first();

        if(!empty($myFamilyTree)) {

            $newUser=$this->addAsNewUser($request,$myFamilyTree);

                if ($newUser!=false) {   

                    $friend_in_myFamilyTree=FamilyTree::where('user_id',$newUser->id)->first();
                    
                    if ($friend_in_myFamilyTree == null) {
                       $friendInfo=$this->addAsNewFamilyTree($request,$newUser);
                    }
                     $registered_friend_in_myFamilyTree=FamilyTree::where('user_id',$newUser->id)->first();


                     $all_friends = explode(",",$myFamilyTree->frnid);    
                     if($all_friends[0] == ""){
                        $all_friends = array($registered_friend_in_myFamilyTree->id);
                     }
                     else{
                        array_push($all_friends,$registered_friend_in_myFamilyTree->id);
                     }                     
                    $user_friends = implode(",",$all_friends);

                    $friends_all_friends = explode(",",$registered_friend_in_myFamilyTree->frnid); 
                    if($friends_all_friends[0] == ""){
                        $friends_all_friends = array($myFamilyTree->id);
                     }
                     else{
                        array_push($friends_all_friends,$myFamilyTree->id);
                     }
                     $friends_of_friend = implode(",",$friends_all_friends);

                    $myFamilyTree->frnid =  $user_friends ;
                    $registered_friend_in_myFamilyTree->frnid = $friends_of_friend;

                        if($myFamilyTree->save() && $registered_friend_in_myFamilyTree->save()){
                             return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>'Friend info Added Successfully.',
                            ];
                        }

                }
                else{
                    return [
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>'You have already added your Friend info.',
                    ];
                }
        }
        else{
            return [
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>'Add Yourself in Family Tree  First.',
            ];
        }
    }

    public function connectPartner($husbandId,$wifeId)
    {
        $husbandInfo=FamilyTree::find($husbandId);
        
        $wifeInfo=FamilyTree::find($wifeId);

        $husbandInfo->pid=$wifeInfo->id;
        $wifeInfo->pid=$husbandInfo->id;

        //RELATION ID SETTING FOR HUSBAND , WIFE AND Partner
        if($wifeInfo->relation_id != NULL)
        {
            if($wifeInfo->relation_id == 27){
                $wifeInfo->relation_id = 27; //partner
                $husbandInfo->relation_id = 27; //partner
            }
            else if($husbandInfo->gender === 'female' && $wifeInfo->relation_id == 14){
                $wifeInfo->relation_id = 14; //wife
                $husbandInfo->relation_id = 14; //wife
            }
            else if($husbandInfo->gender === 'male' && $wifeInfo->relation_id == 26)
            {
                $wifeInfo->relation_id = 26; //Husband
                $husbandInfo->relation_id = 26; //Husband
            }
            else if($husbandInfo->gender === 'female' && $wifeInfo->relation_id == 26){
                $wifeInfo->relation_id = 26; //Husband
                $husbandInfo->relation_id = 14; //wife
            }
            else{
                if($wifeInfo->gender == 'female' && $husbandInfo->gender == 'male'){
                    $wifeInfo->relation_id = 14; //partner
                    $husbandInfo->relation_id = 26; //partner
                }
                else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'male'){
                    $wifeInfo->relation_id = 26; //partner
                    $husbandInfo->relation_id = 14; //partner
                }
                else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'female'){
                    $wifeInfo->relation_id = 14; //partner
                    $husbandInfo->relation_id = 14; //partner
                }
                else if($husbandInfo->gender == 'male' && $wifeInfo->gender == 'male'){
                    $wifeInfo->relation_id = 26; //partner
                    $husbandInfo->relation_id = 26; //partner
                }
            }
        }
        else if($husbandInfo->relation_id != NULL)
        {
            if($husbandInfo->relation_id == 27){
                $wifeInfo->relation_id = 27; //partner
                $husbandInfo->relation_id = 27; //partner
            }
            else if($wifeInfo->gender === 'female' && $husbandInfo->relation_id == 14){
                $wifeInfo->relation_id = 14; //wife
                $husbandInfo->relation_id = 14; //wife
            }
            else if($wifeInfo->gender === 'male' && $husbandInfo->relation_id == 26)
            {
                $wifeInfo->relation_id = 26; //Husband
                $husbandInfo->relation_id = 26; //Husband
            }
            else if($wifeInfo->gender === 'female' && $husbandInfo->relation_id == 26){
                $wifeInfo->relation_id = 14; //Husband
                $husbandInfo->relation_id = 26; //wife
            }
            else{
                if($wifeInfo->gender == 'female' && $husbandInfo->gender == 'male'){
                    $wifeInfo->relation_id = 14; //partner
                    $husbandInfo->relation_id = 26; //partner
                }
                else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'male'){
                    $wifeInfo->relation_id = 26; //partner
                    $husbandInfo->relation_id = 14; //partner
                }
                else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'female'){
                    $wifeInfo->relation_id = 14; //partner
                    $husbandInfo->relation_id = 14; //partner
                }
                else if($husbandInfo->gender == 'male' && $wifeInfo->gender == 'male'){
                    $wifeInfo->relation_id = 26; //partner
                    $husbandInfo->relation_id = 26; //partner
                }
            }
        }
        else{
            if($wifeInfo->gender == 'female' && $husbandInfo->gender == 'male'){
                $wifeInfo->relation_id = 14; //partner
                $husbandInfo->relation_id = 26; //partner
            }
            else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'male'){
                $wifeInfo->relation_id = 26; //partner
                $husbandInfo->relation_id = 14; //partner
            }
            else if($husbandInfo->gender == 'female' && $wifeInfo->gender == 'female'){
                $wifeInfo->relation_id = 14; //partner
                $husbandInfo->relation_id = 14; //partner
            }
            else if($husbandInfo->gender == 'male' && $wifeInfo->gender == 'male'){
                $wifeInfo->relation_id = 26; //partner
                $husbandInfo->relation_id = 26; //partner
            }
        }

        if($husbandInfo->save() && $wifeInfo->save())
            return true;
        else
            return false;
    }

    public function deleteMember($id){

       
        $user = FamilyTree::where('user_id',Auth::user()->id)->first();
      
        $paternal_grand_father_mother = FamilyTree::leftjoin('family_trees as grand_father', 'grand_father.id', '=', 'family_trees.fid')
            ->leftjoin('family_trees as grand_mother', 'grand_mother.id', '=', 'family_trees.mid')
            ->where('family_trees.id',$user->fid)
            ->first([
                'grand_father.email as grand_father',
                'grand_father.id as grand_father_id',
                'grand_mother.email as grand_mother',
                'grand_mother.id as grand_mother_id',
            ]);
            $maternal_grand_father_mother = FamilyTree::leftjoin('family_trees as grand_father', 'grand_father.id', '=', 'family_trees.fid')
            ->leftjoin('family_trees as grand_mother', 'grand_mother.id', '=', 'family_trees.mid')
            ->where('family_trees.id',$user->mid)
            ->first([
                'grand_father.email as grand_father',
                'grand_father.id as grand_father_id',
                'grand_mother.email as grand_mother',
                'grand_mother.id as grand_mother_id',
            ]);
            

        
        // $msg = 'Failed to delete data you can delete you patner, father and mother now.';
        $msg = 'Failed to Process Your Request';

        if($user->fid == $id){
            $user->fid = null;
            $user->mid = null;
            $user->save();
            $msg = "Delete your father node is successful";
        }

        if($user->mid == $id){
            $user->mid = null;
            $user->save();
            $msg = "Delete your mother node is successful";
        }
        

        if($user->pid == $id){
            $user->pid = null;
            if ($user->gender == 'male') {
                $user_children = FamilyTree::where('fid',$user->id)->get();
                if (count($user_children) > 0) {
                    foreach ($user_children as $user_child) {
                            $user_child->fid = null;
                            $user_child->mid = null;
                            $user_child->save();
                    }
                }
            }

            if ($user->gender == 'female') {
                $user_children = FamilyTree::where('mid',$user->id)->get();
                if (count($user_children) > 0) {
                    foreach ($user_children as $user_child) {
                            $user_child->fid = null;
                            $user_child->mid = null;
                            $user_child->save();
                    }
                }
            }
            $user->save();
            $msg = "Delete your patner node is successful";
        }


        if ($paternal_grand_father_mother != null) {

            //paternal grand father delete
            if($paternal_grand_father_mother->grand_father_id == $id){
                $user_father = FamilyTree::where('id',$user->fid)->first();
                $user_father->fid = null;
                $user_father->mid = null;
                $user_father->save();
                $msg = "Delete your paternal grand father node is successful";
            }

            //paternal grand mother delete
            if($paternal_grand_father_mother->grand_mother_id == $id){
                $user_father = FamilyTree::where('id',$user->fid)->first();
                $user_father->mid = null;
                $user_father->save();
                $msg = "Delete your paternal grand mother node is successful";

            }
        } 

        if ($maternal_grand_father_mother != null) {

            //maternal grand father delete
            if($maternal_grand_father_mother->grand_father_id == $id){
                $user_mother = FamilyTree::where('id',$user->mid)->first();
                $user_mother->fid = null;
                $user_mother->save();
                $msg = "Delete your maternal grand father node is successful";
            }

            //maternal grand mother delete
            if($maternal_grand_father_mother->grand_mother_id == $id){
                $user_mother = FamilyTree::where('id',$user->mid)->first();
                $user_mother->mid = null;
                $user_mother->save();
                $msg = "Delete your maternal grand mother node is successful";
            }
        }


        // father in law &&  mother in law
        if(!is_null($user->pid)){
           
            $user_patner = FamilyTree::where('id',$user->pid)->first();
            //father in law
            if($user_patner->fid == $id){
                $user_patner->fid = null;
                $user_patner->mid = null;
                $msg = "Delete your father in law node is successful";
                $user_patner->save();
            }
            //mother in law
            if($user_patner->mid == $id){
                $user_patner->mid = null;
                $msg = "Delete your mother in law node is successful";
                $user_patner->save();
            }
        }

        // brother in law &&  sister in law
        if(!is_null($user->pid)){
            $user_patner = FamilyTree::where('id',$user->pid)->first();
            if($user_patner->fid != null){
                $partner_siblings = FamilyTree::where('fid', $user_patner->fid)->get();
                foreach ($partner_siblings as $partner_sibling) {
                   if ($partner_sibling->id == $id) {
                    $partner_sibling->fid = null ;
                    $partner_sibling->mid = null ;
                    $partner_sibling->save();
                       if ($partner_sibling->gender == 'male') {
                        $msg = "Delete your Brother in law node is successful";
                       }
                       if ($partner_sibling->gender == 'female') {
                        $msg = "Delete your Sister in law node is successful";
                       }
                   }
                }
            }           
        }



        //brother sister delete
        if((!is_null($user->fid) || !is_null($user->mid)) && Auth::user()->id!=$id){
            $user_sibling = FamilyTree::where('id',$id)->first();
          if ($user_sibling->fid != null ) {
                if($user_sibling->fid==$user->fid || $user_sibling->mid==$user->mid){
                    $user_sibling->fid = null;
                    $user_sibling->mid = null;
                    $user_sibling->save();
                    if($user_sibling->gender=='male'){
                        $msg = "Delete your brother node is successful";
                    }
                    if($user_sibling->gender=='female'){
                        $msg = "Delete your sister node is successful";
                    }
                }
          }
            
        }



        // son daughter delete
        $user_child = FamilyTree::where('id',$id)->first();

        if($user->id==$user_child->fid || $user->id==$user_child->mid){
            $user_child->fid = null;
            $user_child->mid = null;
            $user_child->save();
            if($user_child->gender=='male'){
                $msg = "Delete your son node is successful";
            }
            if($user_child->gender=='female'){
                $msg = "Delete your daughter node is successful";
            }
        }   



        // Child Partner Delete
        $user_child_partner = FamilyTree::where('id',$id)->first();

        if($user_child_partner->pid != null){
            $user_child = FamilyTree::where('id',$user_child_partner->pid)->first();
            if($user->id==$user_child->fid || $user->id==$user_child->mid){

                if ($user_child->gender == 'male') {
                    $grandChildren = FamilyTree::where('fid',$user_child->id)->get();
                    foreach ($grandChildren as $grandChild) {
                        $grandChild->fid = null;
                        $grandChild->mid = null;
                        $grandChild->save();
                    }
                }
                if ($user_child->gender == 'female') {
                    $grandChildren = FamilyTree::where('mid',$user_child->id)->get();
                    foreach ($grandChildren as $grandChild) {
                        $grandChild->fid = null;
                        $grandChild->mid = null;
                        $grandChild->save();
                    }
                }
                $user_child->pid = null;
                $user_child->save();
                if($user_child->gender =='male'){
                    $msg = "Delete your son's Partner node is successful";
                }
                if($user_child->gender =='female'){
                    $msg = "Delete your daughter's Partner node is successful";
                }
            }

        }


        // Grand Child Delete

        $user_Grandchild = FamilyTree::where('id',$id)->first();

        if ($user->gender == 'male') {
            $user_children = FamilyTree::where('fid',$user->id)->get();
             foreach ($user_children as $user_child) {               
                if ($user_child->gender == 'male') {
                    if ($user_Grandchild->fid == $user_child->id) {
                        $user_Grandchild->fid = null;
                        $user_Grandchild->mid = null;
                        $user_Grandchild->save();
                        if($user_Grandchild->gender=='male'){
                            $msg = "Delete your Grand son node is successful";
                        }
                        if($user_Grandchild->gender=='female'){
                            $msg = "Delete your Grand daughter node is successful";
                        }

                    }
                }
                if ($user_child->gender == 'female') {
                    if ($user_Grandchild->mid == $user_child->id) {
                        $user_Grandchild->fid = null;
                        $user_Grandchild->mid = null;
                        $user_Grandchild->save();
                        if($user_Grandchild->gender=='male'){
                            $msg = "Delete your Grand son node is successful";
                        }
                        if($user_Grandchild->gender=='female'){
                            $msg = "Delete your Grand daughter node is successful";
                        }
                    }
                }
            }
        }
        if ($user->gender == 'female') {
            $user_child = FamilyTree::where('mid',$user->id)->get();
            foreach ($user_children as $user_child) {
                if ($user_child->gender == 'male') {
                    if ($user_Grandchild->fid == $user_child->id) {
                        $user_Grandchild->fid = null;
                        $user_Grandchild->mid = null;
                        $user_Grandchild->save();
                        if($user_Grandchild->gender=='male'){
                            $msg = "Delete your Grand son node is successful";
                        }
                        if($user_Grandchild->gender=='female'){
                            $msg = "Delete your Grand daughter node is successful";
                        }
                    }
                }
                if ($user_child->gender == 'female') {
                    if ($user_Grandchild->mid == $user_child->id) {
                        $user_Grandchild->fid = null;
                        $user_Grandchild->mid = null;
                        $user_Grandchild->save();
                        if($user_Grandchild->gender=='male'){
                            $msg = "Delete your Grand son node is successful";
                        }
                        if($user_Grandchild->gender=='female'){
                            $msg = "Delete your Grand daughter node is successful";
                        }
                    }
                }
            }
        }


        $grand_child_partner = FamilyTree::where('id',$id)->first();




        if ($user->gender == 'male') {
            $user_children = FamilyTree::where('fid',$user->id)->get();
            foreach ($user_children as $user_child) {
                if ($user_child->pid != null) {
                    if ($user_child->gender == 'male') {
                       $user_Grandchildren = FamilyTree::where('fid',$user_child->id)->get();
                       foreach ($user_Grandchildren as $user_Grandchild) {
                            if ($user_Grandchild->pid != null) {
                                if ($user_Grandchild->pid == $id) {
                                    $user_Grandchild->pid = null;
                                    $user_Grandchild->save();

                                    if ($user_Grandchild->gender == 'male') {
                                    $msg = "Delete your Grand Sons's Parter node is successful";
                                
                                    }
                                    if ($user_Grandchild->gender == 'female') {
                                        $msg = "Delete your Grand daughter's Parter node is successful";                                    
                                    }
                                }

                                
                            }
                       }
                    }
                    if ($user_child->gender == 'female') {
                        $user_Grandchildren = FamilyTree::where('mid',$user_child->id)->get();
                        foreach ($user_Grandchildren as $user_Grandchild) {
                            if ($user_Grandchild->pid != null) {
                                if ($user_Grandchild->pid == $id) {
                                    $user_Grandchild->pid = null;
                                    $user_Grandchild->save();

                                    if ($user_Grandchild->gender == 'male') {
                                    $msg = "Delete your Grand Sons's Parter node is successful";
                                
                                    }
                                    if ($user_Grandchild->gender == 'female') {
                                        $msg = "Delete your Grand daughter's Parter node is successful";                                    
                                    }
                                }                               
                            }
                       }
                     }
                }
            }
        }
        if ($user->gender == 'female') {
            $user_children = FamilyTree::where('mid',$user->id)->get();
            foreach ($user_children as $user_child) {
                if ($user_child->pid != null) {
                    if ($user_child->gender == 'male') {
                       $user_Grandchildren = FamilyTree::where('fid',$user_child->id)->get();
                       foreach ($user_Grandchildren as $user_Grandchild) {
                            if ($user_Grandchild->pid != null) {
                                if ($user_Grandchild->pid == $id) {
                                    $user_Grandchild->pid = null;
                                    $user_Grandchild->save();

                                    if ($user_Grandchild->gender == 'male') {
                                    $msg = "Delete your Grand Sons's Parter node is successful";
                                
                                    }
                                    if ($user_Grandchild->gender == 'female') {
                                        $msg = "Delete your Grand daughter's Parter node is successful";                                    
                                    }
                                }

                                
                            }
                       }
                    }
                    if ($user_child->gender == 'female') {
                        $user_Grandchildren = FamilyTree::where('mid',$user_child->id)->get();
                        foreach ($user_Grandchildren as $user_Grandchild) {
                            if ($user_Grandchild->pid != null) {
                                if ($user_Grandchild->pid == $id) {
                                    $user_Grandchild->pid = null;
                                    $user_Grandchild->save();

                                    if ($user_Grandchild->gender == 'male') {
                                    $msg = "Delete your Grand Sons's Parter node is successful";
                                
                                    }
                                    if ($user_Grandchild->gender == 'female') {
                                        $msg = "Delete your Grand daughter's Parter node is successful";                                    
                                    }
                                }                               
                            }
                       }
                     }
                }
            }
        }


        $user_friend = FamilyTree::where('user_id',$id)->first();
     
        if ($user->frnid != null) {           
            $user_all_friends = (explode(",",$user->frnid));
            foreach ($user_all_friends as $key => $friend) {
               if ($friend == $user_friend->id) {
                unset($user_all_friends[$key]); 
               }
            }
            $user_updated_friends = (implode(",",$user_all_friends));
            $user->frnid = $user_updated_friends;
            $user->save();
            $msg = "Delete your Friend node is successful";
        }

        

        
            return (['success'=>true,'msg'=>$msg]);
    }

    public function update_member(Request $request){
        $currentUser=FamilyTree::where('user_id',auth()->user()->id)->first();
        $member_id = $request->user_id;
        $user = User::where('id',$member_id)->first();
     
        if ($user) {         
            $member = FamilyTree::where('user_id',$member_id)->first();
            $relationID = $this->getUsersRelation( $currentUser->id, $member->id);
            if ($member) {
              
                return response()->json([
                                         'status'=>'success',
                                         'first_name'=> $member->first_name,
                                         'last_name'=> $member->last_name,
                                         'email'=> $member->email,
                                         'relation'=> $relationID,
                                         'gender'=> $member->gender,
                                         'dob'=> $user->dob,
                                         'living'=> $member->living,
                                        ]);      
            }
        }
    }

    public function update_member_record(Request $request){

        try{
            if($request->upd_living == 1){
                $rules = [
                        'upd_first_name' => 'required|string|max:255',
                        'upd_last_name' => 'required|string|max:255',
                        'upd_relation_email'     => 'required|unique:users,email,'.$request->upd_user_id,
                        'upd_living' => 'required'                    
                    ];
            }
            else{
                $rules = [
                        'upd_first_name' => 'required|string|max:255',
                        'upd_last_name' => 'required|string|max:255',                    
                        'upd_living' => 'required'                   
                ];
            }
                $messages = [
                    'upd_first_name.required'=>'First Name is Required',
                    'upd_first_name.string'=>'First Name is Invalid',
                    'upd_first_name.max'=>'First Name Must be of Minimum 255 Cahracters',
                    'upd_last_name.required'=>'Last Name is Required',
                    'upd_last_name.string'=>'Last Name is Invalid',
                    'upd_last_name.max'=>'Last Name Must be of Minimum 255 Cahracters',
                    'upd_relation_email.required' => 'Email Address is Required',
                    'upd_relation_email.unique' => 'This Email Address is Already Registered',
                    'upd_living.required'=>'This Field is Required'
                ];
               $this->validate($request, $rules, $messages);

               $to_be_upd_user = User::where('id',$request->upd_user_id)->first();
               $to_be_upd_user->first_name = $request->upd_first_name;
               $to_be_upd_user->last_name = $request->upd_last_name;
               $to_be_upd_user->email = $request->upd_relation_email;
               $to_be_upd_user->dob = date_format(date_create( $request->get('upd_relation_dob')),'Y-m-d');

               if($to_be_upd_user->save()){
                        $member_to_be_upd = FamilyTree::where('user_id',$request->upd_user_id)->first();
                        $member_to_be_upd->first_name = $request->upd_first_name;
                        $member_to_be_upd->last_name = $request->upd_last_name;
                        $member_to_be_upd->email = $request->upd_relation_email;
                        $member_to_be_upd->living = $request->upd_living;

                        if($member_to_be_upd->save()){
                            return [
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>"Member Updated Successfully",
                            ];
                        }
                        else{
                            return [
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>"Failed To Update Member.",
                            ];
                        }
               }

               
        }

        catch(Exception $err){
         DB::rollBack();

         return response()->json(
                [
                    'status'       => 'error3',
                ]
            );
        }
    }

    public function getUsersRelation($user1 , $user2){
        $user1Info = FamilyTree::find($user1);

        if($user1Info->fid === $user2){ return 9; } //father
        elseif($user1Info->mid === $user2){ return 11; } //mother
        elseif($user1Info->pid === $user2){ 
            if($user1Info->relation_id == 14){ return 14; } //wife
            if($user1Info->relation_id == 26){ return 26; } //Husband
            if($user1Info->relation_id == 27){ return 27; } //Partner
        } 
        elseif($user1Info->fid === $user2){ return 23; } //friend
        else{
            //check on parental and maternal grandparents
            $fatherInfo = FamilyTree::find($user1Info->fid);
            $motherInfo = FamilyTree::find($user1Info->mid);
            $partnerParents = FamilyTree::find($user1Info->pid);
            $partnerFatherInfo = FamilyTree::find($partnerParents->fid);
            $partnerMotherInfo = FamilyTree::find($partnerParents->mid);
            if($fatherInfo !== NULL && $fatherInfo->fid === $user2){ return 1; } //Grand father
            elseif($fatherInfo !== NULL && $fatherInfo->mid === $user2){ return 2; } //Grand mother
            else if($motherInfo !== NULL && $motherInfo->fid === $user2){ return 3; } //Maternal Grand father
            elseif($motherInfo !== NULL && $motherInfo->mid === $user2){ return 4; } //Maternal Grand mother

            else if($partnerParents !== NULL && $partnerParents->fid === $user2){ return 10; } //Father In Law
            elseif($partnerParents !== NULL && $partnerParents->mid === $user2){ return 12; } //Mother In Law
            else if($partnerFatherInfo !== NULL && $partnerFatherInfo->fid === $user2){ return 5; } //Parental Grand Father In Law
            elseif($partnerFatherInfo !== NULL && $partnerFatherInfo->mid === $user2){ return 6; } //Parental Grand Mother In Law
            else if($partnerMotherInfo !== NULL && $partnerMotherInfo->fid === $user2){ return 7; } //Matheral Grand Father In Law
            elseif($partnerMotherInfo !== NULL && $partnerMotherInfo->mid === $user2){ return 8; } //Maternal Grand Mother In Law
            else{
                //check for siblings
                $user2Info = FamilyTree::find($user2);
                if(($user1Info->fid == $user2Info->fid) || ($user1Info->mid == $user2Info->fid) || ($user1Info->fid == $user2Info->mid) || ($user1Info->mid == $user2Info->mid)){
                    if($user2Info->gender == 'male'){ return 15; } //Brother
                    if($user2Info->gender == 'female'){ return 17; } //Sister
                }
                if($user1Info->fid !== NULL){
                    //check with sibling Partner
                    $relation=0;
                    $childrens = $this->getChildrens($user1Info->fid);
                    foreach($childrens as $key=>$childInfo){                   
                        if($childInfo->pid == $user2 && $childInfo->gender == 'male'){
                            if($childInfo->relationId == 14){ $relation= 18; break; } //Sister In Law
                            if($childInfo->relationId == 26){ $relation= 16; break; } //Brother in Law
                            if($childInfo->relationId == 27){ $relation= 18; break; } //Sister in Law
                        }
                        if($childInfo->pid == $user2 && $childInfo->gender == 'female'){
                            if($childInfo->relationId == 14){ $relation= 18; break; } //Sister's in Law
                            if($childInfo->relationId == 26){ $relation= 16; break; } //Brother in Law
                            if($childInfo->relationId == 27){ $relation= 16; break; } //Brother in Law
                        }
                    }
                    if($relation !== 0) {return $relation;}
                }
                if($user1Info->mid !== NULL){
                    //check with sibling Partner
                    $relation=0;
                    $childrens = $this->getChildrens($user1Info->mid);
                    foreach($childrens as $key=>$childInfo){                      
                        if($childInfo->pid == $user2 && $childInfo->gender == 'male'){
                            if($childInfo->relationId == 14){ $relation= 18; break; } //Sister In Law
                            if($childInfo->relationId == 26){ $relation= 16; break; } //Brother in Law
                            if($childInfo->relationId == 27){ $relation= 18; break; } //Sister in Law
                        }
                        if($childInfo->pid == $user2 && $childInfo->gender == 'female'){
                            if($childInfo->relationId == 14){ $relation= 18; break; } //Sister's in Law
                            if($childInfo->relationId == 26){ $relation= 16; break; } //Brother in Law
                            if($childInfo->relationId == 27){ $relation= 16; break; } //Brother in Law
                        }
                    }
                    if($relation !== 0) {return $relation;}
                }
                
                    //check in child
                    $relation = '';
                    $childrens = $this->getChildrens($user1Info->id);
                    foreach($childrens as $key=>$childInfo){
                        if($childInfo->id == $user2 && $childInfo->gender === 'male'){ $relation = 19; break; } //Son
                        if($childInfo->id == $user2 && $childInfo->gender === 'female'){ $relation = 21; break; } //Daughter
                        //check Child partner
                        if($childInfo->pid == $user2){
                            if($childInfo->gender === 'male' && $childInfo->partnerInfo->relation_id == 14 ){$relation = 20; break;} //Son's wife
                            if($childInfo->gender === 'male' && $childInfo->partnerInfo->relation_id == 26 ){$relation = 28; break;} //Son's Husband
                            if($childInfo->gender === 'male' && $childInfo->partnerInfo->relation_id == 27 ){$relation = 29; break;} //Son's Partner
                            if($childInfo->gender === 'female' && $childInfo->partnerInfo->relation_id == 14 ){$relation = 30; break;} //Daughter's Wife
                            if($childInfo->gender === 'female' && $childInfo->partnerInfo->relation_id == 26 ){$relation = 22; break;} //Daughter's Husband
                            if($childInfo->gender === 'female' && $childInfo->partnerInfo->relation_id == 27 ){$relation = 31; break;} //Daughter's Partner
                        }
                        //check in grand son, grand daughters
                        // if($childInfo->gender == 'male'){
                            $childrens = $this->getChildrens($childInfo->id);
                            foreach($childrens as $key=>$grandChildInfo){
                            foreach($childInfo->childInfosForFather()->get() as $key=>$grandChildInfo){
                                if($grandChildInfo->id == $user2 && $grandChildInfo->gender === 'male'){ $relation = 24; break; } //Grand Son
                                if($grandChildInfo->id == $user2 && $grandChildInfo->gender === 'female'){ $relation = 25; break; } //Grand Daughter
                                //check Grand Child partner
                                if($grandChildInfo->pid == $user2){
                                    if($grandChildInfo->gender === 'male' && $grandChildInfo->partnerInfo->relation_id == 14 ){$relation = 32; break;} //Grand Son's wife
                                    if($grandChildInfo->gender === 'male' && $grandChildInfo->partnerInfo->relation_id == 26 ){$relation = 33; break;} //Grand Son's Husband
                                    if($grandChildInfo->gender === 'male' && $grandChildInfo->partnerInfo->relation_id == 27 ){$relation = 34; break;} //Grand Son's Partner
                                    if($grandChildInfo->gender === 'female' && $grandChildInfo->partnerInfo->relation_id == 14 ){$relation = 35; break;} //Grand Daughter's Wife
                                    if($grandChildInfo->gender === 'female' && $grandChildInfo->partnerInfo->relation_id == 26 ){$relation = 36; break;} //Grand Daughter's Husband
                                    if($grandChildInfo->gender === 'female' && $grandChildInfo->partnerInfo->relation_id == 27 ){$relation = 37; break;} //Grand Daughter's Partner
                                }
                            }
                            if($relation !== ''){ break ; }
                        }
                    }
                   
                    return $relation;
                
            }

        }
    }

    public function get_video(Request $request){

        if (Auth::check()) {
                $story = Story::where('user_id',$request->user_id)->get();
                $addon = AddonVideo::where('user_id',$request->user_id)->first();

                if(count($story) >= 1){
                  $stories = $story->toArray();
                  $latest_story = end($stories);
                    if($story){
                        if($addon){
                            return response()->json(['msg'=>'Success','data'=>$latest_story,'addon'=>$addon['video']]);   
                        }
                        else{
                            return response()->json(['msg'=>'Success','data'=>$latest_story]);   

                        }   
                    }
                    else{
                        return response()->json(['msg'=>'Error','data'=>'No Records Found']);
                    }
                }
                else{
                        return response()->json(['msg'=>'Error','data'=>'No Records Found']);
                }     
        }
        else {

            $story = Story::where('user_id',$request->user_id)->get();
            $addon = AddonVideo::where('user_id',$request->user_id)->first();
            $stories = $story->toArray();
            $latest_story = end($stories);
            if($story){
               if($addon){
                            return response()->json(['msg'=>'Success','data'=>$latest_story,'addon'=>$addon['video']]);   
                        }
                        else{
                            return response()->json(['msg'=>'Success','data'=>$latest_story]);   

                        }    
            }
            else{
                return response()->json(['msg'=>'Error','data'=>'No Records Found']);
            }
        }
    }

    public function getChildrens($id){
        return FamilyTree::where('fid', '=' , $id)->orWhere('mid', '=', $id)->get();
    }
    
}