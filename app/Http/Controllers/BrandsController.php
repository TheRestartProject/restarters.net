<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Helpers\Fixometer;
use Auth;

class BrandsController extends Controller
{
    /**
     * Render the brands admin page (a Vue SPA that talks to /api/v2/brands).
     * All create/edit/delete now goes through the API.
     *
     * @param  int|null  $editId  Optional brand id to pre-open in the edit modal
     *                            (used by the legacy /brands/edit/{id} bookmark).
     */
    public function index($editId = null)
    {
        $user = Auth::user();

        if (! Fixometer::hasRole($user, 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $all_brands = Brands::orderBy('brand_name', 'asc')->get();

        $brandsForVue = $all_brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'brand_name' => $brand->brand_name,
            ];
        })->values();

        return view('brands.index', [
            'title' => 'Brands',
            'brands' => $all_brands,
            'brandsForVue' => $brandsForVue,
            'apiToken' => $user->api_token,
            'editId' => $editId !== null ? (int) $editId : null,
        ]);
    }
}
