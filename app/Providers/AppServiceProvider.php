<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Genral;
use App\Seo;
use Auth;
use App\User;
use App\Footer;
use App\Config;
use App\AutoDetectGeo;
use App\UserReview;
use App\DashboardSetting;
use App\multiCurrency;
use App\CommissionSetting;
use Calebporzio\Onboard\OnboardFacade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        

        Schema::defaultStringLength(191);

  
        view()->composer('*',function($view){

          if(\DB::connection()->getDatabaseName()){
            if(Schema::hasTable('genrals') && Schema::hasTable('seos')){
              $defCurrency = multiCurrency::where('default_currency','=',1)->first();
              $rightclick = Genral::findOrFail(1)->right_click;
              $guest_login = Genral::findOrFail(1)->guest_login;
              $inspect = Genral::findOrFail(1)->inspect;
              $title = Seo::findOrFail(1)->project_name;
              $fevicon = Genral::findOrFail(1)->fevicon;
              $Copyright = Genral::findOrFail(1)->copyright;
              $front_logo = Genral::findOrFail(1)->logo;
              $price_login = Genral::findOrFail(1)->login;
              $genrals_settings = Genral::findOrFail(1);
              $config = Config::findOrFail(1);
              $Api_settings = Config::findOrFail(1);
              $cur_setting = AutoDetectGeo::first()->enabel_multicurrency;
              $cms = CommissionSetting::first();
              $pincodesystem = Config::findOrFail(1)->pincode_system;
              $dashsetting = DashboardSetting::first();
              $auth = Auth::user();
              if($auth){
              $Uimage = User::where('id',$auth->id)->first();
               }
               else{
                  $Uimage = User::where('id','1')->first();
               }
              $footer3_widget = Footer::first();
              
              $view->with(['cms' => $cms, 'defCurrency' => $defCurrency, 'dashsetting' => $dashsetting,'pincodesystem' => $pincodesystem,'cur_setting' => $cur_setting, 'config' => $config, 'rightclick' => $rightclick,'inspect'=>$inspect,
                  'title'=>$title,'fevicon'=>$fevicon,'auth'=>$auth,
                  'Uimage'=>$Uimage,'price_login'=>$price_login, 
                  'guest_login'=>$guest_login,'Copyright'=>$Copyright,
                  'footer3_widget'=>$footer3_widget,'front_logo'=>$front_logo,
                  'genrals_settings'=>$genrals_settings,'Api_settings'=>$Api_settings,
              ]);
            }  
          }
            

        });    

        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //Schema::defaultStringLength(191);
    }
}
