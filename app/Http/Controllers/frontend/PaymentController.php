<?php

namespace App\Http\Controllers\frontend;

use App\Models\PaymentLog;
use App\Models\Promocode;
use App\Models\Faq;
use App\Models\User;
use App\Models\Story;
use App\Models\AddonVideo;
use Auth;
use Illuminate\Http\Request;
use App\Repositories\VideoParse;
use Illuminate\Support\Facades\Session;
use RealRashid\SweetAlert\Facades\Alert;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\constants\ANetEnvironment;
use App\Http\Controllers\frontend\BaseController;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\controller\CreateTransactionController;
use Ixudra\Curl\Facades\Curl;


class PaymentController extends BaseController
{
    public function pay()
    {
        $cart       = Session::get('cart');
        $storyItems = collect(Session::get('storyItems'));

        // Cheking is cart necessary data.
        if(!$cart) return redirect()->route('create-your-story.step-1');

        if (isset($cart['addon_video'])) {
            $addon_charges = 5;
        } else {
            $addon_charges = 0;
        }

        return view('frontend.payment',compact('addon_charges'));
    }


    public function pay_addon()
    {
        $cart       = Session::get('cart');
        $storyItems = collect(Session::get('storyItems'));

        // Cheking is cart necessary data.
        if(!$cart) return redirect()->route('create-your-story.step-1');

        // Cheking is all cart story was uploaded or redirect to upload.
 
        if (isset($cart['addon_video'])) {
            $addon_charges = 5;
        } else {
            $addon_charges = 0;
        }

        return view('frontend.payment-addon',compact('addon_charges'));
    }

    public function pay_terms_conditions()
    {
        $faqs = Faq::where('status', 1)->orderBy('sort', 'ASC')->get();
        return view('frontend.terms-conditions',compact('faqs'));
    }

    public function promocode_expire(){

        $promocodes = Promocode::where('status','active')->get();

        if($promocodes){
            foreach($promocodes as $promocode){
                if($promocode->expiry == date('Y-m-d') || date('Y-m-d') > $promocode->expiry){
                    $promocode->status = 'expired';
                    $promocode->save();    
                }
                
            }
            if ($promocode->save()) {
                return response()->json(['success'=>true,'message'=>'Promocode is Expired']);
            }
        }
        else{
            return response()->json(['success'=>true,'message'=>'No Promocode to be Expire']);
        }
    }

    public function promocode_apply(Request $request){

        $promocode = Promocode::where('code',strtoupper($request->promocode))->first();

        if ($promocode) {
            if ($promocode->status == 'active') {
                $discount = $promocode->discount;
                $discounted_price = round($request->price - $discount/100* $request->price,2) ;
                
                return response()->json(['success'=>true,'message'=>"Promocode is Redeemed",'discount'=>$discounted_price,'promocode_id'=>$promocode->id,'discount_percent'=>$discount]);                    
            } 
            elseif($promocode->status == 'expired') {
                return response()->json(['success'=>false,'message'=>"This Promocode is Expired"]);
            }
            else{
                return response()->json(['success'=>false,'message'=>"Invalid Promocode"]);
            }
        }   
        else {
                return response()->json(['success'=>false,'message'=>"Invalid Promocode"]);
            }
        
    }


    public function continue_same_plan(){
        $cart = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $storyItems = collect(Session::get('storyItems'));
        $paymentLogid = Story::where('user_id',Auth::user()->id)->where('package',$cart['plan'])->first();
        VideoParse::mergeChunkVideos($paymentLogid->payment_log_id,$cart, $storyItems);
        $message_text = 'New Story Created Successfully';
        Alert::success($message_text);    
        return redirect()->route('profile');
    }



