<?php

namespace App\Http\Controllers;

use App\Category;
use App\Helpers\Fixometer;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CategoryController extends Controller
{
    public function index()
    {
        $Category = new Category;

        return view('category.index', [
        'list' => $Category->findAll(),
        'categories'  => $Category->listed(),
        ]);
    }

    public function getEditCategory($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $category = Category::find($id);

        $c = new Category;
        $categories = $c->listed();

        return view('category.edit', [
        'title' => 'Edit Category',
        'category'   => $category,
        'categories'  => $categories,
        ]);
    }

    public function postEditCategory($id, Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        try {
            $category = Category::find($id);
            $category->update([
            'name' => $request->input('category_name'),
            'weight' => $request->input('weight'),
            'footprint' => $request->input('co2_footprint'),
            'footprint_reliability' => $request->input('reliability'),
            'cluster' => $request->input('category_cluster'),
            'description_short' => $request->input('categories_desc')
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('danger', __('category.update_error'));
        }

        return redirect()->back()->with('success', __('category.update_success'));
    }
}
