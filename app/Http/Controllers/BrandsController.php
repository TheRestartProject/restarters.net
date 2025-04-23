<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Models\Brands;
use App\Helpers\Fixometer;
use Auth;
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

    public function postCreateBrand(Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $brand = Brands::create([
        'brand_name' => $request->input('brand_name'),
        ]);

        return Redirect::to('brands/edit/'.$brand->id)->with('success', __('brands.create_success'));
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

    public function postEditBrand($id, Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->update([
        'brand_name' => $request->input('brand-name'),
        ]);

        return Redirect::back()->with('success', __('brands.update_success'));
    }

    public function getDeleteBrand($id): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Brands::find($id)->delete();

        return Redirect::back()->with('message', __('brands.delete_success'));
    }
}
