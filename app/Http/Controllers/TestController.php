<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\multiCurrency;
use Session;
use App\AutoDetectGeo;
use App\CurrencyList;
use DataTables;
use DB;
use App\Allcountry;
use App\Country;
use App\Allcity;
use App\Allstate;
use App\Product;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Stripe\Error\Card;
use PaytmWallet;

class TestController extends Controller
{
    public $api_user = "sb-tlvyu24541_api1.business.example.com";
    public $api_pass = "NXWTEVM4MFSY4QTY";
    public $api_sig = "AfjCiReQHjxgQ75D20ymyCratYHDA4HmJVm1.isRpy9HWPKzAIOdWMor";
    public $app_id = "APP-80W284485P519543T";
    public $apiUrl = 'https://svcs.sandbox.paypal.com/AdaptivePayments/';
    public $paypalUrl="https://www.sandbox.paypal.com/webscr?cmd=_ap-payment&paykey=";
    public $headers;

    public function __construct(){
        $this->headers = array(
            "X-PAYPAL-SECURITY-USERID: ".$this->api_user,
            "X-PAYPAL-SECURITY-PASSWORD: ".$this->api_pass,
            "X-PAYPAL-SECURITY-SIGNATURE: ".$this->api_sig,
            "X-PAYPAL-REQUEST-DATA-FORMAT: JSON",
            "X-PAYPAL-RESPONSE-DATA-FORMAT: JSON",
            "X-PAYPAL-APPLICATION-ID: ".$this->app_id,
        );

        $this->envelope = array(
                "errorLanguage" => "en_US",
                "detailLevel" => "ReturnAll",
        );
    }

    public function getPaymentOptions($payKey){

        

        $packet = array(
            "requestEnvelope" => $this->envelope,
            "payKey" => $payKey
        );

        $res =  $this->_paypalSend($packet,"GetPaymentOptions");

        return $res;

    }

    public function setPaymentOptions(){

    }

    public function _paypalSend($data,$call){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl.$call);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $response = json_decode(curl_exec($ch),true);
        
        return $response;

    }

    public function splitPay(){

        $returnurl = url('admin/completed/payouts');

        // create the pay request
        $createPacket = array(
            "actionType" =>"PAY",
            "currencyCode" => "USD",
            "receiverList" => array(
                "receiver" => array(
                    array(
                        "amount"=> "1.00",
                        "email"=>"ankitswonders@gmail.com"
                    )
                ),
            ),
            "returnUrl" => "$returnurl",
            "cancelUrl" => "http://test.local/payments/cancel",
            "requestEnvelope" => $this->envelope,
        );

        $response = $this->_paypalSend($createPacket,"Pay");
         $payKey = $response['payKey'];

         //Set Payment Detail
         $detailsPacket = array(

        "requestEnvelope" => $this->envelope,

            "payKey" => $payKey,
            "receiverOptions" => array(
                "receiver" => array("email" => "ankitswonders@gmail.com"),
                "invoiceData" => array(
                    "item" => array(
                        array(
                            "name" => 'product 1',
                            "price" => '1.00',
                            "identifier" => 'p1'
                        )
                    )
                )
            ),


         );

          $response = $this->_paypalSend($detailsPacket,"SetPaymentOptions");
           

          $dets = $this->getPaymentOptions($payKey);
           
          //head over to paypal
          return redirect($this->paypalUrl.$payKey);


    }

    public function stripe(){
           
           $stripe = Stripe::make(env('STRIPE_SECRET'));

            $charge = $stripe->charges()->create([
              "amount" => 10,
              "currency" => "usd",
              "source" => "tok_visa",
              "transfer_data" => [
                "destination" => "ac_123456789",
              ],
            ]);

            return $charge;
    }

    public function paytm(){
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
          'order' => '587456',
          'user' => '1',
          'mobile_number' => '7777777777',
          'email' => 'test@demo.com',
          'amount' => 100,
          'callback_url' => url('/paytm-callback')
        ]);
        return $payment->receive();
    }

    public function paymentCallback()
    {
        $transaction = PaytmWallet::with('receive');
        
        $response = $transaction->response(); // To get raw response as array
        //Check out response parameters sent by paytm here -> http://paywithpaytm.com/developer/paytm_api_doc?target=interpreting-response-sent-by-paytm
        
        if($transaction->isSuccessful()){
          //Transaction Successful
        }else if($transaction->isFailed()){
          //Transaction Failed
        }else if($transaction->isOpen()){
          //Transaction Open/Processing
        }
        $transaction->getResponseMessage(); //Get Response Message If Available
        //get important parameters via public methods
        $transaction->getOrderId(); // Get order id
        $transaction->getTransactionId(); // Get transaction id

        dd($response);
    } 
    
}
