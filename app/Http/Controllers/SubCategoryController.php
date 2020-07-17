<?php

namespace App\Http\Controllers;

use App\Subcategory;
use App\Category;
use Illuminate\Http\Request;
use Image;
use Storage;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */ 
    public function index()
    {
        $subcategory = Subcategory::orderBy('position','ASC')->get();

        return view('admin.category.subcategory.index',compact("subcategory"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parent = Category::all();

        return view("admin.category.subcategory.create",compact("parent"));
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
            
            "title"=>"required",
            

        ],[

            "title.required"=>"Subcategory Name is needed",
            
          ]);

        $data  = new Subcategory;
        $input = $request->all();

        if(isset($request->status)){
           $input['status'] = "1";
        }else{
            $input['status'] = "0";
        }

        
        if ($file = $request->file('image')) 
         {
            
          $optimizeImage = Image::make($file);
          $optimizePath = public_path().'/images/subcategory/';
          $image = time().$file->getClientOriginalName();
          $optimizeImage->save($optimizePath.$image, 72);

          $input['image'] = $image;

           
         }

         $input['position'] = (Subcategory::count()+1);

         $input['description'] = clean($request->description);

         $data->create($input);
        
        return redirect()->route('subcategory.index')->with("added","Sub Category Has Been Added");
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Subcategory  $subcategory
     * @return \Illuminate\Http\Response
     */
    public function reposition(Request $request)
    {
        $data= $request->all();
        $posts = Subcategory::all();
        $pos = $data['id'];
        $position =json_encode($data);
        foreach ($posts as $key => $item) {
            Subcategory::where('id', $item->id)->update(array('position' => $pos[$key]));
        }
        
        return response()->json(['msg'=>'Sorted Successfully', 'success'=>true]);
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Subcategory  $subcategory
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $parent = Category::all();

        $cat = Subcategory::findOrFail($id);
        return view("admin.category.subcategory.edit",compact("cat","parent"));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Subcategory  $subcategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $subcat = Subcategory::findOrFail($id);

        $request->validate([
            'title' => 'required',
            'parent_cat' => 'required'
        ],[
            'parent_cat.required' => 'Please Select Parent Category'
        ]);

        $subcat->title = $request->title;
        $subcat->parent_cat = $request->parent_cat;
        $subcat->description = clean($request->description);
        $subcat->icon = $request->icon;
            
        if(isset($request->featured)){
            $subcat->featured = 1;
        }else{
            $subcat->featured = 0;
        }

        if(isset($request->status)){
            $subcat->status = 1;
        }else{
            $subcat->status = 0;
        }

      if ($request->hasFile('image')){
               $image = $request->file('image');
               $filename = time() . '.' . $image->getClientOriginalExtension();
               $location = public_path('images/subcategory/' . $filename);
               Image::make($image)->resize(350,600)->save($location);
               $oldFileName = $subcat->image;//old file name
               $subcat->image = $filename;//update the new photo
               Storage::delete($oldFileName);
        }

        $subcat->save();

        return redirect()->route('subcategory.index')->with("updated","Subcategory Has Been Updated !");

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Subcategory  $subcategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Subcategory::find($id);


        if(count($category->products)>0){
            return back()->with('warning','Subcategory cant be deleted as its linked to products !');
        }

        if ($category->image != null)
        {
                
            $image_file = @file_get_contents(public_path().'/images/subcategory/'.$category->image);

                if($image_file)
                {
                    
                    unlink(public_path().'/images/subcategory/'.$category->image);
                }
        }
        $value = $category->delete();
        if($value){
            session()->flash("deleted","Category Has Been Deleted !");
            return redirect("admin/subcategory");
         }
    }
}
