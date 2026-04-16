<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Brands;
use App\Helpers\Fixometer;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

#[Feature('Administration', description: 'Platform administration and configuration')]
class BrandsController extends Controller
{
    #[UserStory('As an Admin, I can view all device brands', persona: 'Admin', theme: 'Reference data')]
    public function index()
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $all_brands = Brands::orderBy('brand_name', 'asc')->get();

        return view('brands.index', [
        'title' => 'Brands',
        'brands' => $all_brands,
        ]);
    }

    #[UserStory('As an Admin, I can create a new device brand', persona: 'Admin', theme: 'Reference data')]
    public function postCreateBrand(Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $brand = Brands::create([
        'brand_name' => $request->input('brand_name'),
        ]);

        return Redirect::to('brands/edit/'.$brand->id)->with('success', __('brands.create_success'));
    }

    #[UserStory('As an Admin, I can access the form to edit a device brand', persona: 'Admin', theme: 'Reference data')]
    public function getEditBrand($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $brand = Brands::find($id);

        return view('brands.edit', [
        'title' => 'Edit Brand',
        'brand' => $brand,
        ]);
    }

    #[UserStory('As an Admin, I can update a device brand', persona: 'Admin', theme: 'Reference data')]
    public function postEditBrand($id, Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->update([
        'brand_name' => $request->input('brand-name'),
        ]);

        return Redirect::back()->with('success', __('brands.update_success'));
    }

    #[UserStory('As an Admin, I can delete a device brand', persona: 'Admin', theme: 'Reference data')]
    public function getDeleteBrand($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->delete();

        return Redirect::back()->with('message', __('brands.delete_success'));
    }
}
