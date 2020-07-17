<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Category;
use App\Subcategory;
use App\Grandcategory;
use App\Store;
use App\User;
use App\Brand;
use App\Country;
use App\State;
use App\City;
use App\RealatedProduct;
use App\Related_setting;
use Auth;
use DB;
use App\FaqProduct;
use Image;
use App\CommissionSetting;
use App\Commission;
use Excel;
use App\admin_return_product;
use App\TaxClass;
use Session;
use App\Genral;
use App\Shipping;
use App\AddProductVariant;
use App\AddSubVariant;
use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;

class VenderProductController extends Controller
{
    public function index()
    {
        
        $products = Product::orderBy('id','DESC')->where('vender_id', Auth::user()->id)
            ->get();
        return view('seller.product.index', compact('products'));
    }

    public function allvariants($id)
    {
        $pro = Product::findOrFail($id);
        return view('seller.product.allvar', compact('pro'));
    }

    public function importPage()
    {
        return view('seller.product.import');
    }

     public function bulk_delete(Request $request)
    {
        $validator = Validator::make($request->all() , ['checked' => 'required', ]);

        if ($validator->fails())
        {

            return back()
                ->with('warning', 'Please select one of them to delete');
        }

        foreach ($request->checked as $checked)
        {

            $pro = Product::findOrFail($checked);

            $provar = AddProductVariant::where('pro_id', $checked)->first();

            $subvar = AddSubVariant::where('pro_id', $checked)->get();

            DB::table('add_sub_variants')
                ->where('pro_id', $checked)->delete();

            if (isset($provar))
            {
                DB::table('add_product_variants')->where('pro_id', $checked)->delete();
            }

            foreach ($pro->reviews as $value)
            {
                $value->delete();
            }

            if (isset($subvar))
            {

                foreach ($subvar as $s)
                {

                    if ($s->variantimages[0]['image1'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image1']);
                    }

                    if ($s->variantimages[0]['image2'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image2']);
                    }

                    if ($s->variantimages[0]['image3'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image3']);
                    }

                    if ($s->variantimages[0]['image4'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image4']);
                    }

                    if ($s->variantimages[0]['image5'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image5']);
                    }

                    if ($s->variantimages[0]['image6'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image6']);
                    }

                    DB::table('variant_images')
                        ->where('var_id', $s->id)
                        ->delete();

                }

            }

            $pro::destroy($checked);

        }

        return back()->with('deleted', 'Selected Products has been deleted !');
    }

    public function storeImportProducts(Request $request)
    {


            $request->validate(['file' => 'required|mimes:CSV,csv,xlsx']);

            if(!$request->has('file')){
                return back()->with('warning','Please choose a file !');
            }

            $fileName = time().'.'.$request->file->extension();

            if (!is_dir(public_path().'/excel')){
                 mkdir(public_path().'/excel');
            }

            $request->file->move(public_path('excel'), $fileName);

       

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);



            $productfile = (new FastExcel)->import(public_path().'/excel/'.$fileName);
            

            if (count($productfile)>0)
            {
                   
                foreach ($productfile as $key => $line){
                  
                    $rowno = $key+1;
                    $sellPrice = 0;
                    $sellofferPrice = 0;
                    $commissionRate = 0;

                    $catid = Category::where('title->'.Session::get('changed_language'), $line['category_name'])->first();

                    if (!isset($catid))
                    {
                        $file = @file_get_contents(public_path().'/excel/'.$fileName);

                        if($file){
                            unlink(public_path().'/excel/'.$fileName);
                        }
                        return back()->with('warning',"Category not found at $rowno");
                    }

                    $subcatid = Subcategory::where('title->'.Session::get('changed_language'), $line['subcategory_name'])->first();

                    if (!isset($subcatid))
                    {
                        $file = @file_get_contents(public_path().'/excel/'.$fileName);

                        if($file){
                            unlink(public_path().'/excel/'.$fileName);
                        }
                        return back()->with('warning', "Invalid subcategory name at Row no $rowno Subcategory not found ! Please create it and than try to import this file again !");
                        break;
                    }

                    $brandnid = Brand::where('name', $line['brand_name'])->first();

                    if (!isset($brandnid))
                    {

                        $file = @file_get_contents(public_path().'/excel/'.$fileName);

                        if($file){
                            unlink(public_path().'/excel/'.$fileName);
                        }

                        return back()->with('warning', "Invalid brand name at Row no $rowno brand not found ! Please create it and than try to import this file again !");
                        break;
                    }

                    $store = Store::where('name', $line['store_name'])->first();  

                    if (!isset($store))
                    {
                        $file = @file_get_contents(public_path().'/excel/'.$fileName);

                        if($file){
                            unlink(public_path().'/excel/'.$fileName);
                        }

                        return back()->with('warning', "Invalid Store name at Row no $rowno Store not found ! Please create it and than try to import this file again !");
                        break;
                    }

                    if ($line['return_available'] != 0)
                    {

                        $p = admin_return_product::where('name', $line['return_policy'])->first();

                        if (!isset($p))
                        {
                            $file = @file_get_contents(public_path().'/excel/'.$fileName);

                            if($file){
                                unlink(public_path().'/excel/'.$fileName);
                            }

                            return back()->with('warning', "Invalid Return Policy name at Row no $rowno Return Policy not found ! Please create it and than try to import this file again !");
                            break;
                        }

                        $policy = $p->id;
                    }
                    else
                    {
                        $policy = 0;
                    }

                    if ($line['tax'] != 0)
                    {

                        $tc = TaxClass::find($line['tax']);

                        if (!isset($tc))
                        {
                            $file = @file_get_contents(public_path().'/excel/'.$fileName);

                            if($file){
                                unlink(public_path().'/excel/'.$fileName);
                            }

                            return back()->with('warning', "Invalid TaxClass name at Row no $rowno TaxClass not found ! Please create it and than try to import this file again !");
                            break;
                        }

                        $taxClass = $tc->id;

                    }
                    else
                    {

                        $taxClass = 0;

                    }

                    if ($line['free_shipping'] != 1)
                    {

                        $freeShipping = 1;
                        $ship = Shipping::where('default_status', '1')->first();

                        if(!isset($ship)){
                            $file = @file_get_contents(public_path().'/excel/'.$fileName);

                            if($file){
                                unlink(public_path().'/excel/'.$fileName);
                            }

                            return back()->with('warning', "Invalid Shipping name at Row no $rowno Childcategory not found ! Please create it and than try to import this file again !");
                           break;
                        }

                        $shippingID = $ship->id;

                    }
                    else
                    {

                        $freeShipping = 0;
                        $shippingID = NULL;

                    }

                    if ($line['childcategory'] != '')
                    {
                        $c = Grandcategory::where('title', $line['childcategory'])
                            ->first();

                        if (!isset($c))
                        {
                            $file = @file_get_contents(public_path().'/excel/'.$fileName);

                            if($file){
                                unlink(public_path().'/excel/'.$fileName);
                            }

                            return back()->with('warning', "Invalid childcategory name at Row no $rowno Childcategory not found ! Please create it and than try to import this file again !");
                            break;
                        }

                        $childid = $c->id;
                    }
                    else
                    {
                        $childid = 0;
                    }

                    

                    /*Commission Price*/
                        $commissions = CommissionSetting::all();
                        foreach ($commissions as $commission)
                        {
                            if ($commission->type == "flat")
                            {
                                if ($commission->p_type == "f")
                                {

                                    if($line['tax_rate'] !=''){

                                         $cit = $commission->rate*$line['tax_rate']/100;
                                         $price = $line['price'] + $commission->rate+$cit;
                                         $offer = $line['offer_price'] + $commission->rate+$cit;

                                    }else{
                                        $price = $line['price'] + $commission->rate;
                                        $offer = $line['offer_price'] + $commission->rate;
                                    }
                                    
                                    $sellPrice = $price;
                                    $sellofferPrice = $offer;
                                    $commissionRate = $commission->rate;

                                }
                                else
                                {

                                    $taxrate = $commission->rate;
                                    $price1 = $line['price'];
                                    $price2 = $line['offer_price'];
                                    $tax1 = ($price1 * (($taxrate / 100)));
                                    $tax2 = ($price2 * (($taxrate / 100)));
                                    $sellPrice = $price1 + $tax1;
                                    $sellofferPrice = $price2 + $tax2;
                  
                                    if (!empty($tax2))
                                    {
                                        $commissionRate = $tax2;
                                    }
                                    else
                                    {
                                        $commissionRate = $tax1;
                                    }
                                }
                            }
                            else
                            {

                                $comm = Commission::where('category_id', $catid)
                                    ->first();
                                if (isset($comm))
                                {
                                    if ($comm->type == 'f')
                                    {

                                        if($line['tax_rate'] !=''){

                                            $cit = $comm->rate*$line['tax_rate']/100;
                                            $price = $line['price'] + $comm->rate + $cit;
                                            $offer = $line['offer_price'] + $comm->rate + $cit;

                                        }else{

                                            $price = $line['price'] + $comm->rate;
                                            $offer = $line['offer_price'] + $comm->rate;

                                        }
                                        
                                        $sellPrice = $price;
                                        $sellofferPrice = $offer;
                                        $commissionRate = $comm->rate;

                                    }
                                    else
                                    {
                                        $taxrate = $comm->rate;
                                        $price1 = $line['price'];
                                        $price2 = $line['offer_price'];
                                        $tax1 =   ($price1 * (($taxrate / 100)));
                                        $tax2 = ($price2 * (($taxrate / 100)));
                                        $price = $line['price'] + $tax1;
                                        $offer = $line['offer_price'] + $tax2;
                                        $sellPrice = $price;
                                        $sellofferPrice = $offer;

                                        if (!empty($tax2))
                                        {
                                            $commissionRate = $tax2;
                                        }
                                        else
                                        {
                                            $commissionRate = $tax1;
                                        }
                                    }
                                }else{
                                    $commissionRate = 0;
                                }
                            }

                        }
                        /**/

                        //convert for enum value 
                          if($line['featured'] == 0){
                            $featured = '0';
                          }else{
                            $featured = '1';
                          }

                          if($line['status'] == 0){
                            $pstatus = '0';
                          }else{
                            $pstatus = '1';
                          }
                        /**/

                     $product = Product::create([
                        
                        'category_id' => $catid->id, 
                        'child' => $subcatid->id, 
                        'grand_id' => $childid, 
                        'store_id' => $store->id, 
                        'vender_id' => Auth::user()->id, 
                        'brand_id' => $brandnid->id, 
                        'name' => $line['product_name'], 
                        'des' => clean($line['product_description']), 
                        'tags' => $line['tags'], 
                        'model' => $line['model_no'], 
                        'sku' => $line['sku'], 
                        'price_in' => $line['price_in'], 
                        'price' =>  $sellPrice, 
                        'offer_price' => $sellofferPrice, 
                        'featured' => $featured, 
                        'status' => $pstatus, 
                        'vender_price' => $line['price'], 
                        'vender_offer_price' => $line['offer_price'], 
                        'tax' => $taxClass, 
                        'codcheck' => $line['cash_on_delivery'], 
                        'free_shipping' => $freeShipping, 
                        'selling_start_at' => $line['selling_start_at'],
                        'return_avbl' => $line['return_available'], 
                        'cancel_avl' => $line['cancel_available'], 
                        'w_d' => $line['warranty_in_days'], 
                        'w_my' => $line['warranty_in_monthsyears'], 
                        'w_type' => $line['warranty_type'], 
                        'commission_rate' => $commissionRate, 
                        'shipping_id' => $shippingID, 
                        'return_policy' => $policy, 
                        'tax_r' => $line['tax_rate'], 
                        'tax_name' => $line['tax_name'], 
                        'created_at' => date('Y-m-d h:i:s') , 
                        'updated_at' => date('Y-m-d h:i:s')

                    ]);



                    $relsetting = new Related_setting;
                    $relsetting->pro_id = $product->id;
                    $relsetting->status = '0';
                    $relsetting->save();



                }
                
             
                Session::flash('added', 'Your Data has successfully imported');
                $file = @file_get_contents(public_path().'/excel/'.$fileName);

                if($file){
                    unlink(public_path().'/excel/'.$fileName);
                }
                return back();
                    

            }else{
                Session::flash('warning', 'Your Excel file is empty !');
                $file = @file_get_contents(public_path().'/excel/'.$fileName);

                if($file){
                    unlink(public_path().'/excel/'.$fileName);
                }
                return back();
            }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Auth::check())
        {
            $auth_name = Auth::user()->name;
            $vender_id = Auth::user()->id;
        }

        $brands = Brand::all();
        $categorys = Category::all();
        
        return view('seller.product.create', compact('auth_name', 'brands', 'categorys', 'vender_id'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $data = $this->validate($request, ["name" => "required", "price" => "required", 'brand_id' => 'required|not_in:0', 'category_id' => 'required|not_in:0', 'child' => 'required|not_in:0'

        ], [

        "name.required" => "Product Name is needed", "price.required" => "Price is needed", "brand_id.required" => "Please Choose Brand",

        ]);

        $input = $request->all();
        $currency_code = Genral::first()->currency_code;

        if (isset($request->codcheck))
        {
            $input['codcheck'] = "1";
        }
        else
        {
            $input['codcheck'] = "0";
        }

        if (isset($request->featured))
        {
            $input['featured'] = "1";
        }
        else
        {
            $input['featured'] = "0";
        }

        if (isset($request->tax_manual))
        {

            $request->validate(['tax_r' => 'required|numeric', 'tax_name' => 'string|required|min:1', ]);

            $input['tax'] = 0;

        }
        else
        {

            $input['tax_r'] = NULL;
            $input['tax_name'] = NULL;

        }

        if (isset($request->free_shipping))
        {

            $input['free_shipping'] = "1";
        }
        else
        {

            $sid = Shipping::where('default_status', "1")->first();
            $input['shipping_id'] = $sid->id;
            $input['free_shipping'] = "0";
        }

        $input['price_in'] = $currency_code;

        if ($request->vender_price == '')
        {
            $input['vender_price'] = $request->price;
            $input['vender_offer_price'] = $request->offer_price;
        }

        $commissions = CommissionSetting::all();
        foreach ($commissions as $commission)
        {
            if ($commission->type == "flat")
            {
                if ($commission->p_type == "f")
                {

                    $price = $input['price'] + $commission->rate;
                    $offer = $input['offer_price'] + $commission->rate;
                    $input['price'] = $price;
                    $input['offer_price'] = $offer;
                    $input['commission_rate'] = $commission->rate;

                }
                else
                {

                    $taxrate = $commission->rate;
                    $price1 = $input['price'];
                    $price2 = $input['offer_price'];
                    $tax1 = $priceMinusTax = ($price1 * (($taxrate / 100)));
                    $tax2 = $priceMinusTax = ($price2 * (($taxrate / 100)));
                    $price = $input['price'] + $tax1;
                    $offer = $input['offer_price'] + $tax2;
                    $input['price'] = $price;
                    $input['offer_price'] = $offer;
                    if (!empty($tax2))
                    {
                        $input['commission_rate'] = $tax2;
                    }
                    else
                    {
                        $input['commission_rate'] = $tax1;
                    }
                }
            }
            else
            {

                $comm = Commission::where('category_id', $request->category_id)
                    ->first();
                if (isset($comm))
                {
                    if ($comm->type == 'f')
                    {

                        $price = $input['price'] + $comm->rate;
                        $offer = $input['offer_price'] + $comm->rate;
                        $input['price'] = $price;
                        $input['offer_price'] = $offer;
                        $input['commission_rate'] = $comm->rate;

                    }
                    else
                    {
                        $taxrate = $comm->rate;
                        $price1 = $input['price'];
                        $price2 = $input['offer_price'];
                        $tax1 = $priceMinusTax = ($price1 * (($taxrate / 100)));
                        $tax2 = $priceMinusTax = ($price2 * (($taxrate / 100)));
                        $price = $input['price'] + $tax1;
                        $offer = $input['offer_price'] + $tax2;
                        $input['price'] = $price;
                        $input['offer_price'] = $offer;

                        if (!empty($tax2))
                        {
                            $input['commission_rate'] = $tax2;
                        }
                        else
                        {
                            $input['commission_rate'] = $tax1;
                        }
                    }
                }
            }

        }

        if ($request->return_avbls == "1")
        {

            $request->validate(['return_avbls' => 'required', 'return_policy' => 'required'], ['return_policy.required' => 'Please choose return policy']);

            if ($request->return_policy === "Please choose an option")
            {
                return back()
                    ->with('warning', 'Please choose a return policy !');
            }

        }

        if ($request->return_avbls == "1")
        {

            $input['return_avbl'] = "1";
            $input['return_policy'] = $request->return_policy;
        }
        else
        {

            $input['return_avbl'] = 0;
            $input['return_policy'] = 0;
        }

        $input['vender_id'] = Auth::user()->id;

        $input['w_d'] = $request->w_d;
        $input['w_my'] = $request->w_my;
        $input['w_type'] = $request->w_type;

        $data = Product::create($input);

        $data->save();

        $relsetting = new Related_setting;
        $relsetting->pro_id = $data->id;
        $relsetting->status = '0';
        $relsetting->save();

        return redirect()->route('seller.add.var',$data->id)->with('success','Product created !  create a variant now ');

    }

    public function show_pro_image($id)
    {
        if (Auth::check())
        {
            $auth_name = Auth::user()->name;
            $vender_id = Auth::user()->id;
        }

        $brands = Brand::all();
        $products = Product::find($id);
        $categorys = Category::all();
        $faqs = FaqProduct::where('pro_id', $id)->get();
        $realateds = RealatedProduct::get();
        $pro_image = DB::table('pro_images')->where('pro_id', $id)->get();
        $cat_id = Product::where('id', $id)->first();
        $child = Subcategory::where('parent_cat', $cat_id->category_id)
            ->get();
        $grand = Grandcategory::where('subcat_id', $cat_id->child)
            ->get();
        $stores = Store::where('user_id', auth()->id())
            ->where('status', '1')
            ->where('rd', '0')
            ->get();
        $pro_image = DB::table('pro_images')->where('pro_id', $id)->get();
        return view("seller.product.edit_tab", compact('auth_name', 'stores', 'brands', 'categorys', 'vender_id', 'pro_image', 'products', 'child', 'grand'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Vender $vender)
    {
        //
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        session()->put('faqproduct', ['id' => $id, ]);
        $brands = Brand::all();
        $products = Product::find($id);
        $categorys = Category::all();
        $store = Store::where('user_id', Auth::user()->id)
            ->where('status', "1")
            ->first();
        $faqs = FaqProduct::where('pro_id', $id)->get();
        $cat_id = Product::where('id', $id)->first();
        $child = Subcategory::where('parent_cat', $cat_id->category_id)
            ->get();
        $realateds = RealatedProduct::get();
        $rel_setting = $products->relsetting;
        //$child_id = Subcategory::where('parent_cat',$cat_id->category_id)->first();
        $grand = Grandcategory::where('subcat_id', $cat_id->child)
            ->get();
      
      return view("seller.product.edit_tab", compact('rel_setting', "products", "categorys", "store", "brands", "faqs", "child", "grand", "realateds"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $products = Product::findOrFail($id);
        $currency_code = Genral::first()->currency_code;
        //return $products->image;
        $data = $this->validate($request, ["name" => "required", "price" => "required|numeric", "brand_id.required" => "Please Choose Brand"

        ], [

        "name.required" => "Product Name is needed", "price.required" => "Price is needed",

        ]);

        $product = Product::findOrFail($id);

        $input = $request->all();

        if (isset($request->codcheck))
        {
            $input['codcheck'] = "1";
        }
        else
        {
            $input['codcheck'] = "0";
        }

        if (isset($request->featured))
        {
            $input['featured'] = "1";
        }
        else
        {
            $input['featured'] = "0";
        }

        if (isset($request->tax_manual))
        {

            $request->validate(['tax_r' => 'required|numeric', 'tax_name' => 'string|required|min:1', ]);

            $input['tax'] = 0;

        }
        else
        {

            $input['tax_r'] = NULL;
            $input['tax_name'] = NULL;

        }

        if ($request->price != $product->price)
        {

            if ($request->vender_price == '')
            {
                $input['vender_price'] = $request->price;
                $input['vender_offer_price'] = $request->offer_price;
            }
            $commissions = CommissionSetting::all();
            foreach ($commissions as $commission)
            {
                if ($commission->type == "flat")
                {
                    if ($commission->p_type == "f")
                    {

                        $price = $input['price'] + $commission->rate;
                        $offer = $input['offer_price'] + $commission->rate;
                        $input['price'] = $price;
                        $input['offer_price'] = $offer;
                        $input['commission_rate'] = $commission->rate;

                    }
                    else
                    {

                        $taxrate = $commission->rate;
                        $price1 = $input['price'];
                        $price2 = $input['offer_price'];
                        $tax1 = $priceMinusTax = ($price1 * (($taxrate / 100)));
                        $tax2 = $priceMinusTax = ($price2 * (($taxrate / 100)));
                        $price = $input['price'] + $tax1;
                        $offer = $input['offer_price'] + $tax2;
                        $input['price'] = $price;
                        $input['offer_price'] = $offer;
                        if (!empty($tax2))
                        {
                            $input['commission_rate'] = $tax2;
                        }
                        else
                        {
                            $input['commission_rate'] = $tax1;
                        }
                    }
                }
                else
                {

                    $comm = Commission::where('category_id', $request->category_id)
                        ->first();
                    if (isset($comm))
                    {
                        if ($comm->type == 'f')
                        {

                            $price = $input['price'] + $comm->rate;
                            $offer = $input['offer_price'] + $comm->rate;
                            $input['price'] = $price;
                            $input['offer_price'] = $offer;
                            $input['commission_rate'] = $comm->rate;

                        }
                        else
                        {
                            $taxrate = $comm->rate;
                            $price1 = $input['price'];
                            $price2 = $input['offer_price'];
                            $tax1 = $priceMinusTax = ($price1 * (($taxrate / 100)));
                            $tax2 = $priceMinusTax = ($price2 * (($taxrate / 100)));
                            $price = $input['price'] + $tax1;
                            $offer = $input['offer_price'] + $tax2;
                            $input['price'] = $price;
                            $input['offer_price'] = $offer;

                            if (!empty($tax2))
                            {
                                $input['commission_rate'] = $tax2;
                            }
                            else
                            {
                                $input['commission_rate'] = $tax1;
                            }
                        }
                    }
                }

            }
        }

        if ($request->return_avbls == "1")
        {

            $request->validate(['return_avbls' => 'required', 'return_policy' => 'required'], ['return_policy.required' => 'Please choose return policy']);

            if ($request->return_policy === "Please choose an option")
            {
                return back()
                    ->with('warning', 'Please choose a return policy !');
            }

        }

        if ($request->return_avbls == "1")
        {

            $input['return_avbl'] = "1";
            $input['return_policy'] = $request->return_policy;
        }
        else
        {

            $input['return_avbl'] = 0;
            $input['return_policy'] = 0;
        }

        if (isset($request->free_shipping))
        {

            $input['free_shipping'] = "1";
            $input['shipping_id'] = NULL;

        }
        else
        {

            $sid = Shipping::where('default_status', "1")->first();
            $input['shipping_id'] = $sid->id;
            $input['free_shipping'] = '0';
        }

        $input['price_in'] = $currency_code;
        $input['vender_id'] = Auth::user()->id;
        $input['w_d'] = $request->w_d;
        $input['w_my'] = $request->w_my;
        $input['w_type'] = $request->w_type;

        $product->update($input);

        // return $product;
        return back()->with('updated', 'Product has been updated !');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Vender  $vender
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       
        $pro = Product::findOrFail($id);

            $provar = AddProductVariant::where('pro_id', $pro->id)->first();

            $subvar = AddSubVariant::where('pro_id', $pro->id)->get();

            DB::table('add_sub_variants')
                ->where('pro_id', $pro->id)->delete();

            if (isset($provar))
            {
                DB::table('add_product_variants')->where('pro_id', $pro->id)->delete();
            }

            foreach ($pro->reviews as $value)
            {
                $value->delete();
            }

            if (isset($subvar))
            {

                foreach ($subvar as $s)
                {

                    if ($s->variantimages[0]['image1'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image1']);
                    }

                    if ($s->variantimages[0]['image2'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image2']);
                    }

                    if ($s->variantimages[0]['image3'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image3']);
                    }

                    if ($s->variantimages[0]['image4'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image4']);
                    }

                    if ($s->variantimages[0]['image5'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image5']);
                    }

                    if ($s->variantimages[0]['image6'] != null)
                    {
                        unlink('../public/variantimages/' . $s->variantimages[0]['image6']);
                    }

                    DB::table('variant_images')
                        ->where('var_id', $s->id)
                        ->delete();

                }

            }

            $pro->delete();

            return back()->with('deleted','Product has been deleted !');
    }

    
}

