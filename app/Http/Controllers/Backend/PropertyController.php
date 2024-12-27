<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Property;
use App\Models\MultiImage;
use App\Models\Facility;

use App\Models\Amenities;
use App\Models\PropertyType;
use App\Models\User;

use Intervention\Image\Facades\Image;
use Haruncpi\LaravelIdGenerator\IdGenerator; // generate property code
use Carbon\Carbon; //generate present date i.e adding date when property was added
use App\Models\PackagePlan;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\State;


class PropertyController extends Controller
{
    public function AllProperty(){

        $property = Property::latest()->get();

        return view('backend.property.all_property',compact('property'));

    } // End Method 


    public function AddProperty(){

        $propertytype = PropertyType::latest()->get();
        $pstate = State::latest()->get();
        $amenities = Amenities::latest()->get();
        $activeAgent = User::where('status','active')->where('role','agent')->latest()->get();
        return view('backend.property.add_property',compact('propertytype','amenities','activeAgent','pstate'));
    }// End Method 


    public function StoreProperty(Request $request){

        $amen = $request->amenities_id ?? [];  //////////////////////////////////////////////
        $amenites = implode(",", $amen); // convert amenitied id list from dictionary type to list type
        // dd($amenites); //see all amenities id in the list

        $pcode = IdGenerator::generate(['table' => 'properties','field' => 'property_code','length' => 5, 'prefix' => 'PC' ]); //generate in propertied db table, in field(column) property_code


        $image = $request->file('property_thambnail'); //property thumbnail
       // $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension(); //rename $image to uniqId.(jpg,png.. extention)
       // Image::make($image)->resize(370,250)->save('upload/property/thambnail/'.$name_gen); // save thumb with uniq name inside thambnail folder
       
   

if ($image) {
    $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
    Image::make($image)->resize(370,250)->save('upload/property/thambnail/'.$name_gen); // save thumb with uniq name inside thambnail folder
    // Continue with saving or processing the image
}
 else {
    // Handle the error, e.g. return a message to the user
    return back()->withErrors(['property_thambnail' => 'Safwan thumbnail is required.']);
}



       $save_url = 'upload/property/thambnail/'.$name_gen;

        $property_id = Property::insertGetId([

            'ptype_id' => $request->ptype_id,
            'amenities_id' => $amenites,
            'property_name' => $request->property_name,
            'property_slug' => strtolower(str_replace(' ', '-', $request->property_name)),
            'property_code' => $pcode,
            'property_status' => $request->property_status,

            'lowest_price' => $request->lowest_price,
            'max_price' => $request->max_price,
            'short_descp' => $request->short_descp,
            'long_descp' => $request->long_descp,
            'bedrooms' => $request->bedrooms,
            'bathrooms' => $request->bathrooms,
            'garage' => $request->garage,
            'garage_size' => $request->garage_size,

            'property_size' => $request->property_size,
            'property_video' => $request->property_video,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,

            'neighborhood' => $request->neighborhood,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'featured' => $request->featured,
            'hot' => $request->hot,
            'agent_id' => $request->agent_id,
            'status' => 1, //default value was 0, change to 1 for now to indicate property was added
            'property_thambnail' => $save_url,
            'created_at' => Carbon::now(), //present date
        ]);

        /// Multiple Image Upload From Here ////

        $images = $request->file('multi_img');
        if ($images){
            foreach($images as $img){

            $make_name = hexdec(uniqid()).'.'.$img->getClientOriginalExtension();
            Image::make($img)->resize(770,520)->save('upload/property/multi-image/'.$make_name);
            $uploadPath = 'upload/property/multi-image/'.$make_name;

            MultiImage::insert([

                'property_id' => $property_id,
                'photo_name' => $uploadPath,
                'created_at' => Carbon::now(), 

            ]); 
            } // End Foreach
        }

         // End Multiple Image Upload Here //    

        // Nearby Facilities Add From Here //

        $facilities = Count($request->facility_name); //count no. of facilities as number can be limitless and have eqivalent num of rows in the frontend
// although no of facilities are limited, each can have a different distance value, so for each property and distance we need a value in facilities table
        if ($facilities != NULL) {  // facilities dictionary not empty
           for ($i=0; $i < $facilities; $i++) { 

                
                $fname=$request->facility_name[$i];
                if($fname!=NULL){ //no facility was named
                
                    $fcount = new Facility();
                    $fcount->property_id = $property_id;
                    $fcount->facility_name = $request->facility_name[$i];
                    $fcount->distance = $request->distance[$i];
                    $fcount->save();
                }
           }
        }
         // End Facilities  //

            $notification = array(
            'message' => 'Property Inserted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.property')->with($notification);
    }// End Method 

