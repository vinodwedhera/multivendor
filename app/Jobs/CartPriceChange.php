<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Auth;
use App\Product;
use App\Cart;
use App\AddSubVariant;
use App\Commission;
use App\CommissionSetting;
use Session;

class CartPriceChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(Auth::check()){

    
            $carts = Cart::where('user_id',Auth::user()->id)->get();

            /*Cart Check*/
            if(count($carts)>0){
              foreach($carts as $citem){

                  $convert_price = 0;
                  $show_price = 0;
                  
                  $pro = Product::withTrashed()->find($citem->pro_id);

                  if($pro->trashed()){
                      Cart::where('pro_id',$pro->id)->where('user_id',Auth::user()->id)->delete();
                  }



                  $orivar = AddSubVariant::withTrashed()->findorfail($citem->variant_id);

                  if($orivar->trashed()){
                      Cart::where('variant_id',$citem->variant_id)->where('user_id',Auth::user()->id)->delete();
                  }

                    
                  $oriofferprice = $pro->offer_price+$orivar->price;
                  $oriprice      = $pro->price_total+$orivar->price;

                 
                  $commision_setting = CommissionSetting::first();
           
                                         if($commision_setting->type == "flat"){
           
                                            $commission_amount = $commision_setting->rate;
                                           if($commision_setting->p_type == 'f'){
                                           
                                             $totalprice = $pro->vender_price+$orivar->price+$commission_amount;
                                             $totalsaleprice = $pro->vender_offer_price + $orivar->price + $commission_amount;
           
                                              if($pro->vender_offer_price == 0){
                                                  $show_price = $totalprice;
                                               }else{
                                                  $totalsaleprice;
                                                
                                                 $convert_price = $totalsaleprice ==''?$totalprice:$totalsaleprice;
                                                 $show_price = $totalprice;
                                               }
           
                                              
                                           }else{
           
                                             $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;
           
                                             $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;
           
                                             $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);
           
                                             $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);
           
                                            
                                               if($pro->vender_offer_price ==0){
                                                 $show_price =  round($buyerprice,2);
                                               }else{
                                                  round($buyersaleprice,2);
                                                $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                 $show_price = $buyerprice;
                                               }
                                            
           
                                           }
                                         }else{
                                           
                                         $comm = Commission::where('category_id',$pro->category_id)->first();
                                         if(isset($comm)){
                                        if($comm->type=='f'){
                                          
                                          $price =  $pro->vender_price  + $comm->rate + $orivar->price;
           
                                           if($pro->vender_offer_price != null){
                                           $offer =  $pro->vender_offer_price  + $comm->rate + $orivar->price;
                                           }else{
                                             $offer =  $pro->vender_offer_price;
                                           }
           
                                           if($pro->vender_offer_price == 0 || $pro->vender_offer_price == null){
                                                 $show_price =  $price;
                                           }else{
                                            
                                             $convert_price = $offer;
                                             $show_price = $price;
                                           }
           
                                         
                                         
                                          
                                       }
                                       else{
           
                                         $commission_amount = $comm->rate;
           
                                             $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;
           
                                             $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;
           
                                             $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);
           
                                             $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);
           
                                            
                                               if($pro->vender_offer_price == 0){
                                                  $show_price = round($buyerprice,2);
                                               }else{
                                                  round($buyersaleprice,2);
                                                
                                                 $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                 $show_price = round($buyerprice,2);
                                               }
                                            
                                            
                                             
                                       }
                                    }else{
                                            $commission_amount = 0;

                                            $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;

                                            $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;

                                            $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);

                                            $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);

                                           
                                              if($pro->vender_offer_price == NULL){
                                                 $show_price = round($buyerprice,2);
                                              }else{
                                                $convert_price =  round($buyersaleprice,2);
                                                
                                                $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                $show_price = round($buyerprice,2);
                                              } 
                                    }
                                       }

                                        
                                       
                                        
                              if($pro->vender_offer_price !=NULL || $pro->vender_offer_price !='' || $pro->vender_offer_price != 0){

                                if($convert_price != $citem->ori_offer_price || $show_price != $citem->ori_price){
                                  
                                  Cart::where('pro_id','=',$pro->id)->where('id','=',$citem->id)->update(['semi_total' => $convert_price*$citem->qty, 'ori_offer_price' => $convert_price, 'price_total' => $show_price*$citem->qty, 'ori_price' => $show_price]);

                                }


                            }else{

                              if($pro->vender_offer_price == NULL || $pro->vender_offer_price == '' || $pro->vender_offer_price == 0 && $show_price != $citem->ori_price){
                                  
                                  Cart::where('pro_id','=',$pro->id)->where('id','=',$citem->id)->update(['semi_total' => '0', 'ori_offer_price' => '0', 'price_total' => $show_price*$citem->qty, 'ori_price' => $show_price]);

                                }
                            }
                            
                                   

              }
            }
            /*End*/
        }else{

          $cart = Session::get('cart');
          $convert_price = 0;
          $show_price = 0;
          if(!empty($cart)){
            foreach ($cart as $key=> $c) {

                  $pro = Product::withTrashed()->find($c['pro_id']);

                  

                    if(!$pro->trashed()){

                        $orivar = AddSubVariant::withTrashed()->findorfail($c['variantid']);
                        
                        if(!$orivar->trashed()){

                            $oriofferprice = $pro->offer_price+$orivar->price;
                                        $oriprice      = $pro->price_total+$orivar->price;

                                        $commision_setting = CommissionSetting::first();
           
                                         if($commision_setting->type == "flat"){
           
                                            $commission_amount = $commision_setting->rate;
                                           if($commision_setting->p_type == 'f'){
                                           
                                             $totalprice = $pro->vender_price+$orivar->price+$commission_amount;
                                             $totalsaleprice = $pro->vender_offer_price + $orivar->price + $commission_amount;
           
                                              if($pro->vender_offer_price == 0){
                                                  $show_price = $totalprice;
                                               }else{
                                                  $totalsaleprice;
                                                
                                                 $convert_price = $totalsaleprice ==''?$totalprice:$totalsaleprice;
                                                 $show_price = $totalprice;
                                               }
           
                                              
                                           }else{
           
                                             $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;
           
                                             $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;
           
                                             $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);
           
                                             $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);
           
                                            
                                               if($pro->vender_offer_price ==0){
                                                 $show_price =  round($buyerprice,2);
                                               }else{
                                                  round($buyersaleprice,2);
                                                $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                 $show_price = $buyerprice;
                                               }
                                            
           
                                           }
                                         }else{
                                           
                                         $comm = Commission::where('category_id',$pro->category_id)->first();
                                         if(isset($comm)){
                                        if($comm->type=='f'){
                                          
                                          $price =  $pro->vender_price  + $comm->rate + $orivar->price;
           
                                           if($pro->vender_offer_price != null){
                                           $offer =  $pro->vender_offer_price  + $comm->rate + $orivar->price;
                                           }else{
                                             $offer =  $pro->vender_offer_price;
                                           }
           
                                           if($pro->vender_offer_price == 0 || $pro->vender_offer_price == null){
                                                 $show_price =  $price;
                                           }else{
                                            
                                             $convert_price = $offer;
                                             $show_price = $price;
                                           }
           
                                         
                                         
                                          
                                       }
                                       else{
           
                                             $commission_amount = $comm->rate;
           
                                             $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;
           
                                             $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;
           
                                             $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);
           
                                             $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);
           
                                            
                                               if($pro->vender_offer_price == 0){
                                                  $show_price = round($buyerprice,2);
                                               }else{
                                                  round($buyersaleprice,2);
                                                
                                                 $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                 $show_price = round($buyerprice,2);
                                               }
                                            
                                            
                                             
                                       }
                                    }else{

                                             $commission_amount = 0;
           
                                             $totalprice = ($pro->vender_price+$orivar->price)*$commission_amount;
           
                                             $totalsaleprice = ($pro->vender_offer_price+$orivar->price)*$commission_amount;
           
                                             $buyerprice = ($pro->vender_price+$orivar->price)+($totalprice/100);
           
                                             $buyersaleprice = ($pro->vender_offer_price+$orivar->price)+($totalsaleprice/100);
           
                                            
                                               if($pro->vender_offer_price == 0){
                                                  $show_price = round($buyerprice,2);
                                               }else{
                                                  round($buyersaleprice,2);
                                                
                                                 $convert_price = $buyersaleprice==''?$buyerprice:$buyersaleprice;
                                                 $show_price = round($buyerprice,2);
                                               }

                                    }
                                       }


                                if($pro->vender_offer_price !='' || $pro->vender_offer_price != 0){
                                    if($c['pro_id'] == $pro->id){
                                      
                                    if($convert_price != $c['varofferprice'] || $show_price != $c['varprice'])
                                      {
                                        $cart[$key]['varprice'] = $show_price;
                                        $cart[$key]['varofferprice'] = $convert_price;
                                      }

                                  }
                                    

                                }else{
                                 
                                    if($c['pro_id'] == $pro->id){

                                        if($pro->vender_offer_price =='' || $pro->vender_offer_price == 0 && $show_price != $c['varprice'] || $pro->vender_offer_price == 0){
                                         
                                          $cart[$key]['varofferprice'] = 0;
                                          $cart[$key]['varprice'] = $show_price;

                                        }
                                    }
                                  }

                        }else{
                          unset($cart[$key]);
                        }
                                        

                    }else{

                      unset($cart[$key]);

                    }

                  
            }

            Session::put('cart',$cart);
          }
        }
        
        
    }
}
