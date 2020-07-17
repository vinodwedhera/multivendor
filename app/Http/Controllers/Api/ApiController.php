<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Config;
class ApiController extends Controller
{
    
public function setApiView()
    {
     
      $env_files = [
        'STRIPE_KEY' => env('STRIPE_KEY'),
        'STRIPE_SECRET' => env('STRIPE_SECRET'),
        'MAILCHIMP_APIKEY' => env('MAILCHIMP_APIKEY'),
        'MAILCHIMP_LIST_ID' => env('MAILCHIMP_LIST_ID'),
        'TMDB_API_KEY' => env('TMDB_API_KEY'),
        'PAYPAL_CLIENT_ID' => env('PAYPAL_CLIENT_ID'),
        'PAYPAL_SECRET' => env('PAYPAL_SECRET'),
        'PAYPAL_MODE' => env('PAYPAL_MODE'),
        'PAYU_METHOD' => env('PAYU_METHOD'),
        'PAYU_DEFAULT' => env('PAYU_DEFAULT'),
        'PAYU_MERCHANT_KEY' => env('PAYU_MERCHANT_KEY'),
        'PAYU_MERCHANT_SALT' => env('PAYU_MERCHANT_SALT')
      ];
      return view('admin.mailsetting.api', compact('env_files'));
    }

    public function changeEnvKeys(Request $request)
    {
        $input = $request->all();
       if($request->paypal=='0'){
           $input['PAYPAL_CLIENT_ID'] ='';
           $input['PAYPAL_SECRET'] ='';
           $input['PAYPAL_MODE'] ='';
        }
        if($request->strip=='0')
        {
          $input['STRIPE_KEY'] ='';
          $input['STRIPE_SECRET'] ='';
        }
        // some code
        $env_update = $this->changeEnv([
          'STRIPE_KEY' => $input['STRIPE_KEY'],
          'STRIPE_SECRET' => $input['STRIPE_SECRET'],
          // 'MAILCHIMP_APIKEY' => $request->MAILCHIMP_APIKEY,
          // 'MAILCHIMP_LIST_ID' => $request->MAILCHIMP_LIST_ID,
          'PAYPAL_CLIENT_ID' => $input['PAYPAL_CLIENT_ID'],
          'PAYPAL_SECRET' => $input['PAYPAL_SECRET'],
          'PAYPAL_MODE' => $input['PAYPAL_MODE'],
          
          //'PAYU_METHOD' => $input['PAYU_METHOD'],
          // 'PAYU_DEFAULT' => $request->PAYU_DEFAULT,
          // 'PAYU_MERCHANT_KEY' => $request->PAYU_MERCHANT_KEY,
          // 'PAYU_MERCHANT_SALT' => $request->PAYU_MERCHANT_SALT
        ]);

        if(!isset($input['stripe']))
        {
          $input['stripe'] = 0;
        }

        if(!isset($input['paypal']))
        {
          $input['paypal'] = 0;
        }

        if($env_update) {
            return back()->with('updated', 'Api settings has been saved');
        } else {
          return back()->with('deleted', 'Api settings could not be saved');
        }

    }

    

    protected function changeEnv($data = array()){
    {
        if ( count($data) > 0 ) {

            // Read .env-file
            $env = file_get_contents(base_path() . '/.env');

            // Split string on every " " and write into array
            $env = preg_split('/\s+/', $env);;

            // Loop through given data
            foreach((array)$data as $key => $value){
              // Loop through .env-data
              foreach($env as $env_key => $env_value){
                // Turn the value into an array and stop after the first split
                // So it's not possible to split e.g. the App-Key by accident
                $entry = explode("=", $env_value, 2);

                // Check, if new key fits the actual .env-key
                if($entry[0] == $key){
                    // If yes, overwrite it with the new one
                    $env[$env_key] = $key . "=" . $value;
                } else {
                    // If not, keep the old one
                    $env[$env_key] = $env_value;
                }
              }
            }

            // Turn the array back to an String
            $env = implode("\n\n", $env);

            // And overwrite the .env with the new data
            file_put_contents(base_path() . '/.env', $env);

            return true;

        } else {

          return false;
        }
    }
  }



  public function paytem_create(Request $request){

    $cat = Config::first();
    $input = $request->all();
    if(empty($cat))
         {
      if($request->paytem=='0')
      {
        $input['MERCHANT_KEY'] = null;
        $data = Config::create($input);
        
        $data->save();
        return back()->with("category_message","Paytem Key Has Been created");
      }
      elseif($request->instamojo=='0')
        {
          $input['instamojo_auth_key'] = null;
          $input['instamojo_key'] = null;
          $data = Config::create($input);
          $data->save();
          return back()->with("category_message","Paytem Key Has Been created");
        }

        else{
          $data = Config::create($input);
          $data->save();
          return back()->with("category_message","Paytem Key Has Been created");
        }  

      
    }
     else{
      if($request->paytem=='0'){
        $input['MERCHANT_KEY'] = null;
      
      $cat->update($input);
            return back()->with("category_message","Api Key Has Been Updated");
        }
        elseif($request->instamojo=='0')
        {
          $input['instamojo_auth_key'] = null;
          $input['instamojo_key'] = null;
          $cat->update($input);
        return back()->with("category_message","Api Key Has Been Updated");    
        }
        else{
      $cat->update($input);
        return back()->with("category_message","Api Key Has Been Updated");    
      }
    }
  }

}