    public function EditProperty($id){

        $facilities = Facility::where('property_id',$id)->get();
        $property = Property::findOrFail($id);

        $type = $property->amenities_id;
        $property_ami = explode(',', $type);

        $multiImage = MultiImage::where('property_id',$id)->get();

        $pstate = State::latest()->get();
        $propertytype = PropertyType::latest()->get();
        $amenities = Amenities::latest()->get();
        $activeAgent = User::where('status','active')->where('role','agent')->latest()->get();

        return view('backend.property.edit_property',compact('property','propertytype','amenities','activeAgent','property_ami','multiImage','facilities','pstate'));
        

    }// End Method 

    public function UpdateProperty(Request $request){

        $amen = $request->amenities_id ?? [];
        $amenites = implode(",", $amen);

        $property_id = $request->id;

        Property::findOrFail($property_id)->update([

            'ptype_id' => $request->ptype_id,
            'amenities_id' => $amenites,
            'property_name' => $request->property_name,
            'property_slug' => strtolower(str_replace(' ', '-', $request->property_name)), 
            'property_status' => $request->property_status,

            'lowest_price' => $request->lowest_price,
            'max_price' => $request->max_price,
            'short_descp' => $request->short_descp,
            'long_descp' => $request->long_descp,
            'bedrooms' => $request->bedrooms,
            'bathrooms' => $request->bathrooms,
            'garage' => $request->garage,
            'garage_size' => $request->garage_size,

            'property_size' => $request->property_size,
            'property_video' => $request->property_video,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,

            'neighborhood' => $request->neighborhood,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'featured' => $request->featured,
            'hot' => $request->hot,
            'agent_id' => $request->agent_id, 
            'updated_at' => Carbon::now(), 

        ]);

         $notification = array(
            'message' => 'Property Updated Successfully',
            'alert-type' => 'success'
        );

        return redirect()->route('all.property')->with($notification); 

    }// End Method 



