<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Auth;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    /**
     * Nothing to see here.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->action([\App\Http\Controllers\HomeController::class, 'index']);
    }

    public function guide(Request $request)
    {
        if (! Auth::check()) {
            return $this->index($request);
        }

        return view('test.styles', []);
    }

    public function find(Request $request): View
    {
        $result = $this->findClassElements();
        logger($result);

        return view('test.stylesfind', ['content' => $result]);
    }

    /**
     * Find CSS class declarations in blade files.
     * For investigative purposes.
     */
    private function findClassElements()
    {
        $files = $this->getBlades();
        $result = $this->getClasses();
        foreach ($files as $file) {
            $content = file_get_contents($file);
            foreach ($result as $type => $elem) {
                foreach ($elem as $class => $arr) {
                    if ($n = preg_match_all('/class="([-\w\d\s]+\s'.$class.')/', $content, $matches)) {
                        $result[$type][$class][$file] = $n;
                    }
                    arsort($result[$type][$class], SORT_NUMERIC);
                }
            }
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    private function getBlades($path = null, &$result = [])
    {
        if (is_null($path)) {
            $path = resource_path().'/views';
        }
        $files = scandir($path);
        foreach ($files as $file) {
            $filepath = $path.DIRECTORY_SEPARATOR.$file;
            if (is_dir($filepath)) {
                if (strpos($file, '.') !== 0 && $file !== 'test') {
                    $this->getBlades($filepath, $result);
                }
            }
            if (strpos($file, 'blade')) {
                $result[] = $filepath;
            }
        }

        return $result;
    }

    private function getClasses()
    {
        return [
            'buttons' => [
                'btn-primary' => [],
                'btn-sm' => [],
                'btn-outline-primary' => [],
                'btn-rounded' => [],
                'btn-secondary' => [],
                'btn-outline-secondary' => [],
                'btn-info' => [],
                'btn-outline-info' => [],
                'btn-success' => [],
                'btn-outline-success' => [],
                'btn-warning' => [],
                'btn-outline-warning' => [],
                'btn-danger' => [],
                'btn-outline-danger' => [],
                'btn-light' => [],
                'btn-outline-light' => [],
                'btn-dark' => [],
                'btn-outline-dark' => [],
                'btn-link' => [],
                'btn-view' => [],
                'btn-preferences' => [],
                'btn-title' => [],
                'btn-fault-info' => [],
                'btn-fault-option' => [],
                'btn-column' => [],
                'btn btn-tertiary' => [],
                'btn-tertiary' => [],
                'dropdown-toggle' => [],
            ],
            'alerts' => [
                'information-alert' => [],
                'alert-primary' => [],
                'alert-secondary' => [],
                'alert-success' => [],
                'alert-info' => [],
                'alert-warning' => [],
                'alert-danger' => [],
                'alert-delete' => [],
                'alert-light' => [],
                'alert-dark' => [],
            ],
            'text' => [
                'lead' => [],
                'small' => [],
                'initialism' => [],
                'blockquote' => [],
                'text-muted' => [],
                'text-black' => [],
                'display-1' => [],
                'display-3' => [],
                'display-4' => [],
            ],
            'panels' => [
                'panel' => [],
                'panel__blue' => [],
                'panel__orange' => [],
            ],
            'badges' => [
                'badge-primary' => [],
                'badge-secondary' => [],
                'badge-success' => [],
                'badge-info' => [],
                'badge-warning' => [],
                'badge-danger' => [],
                'badge-light' => [],
                'badge-dark' => [],
            ],
            'cards' => [
                'card__content' => [],
            ],
        ];
    }
}
