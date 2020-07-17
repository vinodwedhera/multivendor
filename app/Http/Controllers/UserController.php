<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Country;
use Image;
use App\Store;
use App\Allcountry;
use App\Allstate;
use App\Allcity;
use DataTables;
use Avatar;

class UserController extends Controller
{
    

    public function __construct(){
        $this->middleware('is_admin');
    }

    public function index(Request $request)
    {   
      
        $users = \DB::table('users')->where('role_id','!=','a')->get();

        if($request->ajax()){
            
            return DataTables::of($users)
                            ->addIndexColumn()
                            ->addColumn('image', function($user){
                                if($user->image !=''){
                                    $image = '<img src="'.url("images/user/".$user->image).'" height="100" width="100"/>';
                                }else{
                                    $image = '<img title='.$user->name.'" src="'.Avatar::create($user->name)->toBase64().'" />';
                                }

                                return $image;
                            })
                            ->addColumn('detail',function($user){
                                
                                if($user->role_id == 'u'){
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>User</p>';
                                }else{
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>Seller</p>';
                                }

                                return $detail;
                            })->addColumn('timestamp',function($user){
                                $time = '<p> <i class="fa fa-calendar-plus-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('M jS Y',strtotime($user->created_at)).',</span></p>
                                <p ><i class="fa fa-clock-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('h:i A',strtotime($user->created_at)).'</span></p>
                            
                                <p class="custom-border"></p>
                            
                                <p>
                                   <i class="fa fa-calendar-check-o" aria-hidden="true"></i> <span class="font-weight">'.date('M jS Y',strtotime($user->updated_at)).'</span>
                                </p>
                           
                                <p><i class="fa fa-clock-o" aria-hidden="true"></i> <span class="font-weight">'.date('h:i A',strtotime($user->updated_at)).'</span></p>';

                                return $time;
                            })
                            ->editColumn('status','admin.user.status')
                            ->editColumn('action','admin.user.action')
                            ->rawColumns(['image','detail','timestamp','status','action'])
                            ->make(true);
        }
    	return view("admin.user.show",compact('users'));
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function create()
    {
        $country = Country::all();
        return view("admin.user.add_user",compact("country"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
             'password' => 'required|string|min:6',
             'image' => 'mimes:jpeg,jpg,png,bmp,gif'
        ]);

		
        $input = $request->all();

        $u = new User;
        
        if ($file = $request->file('image')) 
         {
            
          $optimizeImage = Image::make($file);
          $optimizePath = public_path().'/images/user/';
          $image = time().$file->getClientOriginalName();
          $optimizeImage->save($optimizePath.$image, 72);

          $input['image'] = $image;
          
          $name = Hash::make($request->password);
          $input['password'] = $name;

          
        }

        $u->create($input);
        

        return back()->with("added","User Has Been Added");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
       //echo "<pre>"; print_r($cat);
        $country = Country::all();
        $citys = Allcity::all();
        $states = Allstate::where('country_id',$user->country_id)->get();
        $citys = Allcity::where('state_id',$user->state_id)->get();
        return view("admin.user.edit",compact("user","country","states","citys"));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        

        $data = $this->validate($request,[
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'image' => 'mimes:jpeg,jpg,png,bmp,gif'
        ]);
        

         $user = User::findOrFail($id);
         
         $input = $request->all();  
        
         if(isset($request->is_pass_change)) {
           $this->validate($request,[
           'password' => 'required|between:6,255|confirmed',
            'password_confirmation' => 'required',
        ]);
            $newpass = Hash::make($request->password);
            $input['password'] = $newpass;
            
         }else{
            $input['password'] = $user->password;
         }
         

        if($file = $request->file('image'))
        {
           $userimage = @file_get_contents('/images/user/'.$user->image);

            if ($userimage) 
            {
                 unlink('/images/user/'.$user->image);
                
            }

                    $optimizeImage = Image::make($request->file('image'));
                    $optimizePath = public_path().'/images/user/';
                    $name = time().$file->getClientOriginalName();
                    $optimizeImage->save($optimizePath.$name, 72);
                    $input['image'] = $name;

        }

        try {
              
             $user->update($input);
        
        } catch(\Illuminate\Database\QueryException $e){
            $errorCode = $e->errorInfo[1];
            if($errorCode == '1062'){
                return back()->with("warning","Email Alerdy Exists");
            }
        }
            
    


        
           

        return redirect('admin/users')->with('updated', 'User has been updated');

    }
        
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if ($user->image != null)
        {
                
            $image_file = @file_get_contents(public_path().'/images/user/'.$user->image);

                if($image_file)
                {
                    
                    unlink(public_path().'/images/user/'.$user->image);
                }
        }
        $value = $user->delete();
         if($value){
            session()->flash("deleted","User Has Been Deleted");
            return redirect("admin/users");
         }
    }

