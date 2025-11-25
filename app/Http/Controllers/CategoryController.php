<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Category;
use App\Helpers\Fixometer;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CategoryController extends Controller
{
    public function index(): View
    {
        $Category = new Category;
        $list = $Category->findAll();
        $clusters = $Category->listed();

        // Prepare data for Vue table
        $tableData = [];
        foreach ($list as $category) {
            // Find cluster name
            $clusterName = null;
            if (!empty($category->cluster)) {
                foreach ($clusters as $cluster) {
                    if ($cluster->idclusters == $category->cluster) {
                        $clusterName = $cluster->name;
                        break;
                    }
                }
            }

            // Prepare reliability badge HTML
            $reliability = $category->footprint_reliability ?? 6;
            $colors = [
                1 => '#AD2C1C',
                2 => '#FF1B00',
                3 => '#FFBA00',
                4 => '#43B136',
                5 => '#26781C',
                6 => '#FFBA00',
            ];
            $color = $colors[$reliability] ?? '#FFBA00';
            $reliabilityHtml = '<span class="badge indicator-' . $reliability . '" style="background-color: ' . $color . '">' . __('admin.reliability-' . $reliability) . '</span>';

            $tableData[] = [
                'idcategories' => $category->idcategories,
                'name' => $category->name,
                'cluster' => $clusterName,
                'cluster_name' => $clusterName,
                'weight' => $category->weight,
                'footprint' => $category->footprint,
                'footprint_html' => $category->footprint,
                'reliability' => $reliabilityHtml,
            ];
        }

        return view('category.index', [
            'list' => $list,
            'categories' => $clusters,
            'tableData' => $tableData,
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

    public function postEditCategory($id, Request $request): RedirectResponse
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