    public function UpdatePropertyThambnail(Request $request){

        $pro_id = $request->id;
        $oldImage = $request->old_img;

        $image = $request->file('property_thambnail');
        if ($image) {
            $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
            Image::make($image)->resize(370,250)->save('upload/property/thambnail/'.$name_gen); // save thumb with uniq name inside thambnail folder
            // Continue with saving or processing the image
        } else {
            // Handle the error, e.g. return a message to the user
            return back()->withErrors(['property_thambnail' => 'Safwan thumbnail is required.']);
        }


        $save_url = 'upload/property/thambnail/'.$name_gen;

        if (file_exists($oldImage)) {
            unlink($oldImage);
        }

        Property::findOrFail($pro_id)->update([

            'property_thambnail' => $save_url,
            'updated_at' => Carbon::now(), 
        ]);

         $notification = array(
            'message' => 'Property Image Thambnail Updated Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification); 

    }// End Method 


    public function UpdatePropertyMultiimage(Request $request){

        $imgs = $request->multi_img;
        if($imgs){
                foreach($imgs as $id => $img){
                    $imgDel = MultiImage::findOrFail($id);
                    unlink($imgDel->photo_name);

                    $make_name = hexdec(uniqid()).'.'.$img->getClientOriginalExtension();
                    Image::make($img)->resize(770,520)->save('upload/property/multi-image/'.$make_name);
                    $uploadPath = 'upload/property/multi-image/'.$make_name;

                    MultiImage::where('id',$id)->update([

                        'photo_name' => $uploadPath,
                        'updated_at' => Carbon::now(),

                ]);

                } // End Foreach 


         $notification = array(
            'message' => 'Property Multi Image Updated Successfully',
            'alert-type' => 'success'
        );
    
        return redirect()->back()->with($notification); 
        }
    else {return redirect()->back();}

    }// End Method 


    public function PropertyMultiImageDelete($id){

        $oldImg = MultiImage::findOrFail($id);
        unlink($oldImg->photo_name);

        MultiImage::findOrFail($id)->delete();

        $notification = array(
            'message' => 'Property Multi Image Deleted Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification); 

    }// End Method 


    public function StoreNewMultiimage(Request $request){

        $new_multi = $request->imageid;
        $image = $request->file('multi_img');
if($image){
     $make_name = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
    Image::make($image)->resize(770,520)->save('upload/property/multi-image/'.$make_name);
        $uploadPath = 'upload/property/multi-image/'.$make_name;

        MultiImage::insert([
            'property_id' => $new_multi,
            'photo_name' => $uploadPath,
            'created_at' => Carbon::now(), 
        ]);
    
    $notification = array(
            'message' => 'Property Multi Image Added Successfully',
            'alert-type' => 'success'
        );
    return redirect()->back()->with($notification); 
    }
    else{return redirect()->back(); }
        


    }// End Method 



    public function UpdatePropertyFacilities(Request $request){

        $pid = $request->id;
        Facility::where('property_id',$pid)->delete();

        if ($request->facility_name == NULL) {
           return redirect()->back();
        }
        else{
            Facility::where('property_id',$pid)->delete();

          $facilities = Count($request->facility_name); 

           for ($i=0; $i < $facilities; $i++) { 
               $fcount = new Facility();
               $fcount->property_id = $pid;
               $fcount->facility_name = $request->facility_name[$i];
               $fcount->distance = $request->distance[$i];
               $fcount->save();
           } // end for 


            $notification = array(
                'message' => 'Property Facility Updated Successfully',
                'alert-type' => 'success'
            );
        
            return redirect()->back()->with($notification); 
        }
    }// End Method 




    public function DeleteProperty($id){

        $property = Property::findOrFail($id);
        unlink($property->property_thambnail); //delet property_id er thambnail from folder
 
        Property::findOrFail($id)->delete(); // delete the property 

        $image = MultiImage::where('property_id',$id)->get();

        foreach($image as $img){
            unlink($img->photo_name);
            MultiImage::where('property_id',$id)->delete();
        }

        $facilitiesData = Facility::where('property_id',$id)->get();
        foreach($facilitiesData as $item){
            $item->facility_name;
            Facility::where('property_id',$id)->delete();
        }


         $notification = array(
            'message' => 'Property Deleted Successfully',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification); 

    }// End Method  


    public function DetailsProperty($id){

        $facilities = Facility::where('property_id',$id)->get();
        $property = Property::findOrFail($id);

        $type = $property->amenities_id;
        $property_ami = explode(',', $type);

        $multiImage = MultiImage::where('property_id',$id)->get();

        $propertytype = PropertyType::latest()->get();
        $amenities = Amenities::latest()->get();
        $activeAgent = User::where('status','active')->where('role','agent')->latest()->get();

        return view('backend.property.details_property',compact('property','propertytype','amenities','activeAgent','property_ami','multiImage','facilities'));

    }// End Method 




    public function InactiveProperty(Request $request){

        $pid = $request->id;
        Property::findOrFail($pid)->update([

            'status' => 0,

        ]);

      $notification = array(
            'message' => 'Property Inactive Successfully',
            'alert-type' => 'success'
        );

        return redirect()->route('all.property')->with($notification); 


    }// End Method 


      public function ActiveProperty(Request $request){

        $pid = $request->id;
        Property::findOrFail($pid)->update([

            'status' => 1,

        ]);

      $notification = array(
            'message' => 'Property Active Successfully',
            'alert-type' => 'success'
        );

        return redirect()->route('all.property')->with($notification); 


    }// End Method 

    public function AdminPackageHistory(){

        $packagehistory = PackagePlan::latest()->get();
        return view('backend.package.package_history',compact('packagehistory'));
   
   
       }// End Method 

       public function PackageInvoice($id){

        $packagehistory = PackagePlan::where('id',$id)->first();

        $pdf = Pdf::loadView('backend.package.package_history_invoice', compact('packagehistory'))->setPaper('a4')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
        ]);
        return $pdf->download('invoice.pdf');

    }// End Method 





}