<?php
namespace App\Http\Controllers;

use App\FrontCat;
use Illuminate\Http\Request;

class FrontCatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $NewProCat = FrontCat::first();
        return view('admin.NewProCat.add', compact('NewProCat'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.NewProCat.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $NewProCat = FrontCat::first();

        if (empty($NewProCat))
        {

            $input = $request->all();

            if ($request->name != 0)
            {
                $name = implode(",", $request->name);

                $input['name'] = $name;

            }

            $data = FrontCat::create($input);

            $data->save();

            return back()
                ->with("added", "New Product Category Has Been Added");
        }
        else
        {
            if ($request->name != 0)
            {
                $name = implode(",", $request->name);

                $input['name'] = $name;

            }

            $NewProCat->update($input);

            return back()->with("updated", "New Product Category Has Been Update");

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\FrontCat  $frontCat
     * @return \Illuminate\Http\Response
     */
    public function show(FrontCat $frontCat)
    {
        //
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\FrontCat  $frontCat
     * @return \Illuminate\Http\Response
     */
    public function edit(FrontCat $frontCat)
    {
        //
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\FrontCat  $frontCat
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FrontCat $frontCat)
    {
        //
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\FrontCat  $frontCat
     * @return \Illuminate\Http\Response
     */
    public function destroy(FrontCat $frontCat)
    {
        //
        
    }
}

