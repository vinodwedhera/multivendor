<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Vender;
use App\Country;
use App\State;
use App\City;
use App\User;
use App\Store;
use App\Product;
use App\Order;
use Auth;
use Image;
use DB;
use App\Config;
use App\Charts\OrderChart;
use App\Allcity;
use App\Allstate;
use Illuminate\Http\Request;
use App\CommissionSetting;
use App\Commission;
use App\InvoiceDownload;
use App\Invoice;
use App\CanceledOrders;
use App\FullOrderCancelLog;
use App\Return_Product;
use App\SellerPayout;

class VenderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (Auth::check())
        {
            $auth_name = Auth::user()->name;
            $auth_id = Auth::user()->id;
        }
        $countrys = Country::all();
        $user = Auth::user();
        $countrys = Country::all();
        $store = Auth::user()->store;
        $states = Allstate::where('country_id', $store->country_id)
            ->get();
        $city = Allcity::where('state_id', $store->state_id)->get();
        
        return view("seller.store.edit", compact("store", "countrys", "states", "city", "user", "auth_id"));
    }

    public function getInvoiceSetting(Request $request)
    {

        return view('seller.invoiceset.setting');

    }

    public function createInvoiceSetting(Request $request)
    {
        $findInvoiceSetting = Invoice::where('user_id', Auth::user()->id)
            ->first();

        if (Auth::check())
        {

            if (empty($findInvoiceSetting))
            {
                $createSetting = new Invoice();

                if ($file = $request->file('sign'))
                {
                    $name = time() . $file->getClientOriginalName();
                    $file->move('images/sign', $name);
                    $createSetting->sign = $name;

                }

                if ($file = $request->file('seal'))
                {

                    $name = time() . $file->getClientOriginalName();

                    $file->move('images/seal', $name);

                    $createSetting->seal = $name;

                }

                $createSetting->user_id = Auth::user()->id;
                $createSetting->save();

                return back()
                    ->with('added', 'Invoice Setting Created For You !');
            }
            else
            {
                $updateSetting = Invoice::where('user_id', Auth::user()->id)
                    ->first();
                if (Auth::user()->id == $updateSetting->user_id)
                {

                    if ($file = $request->file('sign'))
                    {
                        $name = time() . $file->getClientOriginalName();
                        $file->move('images/sign', $name);
                        if ($updateSetting->sign != '')
                        {
                            unlink('../public/images/sign/' . $updateSetting->sign);
                        }
                        $updateSetting->sign = $name;

                    }

                    if ($file = $request->file('seal'))
                    {

                        $name = time() . $file->getClientOriginalName();

                        $file->move('images/seal', $name);
                        if ($updateSetting->seal != '')
                        {
                            unlink('../public/images/seal/' . $updateSetting->seal);
                        }
                        $updateSetting->seal = $name;

                    }

                    $updateSetting->user_id = Auth::user()->id;
                    $updateSetting->save();
                    return back()
                        ->with('updated', 'Invoice Setting Updated for You !');
                }
                else
                {
                    return abort(404);
                }

            }

        }
        else
        {
            return abort(404);
        }
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, ["name" => "required", "mobile" => "required", 'address' => "required", "pin_code" => "required",
        ], ["name.required" => "Store Name is Required",
        "mobile.required" => "Mobile No is Required", "pin_code.required" => "Pin Code is Required",

        ]);
        $input = $request->all();
        $auth_id = Auth::user()->id;
        $cat = Store::where('user_id', $auth_id)->where('rd', '0')
            ->first();
        if (empty($cat))
        {
            if ($file = $request->file('store_logo'))
            {

                $optimizeImage = Image::make($file);
                $optimizePath = public_path() . '/images/store/';
                $image = time() . $file->getClientOriginalName();
                $optimizeImage->save($optimizePath . $image, 72);

                $input['store_logo'] = $image;
                $data = Store::create($input);

                $data->save();
                return back()
                    ->with("added", "Store Has Been created !");
            }
        }
        else
        {

            if ($file = $request->file('store_logo'))
            {

                if ($cat->logo != null)
                {

                    $image_file = @file_get_contents(public_path() . '/images/store/' . $cat->store_logo);

                    if ($image_file)
                    {
                        unlink(public_path() . '/images/store/' . $cat->store_logo);
                    }

                }

                $optimizeImage = Image::make($file);
                $optimizePath = public_path() . '/images/store/';
                $name = time() . $file->getClientOriginalName();
                $optimizeImage->save($optimizePath . $name, 72);

                $input['store_logo'] = $name;

            }

            else
            {
                $input['lostore_logogo'] = $cat->store_logo;
                $cat->update($input);
            }
            $cat->update($input);
            return back()->with("updated", "Store Has Been Updated !");
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
   

    public function update(Request $request, $id)
    {

        $store = Store::find($id);

        $pincodesystem = Config::first()->pincode_system;

        $data = $this->validate($request, [

        "name" => "required", "email" => "required|max:255", "mobile" => "required"

        ], [

        "name.required" => "Store Name is needed", "email.required" => "Email is needed", "mobile.required" => "Mobile No is needed",

        ]);

        if ($pincodesystem == 1)
        {

            $request->validate(['pin_code' => 'required'], ["pin_code.required" => "Pincode is required", ]);

        }

        $store = Store::findOrFail($id);

        $input = $request->all();

        if ($file = $request->file('store_logo'))
        {

            if ($store->store_logo != null)
            {

                $image_file = @file_get_contents(public_path() . '/images/store/' . $store->store_logo);

                if ($image_file)
                {
                    unlink(public_path() . '/images/store/' . $store->store_logo);
                }

            }

            $optimizeImage = Image::make($file);
            $optimizePath = public_path() . '/images/store/';
            $name = time() . $file->getClientOriginalName();
            $optimizeImage->save($optimizePath . $name, 72);

            $input['store_logo'] = $name;

        }

        else
        {
            $input['store_logo'] = $store->store_logo;
            $store->update($input);
        }

        $store->update($input);

        return back()->with('updated', 'Store details has been updated !');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $conversion_rate = Store::where('id', $id)->first();
        DB::table('stores')
            ->where('id', $id)->update(['rd' => '1']);
        if ($conversion_rate)
        {
            $auth_id = Auth::user()->id;

            DB::table('products')
                ->where('vender_id', $auth_id)->update(array(
                'status' => '0'
            ));
        }
        return back()
            ->with('deleted', 'Store has been Deleted');
    }

    public function dashbord()
    {

        $products = Product::where('vender_id', Auth::user()->id)
            ->get();
        $returnorders = Return_Product::orderBy('id', 'DESC')->get();
        $payouts = SellerPayout::where('sellerid', Auth::user()->id)
            ->count();
        $actualorder = Order::where('status','=','1')->get();
        $inv_cus = Invoice::first();
        $orders = array();
        $ro2 = collect();
        $money = SellerPayout::where('sellerid','=',Auth::user()->id)->sum('orderamount');

        /*Creating order chart*/

        $totalorder = array(

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','01')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //January

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','02')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //Feb

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','03')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //March

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','04')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //April

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','05')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //May

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','06')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //June

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','07')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //July

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','08')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //August

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','09')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //September

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','10')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //October

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','11')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //November

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','12')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //December

            );

            $sellerorders = new OrderChart;

            $sellerorders->labels(['January', 'Febuary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);

            $sellerorders->title('Total Orders In '.date('Y'))->label('Sales')->dataset('Months', 'area', $totalorder)->options([
                'fill' => 'true',
                'borderColor' => '#51C1C0',
                'shadow' => true
            ]);


        /*END*/


        foreach ($actualorder as $value)
        {
            if (in_array(Auth::user()->id, $value->vender_ids))
            {
                array_push($orders, $value);
            }
        }

        foreach ($returnorders as $key => $ro)
        {
            if ($ro
                ->getorder->vender_id == Auth::user()
                ->id)
            {
                $ro2->push($ro);
            }
        }

        $sellercanorders = collect();
        $allcanorders = CanceledOrders::orderBy('id', 'DESC')->get();
        $allfullcanceledorders = FullOrderCancelLog::orderBy('id', 'DESC')->get();
        $unreadorder = collect();
        $unreadorder2 = collect();

        /*partial cancel order section*/
        foreach ($allcanorders as $key => $value)
        {

            if ($value
                ->singleOrder->vender_id == Auth::user()
                ->id)
            {

                $sellercanorders->push($value);

            }

        }

        foreach ($sellercanorders as $key => $sorder)
        {

            $unreadorder->push($sorder);

        }
        /*end*/

        /*full order detect per seller section*/

        $sellerfullcanorders = collect();
        $total = 0;

        foreach ($allfullcanceledorders as $key => $forder)
        {
            $order = Order::find($forder->order_id);

            if (in_array(Auth::user()->id, $order->vender_ids))
            {
                $sellerfullcanorders->push($forder);
            }
        }

        foreach ($sellerfullcanorders as $key => $sorder)
        {

            $unreadorder2->push($sorder);

        }
        /*end*/

        $partialcount = count($unreadorder);
        $partialcount2 = count($unreadorder2);

        $totalcanorders = $partialcount + $partialcount2;
        $totalreturnorders = count($ro2);

        /*find store*/
        $ifstore = Store::where('user_id', Auth::user()->id)
            ->first();

        if(!$ifstore){
            notify()
                ->error('Sorry Your store is not created yet ! Please apply for seller account under My account menu !');
                return back();
        }

        if ($ifstore->apply_vender == '0' || $ifstore->status == '0')
        {
            notify()->error('Sorry Your store is not active yet ! once it will active you can start selling your items');
            return redirect('/');
        }

        return view('seller.dashbord.index', compact('money', 'payouts', 'sellerorders', 'totalreturnorders', 'orders', 'products', 'inv_cus', 'totalcanorders'));
    }

    public function enable()
    {
        $auth_id = Auth::user()->id;
        // $conversion_rate = Store::where('user_id',$auth_id)->first();
        $conversion_rate = DB::table('stores')->where('user_id', $auth_id)->update(['rd' => '0']);
        if ($conversion_rate)
        {
            $auth_id = Auth::user()->id;

            DB::table('products')
                ->where('vender_id', $auth_id)->update(array(
                'status' => '1'
            ));
        }
        return back()->with('added', 'Active Store');
    }

    public function order()
    {

        $getorder = Order::orderBy('id', 'DESC')->get();

        $emptyOrder = array();

        $inv_cus = Invoice::first();

        $totalorder = array(

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','01')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //January

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','02')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //Feb

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','03')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //March

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','04')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //April

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','05')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //May

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','06')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //June

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','07')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //July

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','08')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //August

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','09')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //September

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','10')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //October

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','11')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //November

                  Order::where('vender_ids', 'like', '%' . Auth::user()->id . '%')->where('status','1')->whereMonth('created_at','12')
                  ->whereYear('created_at', date('Y'))
                  ->count(), //December

            );

            $sellerorders = new OrderChart;

            $sellerorders->labels(['January', 'Febuary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);

            $sellerorders->title('Total Orders In '.date('Y'))->label('Sales')->dataset('Months', 'area', $totalorder)->options([
                'fill' => 'true',
                'borderColor' => '#51C1C0',
                'shadow' => true
            ]);


        /*END*/

        foreach ($getorder as $value)
        {

            if (in_array(Auth::user()->id, $value->vender_ids))
            {

               
                array_push($emptyOrder, $value);
                
                
            }

        }

        return view('seller.order.index', compact('sellerorders', 'emptyOrder', 'inv_cus'));

    }

    public function commission()
    {

        $commissions = CommissionSetting::all();

        return view('seller.commission.index', compact('commissions'));
    }

    public function profile()
    {
        $auth_id = Auth::user()->id;
        $user = User::where('id', $auth_id)->first();
        $country = Country::all();
        $citys = Allcity::all();
        $states = Allstate::where('country_id', $user->country_id)
            ->get();
        $citys = Allcity::where('state_id', $user->state_id)
            ->get();
        return view('seller.profile.edit', compact("user", "country", "states", "citys"));
    }

    public function updateprofile(Request $request)
    {

        $data = $this->validate($request, ['name' => 'required|string|max:255', 'email' => 'required|email|max:255', 'country_id' => 'required|not_in:0', 'state_id' => 'required|not_in:0', 'city_id' => 'required|not_in:0', ]);
        $auth_id = Auth::user()->id;
        $user = User::where('id', $auth_id)->first();
        $input = $request->all();

        if ($request->has('test'))
        {
            $this->validate($request, ['password' => 'required|between:6,255|confirmed', 'password_confirmation' => 'required', ]);
            $name = Hash::make($request->password);
            $input['password'] = $name;
            $user->update($input);
        }
        else
        {

            if ($file = $request->file('image'))
            {

                if ($user->image != null)
                {

                    $image_file = @file_get_contents(public_path() . '/images/user/' . $user->image);

                    if ($image_file)
                    {
                        unlink(public_path() . '/images/user/' . $user->image);
                    }

                }

                $optimizeImage = Image::make($file);
                $optimizePath = public_path() . '/images/user/';
                $name = time() . $file->getClientOriginalName();
                $optimizeImage->save($optimizePath . $name, 72);

                $input['image'] = $name;

            }

            else
            {
                $input['password'] = $user->password;
                $input['image'] = $user->image;
                try
                {

                    $user->update($input);

                }
                catch(\Illuminate\Database\QueryException $e)
                {
                    $errorCode = $e->errorInfo[1];
                    if ($errorCode == '1062')
                    {
                        return back()->with("warning", "Email Already Exists");
                    }
                }

            }

            try
            {

                $user->update($input);

            }
            catch(\Illuminate\Database\QueryException $e)
            {
                $errorCode = $e->errorInfo[1];
                if ($errorCode == '1062')
                {
                    return back()->with("warning", "Email Already Exists");
                }
            }

        }

        return back()
            ->with('updated', 'Profile has been updated !');
    }

}

