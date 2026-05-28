<?php

namespace App\Http\Controllers;

use App\Helpers\Fixometer;
use Auth;
use DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Render the categories admin page (a Vue SPA that talks to /api/v2/categories).
     * Public list view; edit/save require administrator and happen via the API.
     */
    public function index($editId = null): View
    {
        // Fetch categories joined with cluster name, scoped to the current revision
        // (mirrors API\CategoryController). Doing it here means the Vue admin doesn't
        // have to round-trip the API on first paint.
        $categories = DB::select(<<<'SQL'
            SELECT c.idcategories AS id,
                   c.name,
                   c.powered,
                   c.weight,
                   c.footprint,
                   c.footprint_reliability,
                   c.cluster,
                   c.description_short,
                   cl.name AS cluster_name
              FROM categories c
              LEFT JOIN clusters cl ON cl.idclusters = c.cluster
             WHERE c.revision = (SELECT MAX(revision) FROM categories)
             ORDER BY c.name ASC
        SQL);

        $categoriesForVue = array_map(function ($row) {
            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'powered' => $row->powered !== null ? (bool) $row->powered : null,
                'weight' => $row->weight !== null ? (float) $row->weight : null,
                'footprint' => $row->footprint !== null ? (float) $row->footprint : null,
                'footprint_reliability' => $row->footprint_reliability !== null ? (int) $row->footprint_reliability : null,
                'cluster' => $row->cluster !== null ? (int) $row->cluster : null,
                'cluster_name' => $row->cluster_name,
                'description_short' => $row->description_short,
            ];
        }, $categories);

        $clusters = array_map(
            fn ($r) => ['id' => (int) $r->idclusters, 'name' => $r->name],
            DB::select('SELECT idclusters, name FROM clusters ORDER BY idclusters ASC')
        );

        $reliabilityOptions = [];
        foreach (Fixometer::footprintReliability() as $k => $_v) {
            $reliabilityOptions[$k] = __('admin.reliability-' . $k);
        }

        $user = Auth::user();

        return view('category.index', [
            'categoriesForVue' => $categoriesForVue,
            'clusters' => $clusters,
            'reliabilityOptions' => $reliabilityOptions,
            'apiToken' => $user ? $user->api_token : '',
            'editId' => $editId !== null ? (int) $editId : null,
        ]);
    }
}
