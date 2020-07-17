<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Config;

class KeyController extends Controller
{

    public function setApiView()
    {
        $config = Config::first();
        $env_files = ['STRIPE_KEY' => env('STRIPE_KEY') , 'STRIPE_SECRET' => env('STRIPE_SECRET') , 'MAILCHIMP_APIKEY' => env('MAILCHIMP_APIKEY') , 'MAILCHIMP_LIST_ID' => env('MAILCHIMP_LIST_ID') , 'TMDB_API_KEY' => env('TMDB_API_KEY') , 'PAYPAL_CLIENT_ID' => env('PAYPAL_CLIENT_ID') , 'PAYPAL_SECRET' => env('PAYPAL_SECRET') , 'PAYPAL_MODE' => env('PAYPAL_MODE') ];
        return view('admin.mailsetting.api', compact('env_files', 'config'));
    }

    public function razorpay()
    { 
        $config = Config::first();

        $env_files = ['RAZOR_PAY_KEY' => env('RAZOR_PAY_KEY') , 'RAZOR_PAY_SECRET' => env('RAZOR_PAY_SECRET') , 'PAYTM_ENVIRONMENT' => env('PAYTM_ENVIRONMENT') , 'PAYTM_MERCHANT_ID' => env('PAYTM_MERCHANT_ID') , 'PAYTM_MERCHANT_KEY' => env('PAYTM_MERCHANT_KEY') ];

        return view('admin.mailsetting.rpay', compact('env_files', 'config'));
    }

    public function updatePaytm(Request $request)
    {
        $input = $request->all();

        $config = Config::first();

        $env_update = $this->changeEnv([

        'PAYTM_ENVIRONMENT' => $input['PAYTM_ENVIRONMENT'], 'PAYTM_MERCHANT_ID' => $input['PAYTM_MERCHANT_ID'], 'PAYTM_MERCHANT_KEY' => $input['PAYTM_MERCHANT_KEY']

        ]);

        if (isset($request->paytmchk))
        {
            $config->paytm_enable = 1;
        }
        else
        {
            $config->paytm_enable = 0;
        }

        $config->save();

        return back()
            ->with('updated', 'Api settings has been saved !');
    }

    public function updaterazorpay(Request $request)
    {
        $input = $request->all();
        $config = Config::first();

        $env_update = $this->changeEnv([

            'RAZOR_PAY_KEY' => $input['RAZOR_PAY_KEY'], 'RAZOR_PAY_SECRET' => $input['RAZOR_PAY_SECRET']

        ]);

        if (isset($request->rpaycheck))
        {
            $config->razorpay = "1";
        }
        else
        {
            $config->razorpay = "0";
        }

        $config->save();

        return back()
            ->with('updated', 'Api settings has been saved !');

    }

    public function saveKeys(Request $request)
    {

        $input = $request->all();
        $config = Config::first();

        $env_update = $this->changeEnv(['STRIPE_KEY' => $input['STRIPE_KEY'], 'STRIPE_SECRET' => $input['STRIPE_SECRET'],

        'PAYPAL_CLIENT_ID' => $input['PAYPAL_CLIENT_ID'], 'PAYPAL_SECRET' => $input['PAYPAL_SECRET'], 'PAYPAL_MODE' => $input['PAYPAL_MODE'],

        ]);

        if (isset($request->strip_check))
        {
            $config->stripe_enable = "1";
        }
        else
        {
            $config->stripe_enable = "0";
        }

        if (isset($request->paypal_check))
        {
            $config->paypal_enable = "1";
        }
        else
        {
            $config->paypal_enable = "0";
        }

        $config->save();

        if ($env_update)
        {
            return back()->with('updated', 'Api settings has been saved !');
        }
        else
        {
            return back()
                ->with('warning', 'Api settings could not be saved !');
        }
    }

    public function payuInsta_show()
    {
        $config = Config::first();
        $env_files = ['PAYU_METHOD' => env('PAYU_METHOD') , 'PAYU_DEFAULT' => env('PAYU_DEFAULT') , 'PAYU_MERCHANT_KEY' => env('PAYU_MERCHANT_KEY') , 'PAYU_MERCHANT_SALT' => env('PAYU_MERCHANT_SALT') , 'PAYU_REFUND_URL' => env('PAYU_REFUND_URL') , 'INSTAMOJO_SALT' => env('INSTAMOJO_SALT') , 'IM_API_KEY' => env('IM_API_KEY') , 'IM_AUTH_TOKEN' => env('IM_AUTH_TOKEN') , 'IM_URL' => env('IM_URL') , 'IM_REFUND_URL' => env('IM_REFUND_URL') , ];
        return view('admin.mailsetting.payuinsta', compact('config', 'env_files'));
    }

    public function payuInsta(Request $request)
    {

        $cat = Config::first();
        $input = $request->all();

        if (isset($request->payu_chk))
        {
            $cat->payu_enable = "1";
        }
        else
        {
            $cat->payu_enable = "0";
        }

        if (isset($request->instam_check))
        {
            $cat->instamojo_enable = "1";
        }
        else
        {
            $cat->instamojo_enable = "0";
        }

        $env_update = $this->changeEnv([

            'PAYU_METHOD' => $input['PAYU_METHOD'], 
            'PAYU_DEFAULT' => $input['PAYU_DEFAULT'], 
            'PAYU_MERCHANT_KEY' => $input['PAYU_MERCHANT_KEY'], 
            'PAYU_MERCHANT_SALT' => $input['PAYU_MERCHANT_SALT'],
            'PAYU_AUTH_HEADER' => $input['PAYU_AUTH_HEADER'],
            'PAY_U_MONEY_ACC' => isset($request->PAY_U_MONEY_ACC) ? "true" : "false",
            'PAYU_REFUND_URL' => $input['PAYU_REFUND_URL'], 
            'IM_API_KEY' => $input['IM_API_KEY'], 
            'IM_AUTH_TOKEN' => $input['IM_AUTH_TOKEN'], 
            'IM_URL' => $input['IM_URL'], 
            'IM_REFUND_URL' => $input['IM_REFUND_URL']
        ]);

        $cat->save();

        return back()
            ->with('updated', 'Paytm and Instamojo Keys has been Saved !');

    }

    protected function changeEnv($data = array())
    {
        {
            if (count($data) > 0)
            {

                // Read .env-file
                $env = file_get_contents(base_path() . '/.env');

                // Split string on every " " and write into array
                $env = preg_split('/\s+/', $env);;

                // Loop through given data
                foreach ((array)$data as $key => $value)
                {
                    // Loop through .env-data
                    foreach ($env as $env_key => $env_value)
                    {
                        // Turn the value into an array and stop after the first split
                        // So it's not possible to split e.g. the App-Key by accident
                        $entry = explode("=", $env_value, 2);

                        // Check, if new key fits the actual .env-key
                        if ($entry[0] == $key)
                        {
                            // If yes, overwrite it with the new one
                            $env[$env_key] = $key . "=" . $value;
                        }
                        else
                        {
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

            }
            else
            {

                return false;
            }
        }
    }

}

