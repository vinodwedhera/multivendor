<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Actor;
use App\AudioLanguage;
use App\Director;
use App\Genre;
use App\HomeSlider;
use App\LandingPage;
use App\Menu;
use App\Movie;
use App\Package;
use App\Season;
use App\TvSeries;
use App\Plan;
use Closure;
use Illuminate\Support\Carbon;
use Stripe\Customer;
use Stripe\Stripe;
use App\Faq;
use App\Wishlist;
use App\FooterTranslation;
use DB;
use Reminder;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
class MainController extends Controller
{
 
     use SendsPasswordResetEmails;


   public function menu(){

    $auth = Auth::user();
    $menu = Menu::all()->toArray();
    return response()->json(array('auth' =>$auth,'menu'=>$menu), 200);            
       
    }
public function movie(){

    $auth = Auth::user();
    $movie = Movie::all()->toArray();
    return response()->json(array('auth' =>$auth,'movie'=>$movie), 200);            
       
    }
    public function tvseries(){

    $auth = Auth::user();
    $tvseries = TvSeries::all()->toArray();
    return response()->json(array('auth' =>$auth,'tvseries'=>$tvseries), 200);            
       
    }
    public function index(){

    $auth = Auth::user();
    $plans = Package::all()->toArray();
    $blocks = LandingPage::orderBy('position', 'asc')->get()->toArray();
    $menu = Menu::all()->toArray();
    $movie = Movie::all()->toArray();
    $tvseries = TvSeries::all()->toArray();
    $season = Season::all()->toArray();
    $slider = HomeSlider::orderBy('position', 'asc')->get()->toArray();
    $actor = Actor::all()->toArray();
    $director = Director::all()->toArray();
    $genre = Genre::all()->toArray();

    if ($auth->stripe_id != null) {
      //Set your secret key: remember to change this to your live secret key in production
      Stripe::setApiKey(env('STRIPE_SECRET'));
      $plans = Package::all();
      foreach ($plans as $plan) {
        if ($auth->subscribed($plan->plan_id) || $auth->is_admin == 1) {          
          return response()->json(array('auth' =>$auth,'menu'=>$menu,'movie'=>$movie,'tvseries'=>$tvseries,'season'=>$season,'slider'=>$slider,'actor'=>$actor,'director'=>$director,'genre'=>$genre), 200);            
        } else {
          return response()->json(array('auth' =>$auth,'plans'=>$plans,'blocks'=>$blocks), 200);
        }
      }
    }
    else if (isset($auth->paypal_subscriptions) && count($auth->paypal_subscriptions) > 0) {          
      //Check Paypal Subscription of user
      $last_payment = $auth->paypal_subscriptions->last();
      if (isset($last_payment) && $last_payment->status == 1) {
        //check last date to current date
        $current_date = Carbon::now();
        if (date($current_date) <= date($last_payment->subscription_to)) {
          return response()->json(array('auth' =>$auth,'menu'=>$menu,'movie'=>$movie,'tvseries'=>$tvseries,'season'=>$season,'slider'=>$slider,'actor'=>$actor,'director'=>$director,'genre'=>$genre), 200);
        } else {
          $last_payment->status = 0;
          $last_payment->save();
          return response()->json(array('auth' =>$auth,'plans'=>$plans,'blocks'=>$blocks), 200);
        }
      }
      return response()->json(array('auth' =>$auth,'plans'=>$plans,'blocks'=>$blocks), 200);
    } else {
      return response()->json(array('auth' =>$auth,'plans'=>$plans,'blocks'=>$blocks), 200);
    }
	}

  public function faq(){

    $auth = Auth::user();
    $faq = Faq::all()->toArray();
      return response()->json(array('auth' =>$auth,'faq'=>$faq), 200);
    }

    public function landingPage(){

    $auth = Auth::user();
    $landingPage = landingPage::all()->toArray();
      return response()->json(array('auth' =>$auth,'landingPage'=>$landingPage), 200);
    }

    public function userProfile(){

    $auth = Auth::user();
    $user = User::all()->toArray();
      return response()->json(array('auth' =>$auth,'user'=>$user), 200);
    }

    public function package(){

    $auth = Auth::user();
    $package = Package::all()->toArray();
      return response()->json(array('auth' =>$auth,'package'=>$package), 200);
    }

    public function plan(){

    $auth = Auth::user();
    $plan = Plan::all()->toArray();
      return response()->json(array('auth' =>$auth,'plan'=>$plan), 200);
    }

    public function wishlist(){

    $auth = Auth::user();
    $wishlist = Wishlist::all()->toArray();
      return response()->json(array('auth' =>$auth,'wishlist'=>$wishlist), 200);
    }

    public function slider(){

    $auth = Auth::user();
    $slider = HomeSlider::all()->toArray();
      return response()->json(array('auth' =>$auth,'slider'=>$slider), 200);
    }

    public function footer_details(){

    $auth = Auth::user();
    $footer = FooterTranslation::all()->toArray();
      return response()->json(array('auth' =>$auth,'footer'=>$footer), 200);
    }

    public function RecentMovies(){

    $auth = Auth::user();
    $recent = Movie::orderBy('id', 'DESC')->take(30)->get()->toArray();
      return response()->json(array('auth' =>$auth,'recent'=>$recent), 200);
    }

    public function Recenttvseries(){

    $auth = Auth::user();
    $tvseries = TvSeries::orderBy('id', 'DESC')->take(30)->get()->toArray();
    return response()->json(array('auth' =>$auth,'tvseries'=>$tvseries), 200);            
       
    }

    public function MovieByCategory($id){

    $auth = Auth::user();
    $movie = DB::table('movies')
        ->Leftjoin('menu_videos','menu_videos.menu_id','menu_videos.movie_id')->where('menu_videos.menu_id',$id)->get();

    $tvseries = DB::table('tv_series')
        ->Leftjoin('menu_videos','menu_videos.menu_id','menu_videos.tv_series_id')->where('menu_videos.menu_id',$id)->get();

    $movieCount = count($movie);
    $tvCount = count($tvseries);

      if($tvCount == 0 && $movieCount == 0){

      $movieTvSeries = array("No Data Found"); 
      return response()->json(array('auth' =>$auth,'data'=>$movieTvSeries), 200);  
      }
      else{
        if($movieCount == 0){

           $movieTvSeries = array($tvseries); 
           return response()->json(array('auth' =>$auth,'data'=>$movieTvSeries), 200);

        }else{
          if($tvCount == 0){
           $movieTvSeries = array($movie); 
           return response()->json(array('auth' =>$auth,'data'=>$movieTvSeries), 200);
         }else{
          
           $movieTvSeries = array_merge(array($tvseries,$movie));  
           return response()->json(array('auth' =>$auth,'data'=>$movieTvSeries), 200);
        }
      }
    }
               
  }

   /* public function TvSeriesByCategory(){

    $auth = Auth::user();
    $TvSeries = DB::table('tv_series')
        ->Leftjoin('menu_videos','menu_videos.menu_id','menu_videos.tv_series_id')->get();
    return response()->json(array('auth' =>$auth,'TvSeries'=>$TvSeries), 200);            
       
    }*/




}
