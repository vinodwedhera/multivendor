<?php
namespace App\Http\Controllers;

use App\Category;
use Image;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index()
    {
        $category = Category::orderBy('position','asc')->get();
        return view("admin.category.index", compact("category"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {

        return view("admin.category.add_category");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $data = $this->validate($request, ["title" => "required"], [

        "title.required" => "Category Name is required", "description.required" => "Description is required", ]);

        $input = $request->all();

        $input['description'] = clean($request->description);

        $cat = new Category();

        if ($file = $request->file('image'))
        {

            $optimizeImage = Image::make($file);
            $optimizePath = public_path() . '/images/category/';
            $image = time() . $file->getClientOriginalName();
            $optimizeImage->save($optimizePath . $image, 72);

            $input['image'] = $image;

        }

        $input['position'] = (Category::count()+1);

        $cat->create($input);

        return back()->with("added", "Category Has Been Added !");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function reposition(Request $request)
    {
        $data= $request->all();
        $posts = Category::all();
        $pos = $data['id'];
        $position =json_encode($data);
        foreach ($posts as $key => $item) {
            Category::where('id', $item->id)->update(array('position' => $pos[$key]));
        }
        
        return response()->json(['msg'=>'Sorted Successfully', 'success'=>true]);
        
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cat = Category::findOrFail($id);

        return view("admin.category.edit", compact("cat"));

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

        $data = $this->validate($request, ["title" => "required"], [

        "title.required" => "Name is needed", "description.required" => "Description is needed", ]);

        $cat = Category::findOrFail($id);

        $category = Category::findOrFail($id);
        $input = $request->all();

        $input['description'] = clean($request->description);

        if ($file = $request->file('image'))
        {

            if ($category->image != null)
            {

                $image_file = @file_get_contents(public_path() . '/images/category/' . $category->image);

                if ($image_file)
                {
                    unlink(public_path() . '/images/category/' . $category->image);
                }

            }

            $optimizeImage = Image::make($file);
            $optimizePath = public_path() . '/images/category/';
            $name = time() . $file->getClientOriginalName();
            $optimizeImage->save($optimizePath . $name, 72);

            $input['image'] = $name;

        }

        else
        {
            $input['image'] = $cat->image;
            $category->update($input);
        }

        $category->update($input);

        return redirect('admin/category')->with('updated', 'Category has been updated');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (count($category->products) > 0)
        {
            return back()
                ->with('warning', 'Category cant be deleted as its linked to products !');
        }

        if ($category->image != null)
        {

            $image_file = @file_get_contents(public_path() . '/images/category/' . $category->image);

            if ($image_file)
            {

                unlink(public_path() . '/images/category/' . $category->image);
            }
        }

        $value = $category->delete();
        if ($value)
        {
            session()->flash("deleted", "Category Has Been Deleted");
            return redirect("admin/category");
        }
    }

}

