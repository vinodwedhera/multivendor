<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\VariantImages;

class DeleteImageController extends Controller
{
    public function deleteimg1(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image1 = NULL;

        $del->save();

        return "Success";

    }

    public function deleteimg2(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image2 = NULL;

        $del->save();

        return "Success";

    }

    public function deleteimg3(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image3 = NULL;

        $del->save();

        return "Success";

    }

    public function deleteimg4(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image4 = NULL;

        $del->save();

        return "Success";

    }

    public function deleteimg5(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image5 = NULL;

        $del->save();

        return "Success";

    }

    public function deleteimg6(Request $request, $id)
    {

        $del = VariantImages::findorfail($id);

        $image = @file_get_contents(public_path() . '/images/variantimages/' . $$request->getval);
       
        if($image){
             unlink('../public/variantimages/' . $request->getval);
        }

        $del->image6 = NULL;

        $del->save();

        return "Success";

    }

    public function setdef(Request $request, $id)
    {
        
        $findrow = VariantImages::where('var_id', $id)->first();

        $findrow->main_image = $request->defimage;

        $findrow->save();

        return "Success";

    }

}

