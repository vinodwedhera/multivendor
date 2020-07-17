<?php
namespace App\Http\Controllers;

use App\Grandcategory;
use Illuminate\Http\Request;
use App\Category;
use App\Subcategory;
use Image;

class GrandcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cats = Grandcategory::orderBy('position','ASC')->get();
        return view('admin.grandcategory.index', compact('cats'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parent = Category::all();
        return view('admin.grandcategory.add', compact('parent'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        

        $this->validate($request, [

            'parent_id' => 'required|not_in:0', 'title' => 'required|not_in:0',
            'subcat_id' => 'required|not_in:null',

        ], [
            "title.required" => "Please enter Childcategory name",
            "name.required" => "Please Select Parent Field...",

        ]);

        $input = $request->all();
        $data = new Grandcategory;

        if ($file = $request->file('image'))
        {

            $optimizeImage = Image::make($file);
            $optimizePath = public_path() . '/images/grandcategory/';
            $image = time() . $file->getClientOriginalName();
            $optimizeImage->save($optimizePath . $image, 72);

            $input['image'] = $image;

        }
        $input['position'] = (Grandcategory::count()+1);
        $input['description'] = clean($request->description);
        $data->create($input);
        return redirect()->route('grandcategory.index')
            ->with("added", "Child Category Has Been Added");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Grandcategory  $grandcategory
     * @return \Illuminate\Http\Response
     */
    public function reposition(Request $request)
    {
        $data= $request->all();
        $posts = Grandcategory::all();
        $pos = $data['id'];
        $position =json_encode($data);
        foreach ($posts as $key => $item) {
            Grandcategory::where('id', $item->id)->update(array('position' => $pos[$key]));
        }
        
        return response()->json(['msg'=>'Sorted Successfully', 'success'=>true]);
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Grandcategory  $grandcategory
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $parent = Category::all();
        $subcat = Subcategory::all();
        $cat = Grandcategory::find($id);
        return view("admin.grandcategory.edit", compact("cat", 'parent', 'subcat'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Grandcategory  $grandcategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $cat = Grandcategory::findOrFail($id);

        $input = $request->all();

        if ($file = $request->file('image'))
        {

            if ($cat->image != null)
            {

                $image_file = @file_get_contents(public_path() . '/images/grandcategory/' . $cat->image);

                if ($image_file)
                {
                    unlink(public_path() . '/images/grandcategory/' . $cat->image);
                }

            }

            $optimizeImage = Image::make($file);
            $optimizePath = public_path() . '/images/grandcategory/';
            $name = time() . $file->getClientOriginalName();
            $optimizeImage->save($optimizePath . $name, 72);

            $input['image'] = $name;

        }

        else
        {
            $input['image'] = $cat->image;
            $cat->update($input);
        }

        $input['description'] = clean($request->description);

        $cat->update($input);

        return redirect('admin/grandcategory')->with('updated', 'Child Category has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Grandcategory  $grandcategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $getdata = Grandcategory::find($id);

        if (count($getdata->products) > 0)
        {
            return back()
                ->with('warning', 'Childcategory cant be deleted as its linked to products !');
        }

        $value = $getdata->delete();
        if ($value)
        {
            session()->flash("deleted", "Child Category Has Been Deleted");
            return redirect("admin/grandcategory");
        }
    }
}

