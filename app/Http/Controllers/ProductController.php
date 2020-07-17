<?php
namespace App\Http\Controllers;

use App\Product;
use App\AddProductVariant;
use App\AddSubVariant;
use App\Category;
use App\Subcategory;
use App\Grandcategory;
use App\Store;
use App\User;
use App\Brand;
use App\FaqProduct;
use App\RealatedProduct;
use App\Related_setting;
use Excel;
use DB;
use Image;
use Session;
use App\UserReview;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CommissionSetting;
use App\Commission;
use App\Shipping;
use Auth;
use App\Genral;
use App\ProductSpecifications;
use App\TaxClass;
use App\admin_return_product;
use Rap2hpoutre\FastExcel\FastExcel;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function allvariants($id)
    {
        $pro = Product::findOrFail($id);
        return view('admin.product.allvar', compact('pro'));
    }

    public function storeSpecs(Request $request, $id)
    {

        $product = Product::find($id);

        if (isset($product))
        {
            foreach ($request->prokeys as $key => $value)
            {

                $newspec = new ProductSpecifications;
                $newspec->pro_id = $product->id;
                $newspec->prokeys = $value;
                $newspec->provalues = $request->provalues[$key];
                $newspec->save();
            }
        }

        return back()
            ->with('added', 'Product Specification created !');

    }

    public function deleteSpecs(Request $request, $id)
    {

        $validator = Validator::make($request->all() , ['checked' => 'required', ]);

        if ($validator->fails())
        {

            return back()
                ->with('warning', 'Please select one of them to delete');
        }

        foreach ($request->checked as $key => $check)
        {
            $specs = ProductSpecifications::find($check);

            if (isset($specs))
            {

                $specs->delete();

            }
        }

        return back()
            ->with('deleted', 'Selected Specification Deleted');

    }

    public function updateSpecs(Request $request, $id)
    {
        $spec = ProductSpecifications::findOrFail($id);

        $spec->prokeys = $request->pro_key;
        $spec->provalues = $request->pro_val;

        $spec->save();

        return back()
            ->with('updated', 'Specification has been Updated');
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

    public function allreviews($id){

          require_once('price.php');

          $product = Product::find($id);

          $allreviews = UserReview::orderBy('id','DESC')->where('status','=','1')->where('pro_id',$id)->paginate(10);

          $reviewcount = UserReview::where('pro_id', $id)->where('status', "1")->WhereNotNull('review')->count();

          $mainproreviews = UserReview::orderBy('id','DESC')->where('status','=','1')->where('pro_id',$id)->get();
          $review_t = 0;
          $price_t = 0;
          $value_t = 0;
          $sub_total = 0;
          $count = count($mainproreviews);
          

          foreach ($mainproreviews as $review) {
              $review_t = $review->qty * 5;
              $price_t = $review->price * 5;
              $value_t = $review->value * 5;
              $sub_total = $sub_total + $review_t + $price_t + $value_t;
          }

          $count = ($count * 3) * 5;

          if(!isset($overallrating)){
                $overallrating = 0;
                $ratings_var = 0;
          }

          if ($count != "") {
            $rat = $sub_total / $count;

            $ratings_var = ($rat * 100) / 5;

            $overallrating = ($ratings_var / 2) / 10;
          }



        $overallrating = round($overallrating,1);



        $qualityprogress = 0;
        $quality = 0;
        $tq = 0;

        $priceprogress = 0;
        $price = 0;
        $tp = 0;

        $valueprogress = 0;
        $value= 0;
        $vp = 0;

        if(!empty($mainproreviews[0])){

            $count = count($mainproreviews);

            foreach ($mainproreviews as $key => $r) {
                $quality = $tq+$r->qty*5;
            }

            $countq = ($count*1)*5;
            $ratq = $quality/$countq;
            $qualityprogress = ($ratq*100)/5;

            foreach ($mainproreviews as $key => $r) {
                $price = $tp+$r->price*5;
            }

            $countp = ($count*1)*5;
            $ratp = $price/$countp;
            $priceprogress = ($ratp*100)/5;

            foreach ($mainproreviews as $key => $r) {
                $value = $vp+$r->value*5;
            }

            $countv = ($count*1)*5;
            $ratv = $value/$countv;
            $valueprogress = ($ratv*100)/5;

        }
        
        if(isset($product)){
            return view('front.allreviews',compact('conversion_rate','product','ratings_var','allreviews','overallrating','mainproreviews','qualityprogress','priceprogress','valueprogress','reviewcount'));
        }else{
            notify()->error('404 Product not found !');
            return back();
        }
        

    }

    

    public function importPage()
    {
        return view('admin.product.importindex');
    }

    public function import(Request $request)
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
                        $catid = new Category;
                        $catid->title = $line['category_name'];
                        $catid->status = '1';
                        $catid->featured = '1';
                        $catid->position = (Category::count()+1);
                        $catid->save();
                    }

                    $subcatid = Subcategory::where('title->'.Session::get('changed_language'), $line['subcategory_name'])->first();

                    if (!isset($subcatid))
                    {

                        $subcatid = new Subcategory;
                        $subcatid->title = $line['subcategory_name'];
                        $subcatid->status = '1';
                        $subcatid->position = (Subcategory::count()+1);
                        $subcatid->featured = '0';
                        $subcatid->parent_cat = $catid->id;
                        $subcatid->save();
                    }

                    $brandnid = Brand::where('name', $line['brand_name'])->first();

                    if (!isset($brandnid))
                    {

                        $brandnid = new Brand;
                        $brandnid->name = $line['brand_name'];
                        $brandnid->status = '1';
                        $brandnid->show_image = '1';
                        $brandnid->is_requested = '0';
                        $brandnid->save();
                        
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

    public function index()
    {

        $products = Product::orderBy('id', 'desc')->get();
        return view("admin.product.index", compact("products"));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function upload_info(Request $request)
    {

        $id = $request['catId'];

        $category = Category::findOrFail($id);
        $upload = $category
            ->subcategory
            ->where('parent_cat', $id)->pluck('title', 'id')
            ->all();

        return response()
            ->json($upload);
    }

    public function gcato(Request $request)
    {

        $id = $request['catId'];

        $category = Subcategory::findOrFail($id);

        $upload = $category
            ->childcategory
            ->where('subcat_id', $category->id)
            ->pluck('title', 'id')
            ->all();

        return response()
            ->json($upload);
    }

    public function create()
    {
        $categorys = Category::all();
        $brands = Brand::all();
        $store = Store::where('user_id', Auth::user()->id)
            ->where('status', "1")
            ->first();
        $product = Product::all();
        return view("admin.product.create", compact("categorys", "store", "brands", "product"));
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

                    if(!isset($request->tax_r)){


                            $price = $input['price'] + $commission->rate;
                            $offer = $input['offer_price'] + $commission->rate;

                            $input['price'] = $price;
                            $input['offer_price'] = $offer;
                            $input['commission_rate'] = $commission->rate;

                        }else{
                            
                            $cit =$commission->rate*$input['tax_r']/100;
                            $price = $input['price'] + $commission->rate+$cit;
                            $offer = $input['offer_price'] + $commission->rate+$cit;

                            $input['price'] = $price;
                            $input['offer_price'] = $offer;
                            $input['commission_rate'] = $commission->rate+$cit;
                        }

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

                        if(!isset($request->tax_manual)){

                                $price = $input['price'] + $comm->rate;
                                $offer = $input['offer_price'] + $comm->rate;
                                $input['price'] = $price;
                                $input['offer_price'] = $offer;
                                $input['commission_rate'] = $comm->rate;

                            }else{

                                $cit =$commission->rate*$input['tax_r']/100;
                                $price = $input['price'] + $comm->rate + $cit;
                                $offer = $input['offer_price'] + $comm->rate + $cit;
                                $input['price'] = $price;
                                $input['offer_price'] = $offer;
                                $input['commission_rate'] = $comm->rate + $cit;
                            }

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
        $input['key_features'] = clean($request->key_features);
        $input['des'] = clean($request->des);

        $data = Product::create($input);

        $data->save();

        $relsetting  = new Related_setting;

        $relsetting->pro_id = $data->id;
        $relsetting->status = '0';
        $relsetting->save();

        return redirect()->route('add.var',$data->id)->with('success','Product created !  create a variant now ');
        
    }

    public function addSale(Request $request)
    {
        $salePrice = $request->salePrice;
        $pro_id = $request->pro_id;
        DB::table('products')
            ->where('id', $pro_id)->update(['offer_price' => $salePrice]);
        echo "Added success";
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
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
        $grand = Grandcategory::where('subcat_id', $cat_id->child)
            ->get();

        return view("admin.product.edit_tab", compact('rel_setting', "products", "categorys", "store", "brands", "faqs", "child", "grand", "realateds"));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    

         $products = Product::findOrFail($id);
        $currency_code = Genral::first()->currency_code;
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
            $input['tax'] = $request->tax;
        }

            
            
            $input['vender_price'] = $request->price;
            $input['vender_offer_price'] = $request->offer_price;
            
            $commissions = CommissionSetting::all();
            foreach ($commissions as $commission)
            {
                if ($commission->type == "flat")
                {
                    if ($commission->p_type == "f")
                    {

                        if(!isset($request->tax_r)){


                            $price = $input['price'] + $commission->rate;
                            $offer = $input['offer_price'] + $commission->rate;

                            $input['price'] = $price;
                            $input['offer_price'] = $offer;
                            $input['commission_rate'] = $commission->rate;

                        }else{
                            
                            $cit =$commission->rate*$input['tax_r']/100;
                            $price = $input['price'] + $commission->rate+$cit;
                            $offer = $input['offer_price'] + $commission->rate+$cit;

                            $input['price'] = $price;
                            $input['offer_price'] = $offer;
                            $input['commission_rate'] = $commission->rate+$cit;
                        }

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
                           
                            if(!isset($request->tax_manual)){

                                $price = $input['price'] + $comm->rate;
                                $offer = $input['offer_price'] + $comm->rate;
                                $input['price'] = $price;
                                $input['offer_price'] = $offer;
                                $input['commission_rate'] = $comm->rate;

                            }else{

                                $cit =$commission->rate*$input['tax_r']/100;
                                $price = $input['price'] + $comm->rate + $cit;

                                if($request->offer_price){
                                    $offer = $input['offer_price'] + $comm->rate + $cit;
                                    $input['offer_price'] = $offer;
                                }else{
                                    $input['offer_price'] = NULL;
                                }
                                
                                $input['price'] = $price;
                                
                                $input['commission_rate'] = $comm->rate + $cit;
                            }

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
        $input['w_d'] = $request->w_d;
        $input['w_my'] = $request->w_my;
        $input['w_type'] = $request->w_type;
        $input['key_features'] = clean($request->key_features);
        $input['des'] = clean($request->des);

        $product->update($input);
        return back()->with('updated', 'Product has been updated !');

    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product->image != null)
        {

            $image_file = @file_get_contents(public_path() . '/images/product/' . $product->image);

            if ($image_file)
            {

                unlink(public_path() . '/images/product/' . $product->image);
            }
        }

        if(isset($value->subvariants)){
            
            foreach ($$value->subvariants as $variant) {
                $variant->delete();
            }
            
        }

        $trash = $product->delete();

        if ($trash)
        {
            session()->flash("deleted", "Product Has Been Deleted");
            return redirect("admin/products");
        }
    }

    
    public function prorelsetting(Request $request, $id)
    {
        $relsetting = Related_setting::where('pro_id', $id)->first();

        if (!isset($relsetting))
        {

            $relsetting = new Related_setting();
            $relsetting->pro_id = $id;
            $relsetting->status = $request->status;
            $relsetting->save();

            return 'success';

        }
        else
        {

            $relsetting->status = $request->status;

            $relsetting->save();

            return 'success';

        }

    }

    public function relatedProductStore(Request $request, $id)
    {
        $input = $request->all();
        $data = RealatedProduct::where('product_id', '=', $id)->first();

        $request->validate(['related_pro' => 'required'], ['related_pro.required' => 'Please select a product !']);

        if (!isset($data))
        {
            $newR = new RealatedProduct();
            $input['product_id'] = $id;
            $newR->create($input);

            return back()->with('added', 'Related Product Added !');

        }
        else
        {
            $input['product_id'] = $id;
            $data->update($input);
            return back()->with('updated', 'Related Product Updated !');

        }
    }

}

