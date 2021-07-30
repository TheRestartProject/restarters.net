<?php

namespace App\Http\Controllers;

use App\Brands;
use Auth;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class BrandsController extends Controller
{
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

    public function getCreateBrand()
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        return view('brands.create', [
        'title' => 'Add Brand',
        ]);
    }

    public function postCreateBrand(Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $brand = Brands::create([
        'brand_name' => $request->input('brand_name'),
        ]);

        return Redirect::to('brands/edit/'.$brand->id)->with('success', 'Brand successfully created!');
    }

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

    public function postEditBrand($id, Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->update([
        'brand_name' => $request->input('brand-name'),
        ]);

        return Redirect::back()->with('success', 'Brand successfully updated!');
    }

    public function getDeleteBrand($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->delete();

        return Redirect::back()->with('message', 'Brand deleted!');
    }
}
