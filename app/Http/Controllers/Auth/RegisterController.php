<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Cart;
use Session;
use App\Product;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
        protected $redirectTo = '/';


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
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'mobile' => 'numeric',

        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        
        $user =  User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password'])

        ]);

        if(Session::has('cart')){

            foreach (Session::get('cart') as $key => $c) {

                        $venderid = Product::findorFail($c['pro_id']);

                        $cart = new Cart;
                        $cart->user_id = $user->id;
                        $cart->qty = $c['qty'];
                        $cart->pro_id = $c['pro_id'];
                        $cart->variant_id = $c['variantid'];
                        $cart->ori_price = $c['varprice'];
                        $cart->ori_offer_price = $c['varofferprice'];
                        $cart->semi_total = $c['qty'] * $c['varofferprice'];
                        $cart->price_total = $c['qty'] * $c['varprice'];
                        $cart->vender_id = $venderid->vender_id;
                        $cart->save();
            }

        }

        Session::forget('cart');

        return $user;


    }
}
