<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\AddSubVariant;
use App\ProductValues;
use App\ProductAttributes;
use App\VariantImages;
use Alert;
use DB;
use File;

class SellerAddvariantController extends Controller
{
    
    public function getIndex($id)
    {
        $findpro = Product::findorfail($id);
        return view('seller.productvariant.subvar', compact('findpro'));
    }

    public function post(Request $request, $id)
    {
        $request->validate(['main_attr_id' => 'required', 'main_attr_value' => 'required', 'image1' => 'required'], ['main_attr_id.required' => 'Please select an option', 'main_attr_value.required' => 'Please select a value', 'image1.required' => 'Atleast one image is required']);

        $input = $request->all();

        $array2 = AddSubVariant::where('pro_id', $id)->get();

        foreach ($array2 as $key => $value)
        {

            $array1 = $value->main_attr_value;

            $test = $input['main_attr_value'];

            $conversion_rate = array_diff($array1, $test);

            if ($conversion_rate == null)
            {
                return back()->with('warning', 'Variant already exist ! Kindly Update that');
            }

            else
            {
                foreach ($conversion_rate as $e => $new)
                {

                    if ($new == 0)
                    {
                        return back()->with('warning', 'Variant exist Kindly Update it !');
                    }
                    else
                    {

                    }

                }

            }

        }

        $test = new AddSubVariant();
        $input['pro_id'] = $id;
        //Getting All Def
        $all_def = AddSubVariant::where('def', '=', 1)->where('pro_id', '=', $id)->get();

        if (isset($request->def))
        {

            //Updating Current Def
            foreach ($all_def as $value)
            {
                $remove_def = AddSubVariant::where('id', '=', $value->id)
                    ->update(['def' => 0]);
            }

            $input['def'] = 1;
        }
        else
        {
            if ($all_def->count() < 1)
            {
                return back()
                    ->with('warning', 'Atleast one variant should be set to default !');
            }

            $input['def'] = 0;
        }

        $test->create($input);

        $lastid = AddSubVariant::orderBy('id', 'desc')->first()->id;

        $varimage = new VariantImages();

        $path = public_path() . '/variantimages/';
        File::makeDirectory($path, $mode = 0777, true, true);

        if ($file = $request->file('image1'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image1 = $name;
            $varimage->main_image = $name;

        }

        if ($file = $request->file('image2'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image2 = $name;

        }

        if ($file = $request->file('image3'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image3 = $name;

        }

        if ($file = $request->file('image4'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image4 = $name;

        }

        if ($file = $request->file('image5'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image5 = $name;

        }

        if ($file = $request->file('image6'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);
            $varimage->image6 = $name;

        }

        $varimage->var_id = $lastid;

        $varimage->save();

        return redirect()
            ->route('seller.add.var', $id)->with('added', 'Variant Linked Successfully !');

    }

    public function edit($id)
    {
        $vars = AddSubVariant::findorfail($id);
        return view('seller.productvariant.edit', compact('vars'));
    }

    public function delete($id)
    {
        $vars = AddSubVariant::findorfail($id);

        if ($vars->def != 1)
        {
            $vars->delete();
        }
        else
        {
            return back()
                ->with('warning', "Default variant cannot be deleted !");
        }

        return back()
            ->with('deleted', 'Variant has been Deleted !');
    }

    public function update(Request $request, $id)
    {

        $request->validate(['min_order_qty' => 'numeric|min:1'], ['min_order_qty.min' => 'Minimum order quantity must be atleast 1']);

        $vars = AddSubVariant::findorfail($id);

        $array2 = AddSubVariant::where('pro_id', $vars->pro_id)
            ->get();
        $all_def = AddSubVariant::where('def', '=', 1)->where('pro_id', $vars->pro_id)
            ->get();
        $all_def2 = AddSubVariant::where('pro_id', $vars->pro_id)
            ->get();

        if ($all_def2->count() < 1)
        {
            return back()
                ->with('warning', 'Atleast one value should be set to default !');

        }

        $varimage = VariantImages::where('var_id', $id)->first();
        $path = public_path() . '/variantimages/';

        if ($file = $request->file('image1'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image1 != null)
            {
                unlink('../public/variantimages/' . $varimage->image1);
            }

            if ($varimage->image1 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image1 = $name;

        }

        if ($file = $request->file('image2'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image2 != null)
            {
                unlink('../public/variantimages/' . $varimage->image2);
            }

            if ($varimage->image2 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image2 = $name;

        }

        if ($file = $request->file('image3'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image3 != null)
            {
                unlink('../public/variantimages/' . $varimage->image3);
            }

            if ($varimage->image3 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image3 = $name;

        }

        if ($file = $request->file('image4'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image4 != null)
            {
                unlink('../public/variantimages/' . $varimage->image4);
            }

            if ($varimage->image4 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image4 = $name;

        }

        if ($file = $request->file('image5'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image5 != null)
            {
                unlink('../public/variantimages/' . $varimage->image5);
            }

            if ($varimage->image5 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image5 = $name;

        }

        if ($file = $request->file('image6'))
        {

            $name = 'variant_' . time() . str_random(10);
            $file->move($path, $name);

            if ($varimage->image6 != null)
            {
                unlink('../public/variantimages/' . $varimage->image6);
            }

            if ($varimage->image6 == $varimage->main_image)
            {
                $varimage->main_image = $name;
            }

            $varimage->image6 = $name;

        }

        $varimage->save();

        $input = $request->all();

        $current_stock = $vars->stock;
        $addstock = $request->stock;

        $newstock = ($current_stock) + ($addstock);

        if ($newstock < 0)
        {
            return back()->with('deleted', 'Stock cannot be less than 0 !');
        }

        if (isset($request->def))
        {

            //Removing Other Def If Any
            foreach ($all_def as $value)
            {

                if ($vars->id != $value->id)
                {
                    $remove_def = AddSubVariant::where('id', '=', $value->id)
                        ->update(['def' => 0]);
                }

            }

            $input['def'] = 1;
        }
        else
        {

            if ($all_def2->count() <= 1)
            {
                return back()
                    ->with('warning', 'Atleast one value should be set to default !');
            }

            $input['def'] = 0;
        }

        foreach ($array2 as $key => $value)
        {

            $array1 = $value->main_attr_value;

            $test = $input['main_attr_value'];

            $conversion_rate = array_diff($array1, $test);

            if ($conversion_rate == null)
            {

                if ($id == $value->id)
                {

                    if ($request->stock == '')
                    {

                        $input['stock'] = $vars->stock;

                    }
                    else
                    {
                        $existstock = $vars->stock;
                        $input['stock'] = $vars->stock + $request->stock;
                    }

                    $vars->update($input);

                    return redirect()->route('seller.add.var', $vars->pro_id)
                        ->with('updated', 'Variant has been Updated !');
                }
                else
                {
                    return back()
                        ->with('warning', 'Linked Variant already exist !');
                }

            }

            else
            {
                foreach ($conversion_rate as $e => $new)
                {

                    if ($new == 0)
                    {

                        if ($id == $value->id)
                        {
                            if ($request->stock == '')
                            {

                                $input['stock'] = $vars->stock;

                            }
                            else
                            {
                                $existstock = $vars->stock;
                                $input['stock'] = $vars->stock + $request->stock;
                            }

                            $vars->update($input);

                            return redirect()->route('seller.add.var', $vars->pro_id)
                                ->with('updated', 'Linked Variant Updated !');
                        }
                        else
                        {
                            return back()
                                ->with('warning', 'Linked Variant exist !');
                        }

                    }
                    else
                    {

                    }

                }

            }
        }

        if ($request->stock == '')
        {

            $input['stock'] = $vars->stock;

        }
        else
        {
            $existstock = $vars->stock;
            $input['stock'] = $vars->stock + $request->stock;
        }

        $vars->update($input);

        return redirect()->route('seller.add.var', $vars->pro_id)
            ->with('updated', 'Variant Updated !');

    }

    public function gettingvar(Request $request)
    {
        $id = $request->id;
        $name = $request->value;
        $attr_name = $request->attr_name;

        $allvalues = AddSubVariant::all();

        $conversion_rate = array();

        foreach ($allvalues as $g)
        {
            array_push($conversion_rate, $g->main_attr_value);
        }

        $testing = array();
        $getvalname2 = array();
        foreach ($conversion_rate as $key => $val)
        {
            if ($val[$attr_name] === $name)
            {
                array_push($testing, $val);
                // return $val[2];
                if ($id == 0)
                {
                    $getvalname = ProductValues::where('id', '=', $val[2])->first()->values;
                    array_push($getvalname2, $getvalname);
                }
                else
                {
                    $getvalname = ProductValues::where('id', '=', $val[1])->first()->values;
                    array_push($getvalname2, $getvalname);
                }

            }
        }
        $test2;
        if ($id == 0)
        {
            $test2 = 1;
        }
        else
        {
            $test2 = 0;
        }

        if ($id == 0)
        {

        }
        else
        {

        }

        return response()->json([$testing, $test2, $getvalname2]);

    }

    public function ajaxGet(Request $request, $id)
    {

        $attr_name = $request->attr_name;
        $value = $request->value;
        $array1 = $request->arr;

        // return count($array1);
        $newarr = array();
        $arr_count = count($array1);
        if ($arr_count > 1)
        {
            array_push($newarr, [$array1[0]["key"] => $array1[0]["value"], $array1[1]["key"] => $array1[1]["value"]]);
        }
        else
        {
            array_push($newarr, [$array1[0]["key"] => $array1[0]["value"]
            // $array1[1]["key"] => $array1[1]["value"]
            ]);
        }

        // foreach ($array1 as $key => $value) {
        //  array_push($newarr, [$value["key"] => $value["value"]]);
        //  array_splice($newarr, 3, 0, [$value["key"] => $value["value"]]);
        // }
        $t = count($newarr);
        // return($newarr);
        $p_attr_id = ProductAttributes::where('attr_name', '=', $attr_name)->first()->id;

        // $test = array(
        //  'id' => $
        // );
        $all_var = AddSubVariant::where('pro_id', '=', $id)->with('variantimages')
            ->get();
        //  return $newarr;
        //  return $all_var;
        foreach ($all_var as $var)
        {

            if ($newarr[0] == $var['main_attr_value'])
            {
                return $var;
            }

        }

    }

    /*On load data*/

    public function ajaxGet2(Request $request, $id)
    {
        $array1 = $request->arr;
        $newarr = array();
        $arr_count = count($array1);

        if ($arr_count > 1)
        {
            array_push($newarr, [$array1[0]["key"] => $array1[0]["value"], $array1[1]["key"] => $array1[1]["value"]]);
        }
        else
        {
            array_push($newarr, [$array1[0]["key"] => $array1[0]["value"]

            ]);
        }

        //Get all variant with this id
        $all_sub_var = AddSubVariant::where('pro_id', '=', $id)->with('variantimages')
            ->get();

        foreach ($all_sub_var as $value)
        {

            if ($value['main_attr_value'] == $newarr[0])
            {
                return $value;
            }

        }

    }

    public function quicksetdefault(Request $request, $id)
    {
        $pro_id = $request->pro_id;
        $addsub = AddSubVariant::findorfail($id);

        $all_def = AddSubVariant::where('def', '=', 1)->where('pro_id', $pro_id)->get();
        $all_def2 = AddSubVariant::where('pro_id', $pro_id)->get();

        $c = count($all_def2);

        if ($all_def2->count() <= 1)
        {
            return response()
                ->json(array(
                'count' => $c,
                "msg" => "Atleast one value should set to be default"
            ));
        }

        foreach ($all_def as $value)
        {

            if ($id != $value->id)
            {
                AddSubVariant::where('id', '=', $value->id)
                    ->update(['def' => 0]);
            }

        }

        AddSubVariant::where('id', '=', $id)->update(['def' => 1]);

        return response()
            ->json(array(
            'msg' => 'Default Variant is changed !',
            'count' => $c,
            'id' => $id
        ));

    }
}