    public function only_vender(Request $request){

      
        $u = User::query();
        $users = $u->where('role_id','!=','a')->where('role_id','!=','u')->get();

        if($request->ajax()){
            
            return Datatables::of($users)
                            ->addIndexColumn()
                            ->addColumn('image', function($user){
                                if($user->image !=''){
                                    $image = '<img src="'.url("images/user/".$user->image).'" height="100" width="100"/>';
                                }else{
                                    $image = '<img title='.$user->name.'" src="'.Avatar::create($user->name)->toBase64().'" />';
                                }

                                return $image;
                            })
                            ->addColumn('detail',function($user){
                                
                                if($user->role_id == 'u'){
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>User</p>';
                                }else{
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>Seller</p>';
                                }

                                return $detail;
                            })->addColumn('timestamp',function($user){
                                $time = '<p> <i class="fa fa-calendar-plus-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('M jS Y',strtotime($user->created_at)).',</span></p>
                                <p ><i class="fa fa-clock-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('h:i A',strtotime($user->created_at)).'</span></p>
                            
                                <p class="custom-border "></p>
                            
                                <p>
                                   <i class="fa fa-calendar-check-o" aria-hidden="true"></i> <span class="font-weight">'.date('M jS Y',strtotime($user->updated_at)).'</span>
                                </p>
                           
                                <p><i class="fa fa-clock-o" aria-hidden="true"></i> <span class="font-weight">'.date('h:i A',strtotime($user->updated_at)).'</span></p>';

                                return $time;
                            })
                            ->editColumn('status','admin.user.status')
                            ->editColumn('action','admin.user.action')
                            ->rawColumns(['image','detail','timestamp','status','action'])
                            ->make(true);
        }

        return view("admin.user.show",compact("users"));
    }
     public function only_user(Request $request){

        $u = User::query();
        $users = $u->where('role_id','=','u')->get();

        if($request->ajax()){
            
            return Datatables::of($users)
                            ->addIndexColumn()
                            ->addColumn('image', function($user){
                                if($user->image !=''){
                                    $image = '<img src="'.url("images/user/".$user->image).'" height="100" width="100"/>';
                                }else{
                                    $image = '<img title='.$user->name.'" src="'.Avatar::create($user->name)->toBase64().'" />';
                                }

                                return $image;
                            })
                            ->addColumn('detail',function($user){
                                
                                if($user->role_id == 'u'){
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>User</p>';
                                }else{
                                    $detail = '<h4>'.$user->name.'</h4><p><b>Email:</b> '.$user->email.'</p>
                                    <p><b>Mobile:</b> '.$user->mobile.'</p>
                                    <p><b>User Role:</b>Seller</p>';
                                }

                                return $detail;
                            })->addColumn('timestamp',function($user){
                                $time = '<p> <i class="fa fa-calendar-plus-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('M jS Y',strtotime($user->created_at)).',</span></p>
                                <p ><i class="fa fa-clock-o" aria-hidden="true"></i> 
                                <span class="font-weight">'.date('h:i A',strtotime($user->created_at)).'</span></p>
                            
                                <p class="custom-border"></p>
                            
                                <p>
                                   <i class="fa fa-calendar-check-o" aria-hidden="true"></i> <span class="font-weight">'.date('M jS Y',strtotime($user->updated_at)).'</span>
                                </p>
                           
                                <p><i class="fa fa-clock-o" aria-hidden="true"></i> <span class="font-weight">'.date('h:i A',strtotime($user->updated_at)).'</span></p>';

                                return $time;
                            })
                            ->editColumn('status','admin.user.status')
                            ->editColumn('action','admin.user.action')
                            ->rawColumns(['image','detail','timestamp','status','action'])
                            ->make(true);
        }
        return view("admin.user.show",compact("users"));
    }



    public function appliedform(){
       $stores = Store::where('apply_vender', '=','0')->get();
        return view("admin.user.appliyed_vender",compact("stores"));
    }


   public function choose_country(Request $request) 
    {
       
      $id = $request['catId'];

       $country = Allcountry::findOrFail($id);
      $upload = Allstate::where('country_id',$id)->pluck('name','id')->all();

      return response()->json($upload);
    }

    public function choose_city(Request $request) 
    {
        
      $id = $request['catId'];

      $state = Allstate::findOrFail($id);
      $upload = Allcity::where('state_id',$id)->pluck('name','id')->all();

      return response()->json($upload);
    }
    
}