    public function upgrade_plan(Request $request){
    
            

        $request->validate([
            'owner' => "required|string|min:3",
            'cardNumber' => "required|digits:16",
            'expiration' => "required|date_format:m/Y",
            'cvv' => "required|integer|digits_between:3,4",
            'accept_terms' => "required"],
            [
                'accept_terms.required' => "Please Accept Terms & Conditions"
        ]);

        $cart = Session::get('cart');
        if(!$cart) return redirect()->route('create-your-story.step-1');
        $storyItems = collect(Session::get('storyItems'));
        $expirations = explode('/', $request->expiration);
        if (isset($cart['addon_video'])) {
            $addon_charges = 5 ;
        }
        else{
                    $addon_charges = 0 ;
        }

        $input = $request->except('expiration', '_token') + ['expiration-month' => $expirations[0], 'expiration-year' => $expirations[1], 'amount' => $request->price];

            /* Create a merchantAuthenticationType object with authentication details
            retrieved from the constants file */
            $merchantAuthentication = new MerchantAuthenticationType();
            $merchantAuthentication->setName(env('MERCHANT_LOGIN_ID'));
            $merchantAuthentication->setTransactionKey(env('MERCHANT_TRANSACTION_KEY'));

            
            // Set the transaction's refId
            $refId = 'ref' . time();
            $cardNumber = preg_replace('/\s+/', '', $input['cardNumber']);
            // Create the payment data for a credit card
            $creditCard = new CreditCardType();
            $creditCard->setCardNumber($cardNumber);
            $creditCard->setExpirationDate($input['expiration-year'] . "-" . $input['expiration-month']);
            $creditCard->setCardCode($input['cvv']);
            

            // Add the payment data to a paymentType object
            $paymentOne = new PaymentType();
            $paymentOne->setCreditCard($creditCard);
            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($input['amount']);
            $transactionRequestType->setPayment($paymentOne);

            // Assemble the complete transaction request
            $requests = new CreateTransactionRequest();
            $requests->setMerchantAuthentication($merchantAuthentication);
            $requests->setRefId($refId);
            $requests->setTransactionRequest($transactionRequestType);

            // Create the controller and get the response
            $controller = new CreateTransactionController($requests);
            $response = $controller->executeWithApiResponse(ANetEnvironment::SANDBOX);

            if ($response != null) {
            

                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $message_text = $tresponse->getMessages()[0]->getDescription() . ", Transaction ID: " . $tresponse->getTransId();

                        $paymentLog = PaymentLog::create([
                            'amount' => $input['amount'],
                            'response_code' => $tresponse->getResponseCode(),
                            'transaction_id' => $tresponse->getTransId(),
                            'auth_id' => $tresponse->getAuthCode(),
                            'message_code' => $tresponse->getMessages()[0]->getCode(),
                            'name_on_card' => trim($input['owner']),
                            'quantity' => 1
                        ]);
                        
                        $current_user = User::where('id',Auth::user()->id)->first();
                        $current_user->plan_purchased = $cart['plan'];
                        if (isset($cart['addon_video'])) {
                            $current_user->addon = 1;
                        } 
                        $current_user->save();


                        VideoParse::mergeChunkVideos($paymentLog->id, $cart, $storyItems);

                        if (isset($cart['addon_video'])) {
                            $temp_addon = $cart['addon_video'];
                                $permanent_addon = VideoParse::saveAddon($temp_addon);

                                $addon_video = new AddonVideo;
                                $addon_video->user_id = Auth::user()->id;
                                $addon_video->video = $permanent_addon;
                            $addon_video->save();
                        }

                        Alert::success($message_text);
                        return redirect()->route('profile');
                    } else {
                        $message_text = 'There were some issue with the payment. Please try again later.';
                        $msg_type = "error_msg";

                        if ($tresponse->getErrors() != null) {
                            $message_text = $tresponse->getErrors()[0]->getErrorText();
                            $msg_type = "error_msg";
                        }
                    }
                    // Or, print errors if the API request wasn't successful
                } else {
                    $message_text = 'There were some issue with the payment. Please try again later.';
                    $msg_type = "error_msg";

                    $tresponse = $response->getTransactionResponse();
                    
                    if ($tresponse != null && $tresponse->getErrors() != null) {
                        $message_text = $tresponse->getErrors()[0]->getErrorText();
                        $msg_type = "error_msg";
                    } else {
                        $message_text = $response->getMessages()->getMessage()[0]->getText();
                        $msg_type = "error_msg";
                    }
                }
            } else {
                $message_text = "No response returned";
                $msg_type = "error_msg";
            }
        
    }

    public function handlePayment(Request $request)
    {

        $request->validate([
            'charity' => "required",
            'owner' => "required|string|min:3",
            'cardNumber' => "required|digits:16",
            'expiration' => "required|date_format:m/Y",
            'cvv' => "required|integer|digits_between:3,4",
            'accept_terms' => "required"],
            [
                'accept_terms.required' => "Please Accept Terms & Conditions"
        ]);

        $cart = Session::get('cart');

        $promo_id = $request->promo_id ;
        $charity = $request->charity ;
        if($promo_id != null){
            $promocode = Promocode::find($promo_id);
            $discount = $promocode->discount;
            $promocode_used = 'Used';
            $promocode_type = $promocode->type;
            $promocode_name = $promocode->code;
        }
        else{
            $discount = 0;
            $promocode_used = 'Not Used';
            $promocode_type = null;
            $promocode_name = null;
        }


        // end payment
        if(!$cart) return redirect()->route('create-your-story.step-1');

        $storyItems = collect(Session::get('storyItems'));

        // Cheking is all cart story was uploaded or redirect to upload.


        if (isset($cart['addon_video'])) {
            $addon_charges = 5;
        } else {
            $addon_charges = 0;
        }

        $expirations = explode('/', $request->expiration);

       if (isset($cart['addon_video']) && Auth::user()->plan_purchased != null) {
            $input = $request->except('expiration', '_token') + ['expiration-month' => $expirations[0], 'expiration-year' => $expirations[1], 'amount' => $addon_charges];

        } else {
            $input = $request->except('expiration', '_token') + ['expiration-month' => $expirations[0], 'expiration-year' => $expirations[1], 'amount' => config('plans.' . $cart['plan']. '.price')+$addon_charges];
        }

        $discounted_value = $discount / 100 * $input['amount'];

        $input['amount'] = round( $input['amount'] - $discounted_value,2);
        
     
        if ($input['amount'] == 0) {

            function generateRandomString($length) {
             $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
             $string = '';
             $maxIndex = strlen($characters) - 1;
                for ($i = 0; $i < $length; $i++) {
                    $randomIndex = mt_rand(0, $maxIndex);
                    $string .= $characters[$randomIndex];
                }
                return $string;
            }
            $randomString = generateRandomString(6);
            $TransId = rand(30432810723,90270631034);
            $message_text = "This transaction has been approved, Transaction ID: " .$TransId;
                
                        $paymentLog = PaymentLog::create([
                            'amount' => $input['amount'],
                            'promocode_used' => $promocode_used,
                            'promocode_type' => $promocode_type,
                            'promocode' => $promocode_name,
                            'discount' => $discount,
                            'charity' => $charity,
                            'response_code' => 1,
                            'transaction_id' => $TransId,
                            'auth_id' => $randomString,
                            'message_code' => 1,
                            'name_on_card' => trim($input['owner']),
                            'quantity' => 1
                        ]);

                    $current_user = User::where('id',Auth::user()->id)->first();
                    $current_user->plan_purchased = $cart['plan'];
                    if (isset($cart['addon_video'])) {
                        $current_user->addon = 1;
                    } 
                    $current_user->save();

                    VideoParse::mergeChunkVideos($paymentLog->id, $cart, $storyItems);

                    if (isset($cart['addon_video'])) {

                        $temp_addon = $cart['addon_video'];
                        $permanent_addon = VideoParse::saveAddon($temp_addon);

                        $addon_video = new AddonVideo;
                        $addon_video->user_id = Auth::user()->id;
                        $addon_video->video = $permanent_addon;
                        $addon_video->save();
                    }


                    Alert::success($message_text);                        
                    return redirect()->route('profile');
        }
        else{
            
        
            /* Create a merchantAuthenticationType object with authentication details
              retrieved from the constants file */
            $merchantAuthentication = new MerchantAuthenticationType();
            $merchantAuthentication->setName(env('MERCHANT_LOGIN_ID'));
            $merchantAuthentication->setTransactionKey(env('MERCHANT_TRANSACTION_KEY'));

            
            // Set the transaction's refId
            $refId = 'ref' . time();
            $cardNumber = preg_replace('/\s+/', '', $input['cardNumber']);
            // Create the payment data for a credit card
            $creditCard = new CreditCardType();
            $creditCard->setCardNumber($cardNumber);
            $creditCard->setExpirationDate($input['expiration-year'] . "-" . $input['expiration-month']);
            $creditCard->setCardCode($input['cvv']);
            

            // Add the payment data to a paymentType object
            $paymentOne = new PaymentType();
            $paymentOne->setCreditCard($creditCard);
            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($input['amount']);
            $transactionRequestType->setPayment($paymentOne);


         

            // Assemble the complete transaction request
            $requests = new CreateTransactionRequest();
            $requests->setMerchantAuthentication($merchantAuthentication);
            $requests->setRefId($refId);
            $requests->setTransactionRequest($transactionRequestType);

                   

            // Create the controller and get the response
            $controller = new CreateTransactionController($requests);
            $response = $controller->executeWithApiResponse(ANetEnvironment::SANDBOX);

            if ($response != null) {
            

                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $message_text = $tresponse->getMessages()[0]->getDescription() . ", Transaction ID: " . $tresponse->getTransId();

                        $paymentLog = PaymentLog::create([
                            'amount' => $input['amount'],
                            'response_code' => $tresponse->getResponseCode(),
                            'transaction_id' => $tresponse->getTransId(),
                            'promocode_used' => $promocode_used,
                            'promocode' => $promocode_name,
                            'charity' => $charity,
                            'promocode_type' => $promocode_type,
                            'discount' => $discount,
                            'auth_id' => $tresponse->getAuthCode(),
                            'message_code' => $tresponse->getMessages()[0]->getCode(),
                            'name_on_card' => trim($input['owner']),
                            'quantity' => 1
                        ]);

                    $current_user = User::where('id',Auth::user()->id)->first();
                    $current_user->plan_purchased = $cart['plan'];
                    if (isset($cart['addon_video'])) {
                        $current_user->addon = 1;
                    } 
                    $current_user->save();


                       VideoParse::mergeChunkVideos($paymentLog->id, $cart, $storyItems);

                        if (isset($cart['addon_video'])) {
                            $temp_addon = $cart['addon_video'];
                            $permanent_addon = VideoParse::saveAddon($temp_addon);

                            $addon_video = new AddonVideo;
                            $addon_video->user_id = Auth::user()->id;
                            $addon_video->video = $permanent_addon;
                            $addon_video->save();
                        }

                        Alert::success($message_text);

                        return redirect()->route('profile');
                    } else {
                        $message_text = 'There were some issue with the payment. Please try again later.';
                        $msg_type = "error_msg";

                        if ($tresponse->getErrors() != null) {
                            $message_text = $tresponse->getErrors()[0]->getErrorText();
                            $msg_type = "error_msg";
                        }
                    }
                    // Or, print errors if the API request wasn't successful
                } else {
                    $message_text = 'There were some issue with the payment. Please try again later.';
                    $msg_type = "error_msg";

                    $tresponse = $response->getTransactionResponse();
                    
                    if ($tresponse != null && $tresponse->getErrors() != null) {
                        $message_text = $tresponse->getErrors()[0]->getErrorText();
                        $msg_type = "error_msg";
                    } else {
                        $message_text = $response->getMessages()->getMessage()[0]->getText();
                        $msg_type = "error_msg";
                    }
                }
            } else {
                $message_text = "No response returned";
                $msg_type = "error_msg";
            }
        }
        return back()->with($msg_type, $message_text);
    }
}
