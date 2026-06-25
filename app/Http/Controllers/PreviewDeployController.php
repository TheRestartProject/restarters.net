<?php

namespace App\Http\Controllers;

use App\Helpers\Fixometer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PreviewDeployController extends Controller
{
    private const REPO = 'TheRestartProject/restarters.net';
    private const WORKFLOW = 'preview-deploy.yml';

    public function show(): View|RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $token = config('services.github.deploy_pat');
        $prs = [];
        $error = null;

        if ($token) {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/" . self::REPO . "/pulls", [
                    'state' => 'open',
                    'per_page' => 50,
                ]);

            if ($response->successful()) {
                $prs = collect($response->json())->map(fn($pr) => [
                    'number' => $pr['number'],
                    'title' => $pr['title'],
                    'branch' => $pr['head']['ref'],
                    'author' => $pr['user']['login'],
                ])->all();
            } else {
                $error = 'Could not fetch PRs from GitHub (status ' . $response->status() . ')';
            }
        } else {
            $error = 'GITHUB_DEPLOY_PAT is not configured. Set it as a Fly secret on restarters-dev.';
        }

        return view('admin.preview-deploy', compact('prs', 'error'));
    }

    public function deploy(Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $branch = $request->input('branch');

        if (! $branch) {
            return back()->withErrors(['branch' => 'Please select a branch.']);
        }

        $token = config('services.github.deploy_pat');

        if (! $token) {
            return back()->withErrors(['token' => 'GITHUB_DEPLOY_PAT is not configured.']);
        }

        $response = Http::withToken($token)
            ->post("https://api.github.com/repos/" . self::REPO . "/actions/workflows/" . self::WORKFLOW . "/dispatches", [
                'ref' => 'develop',
                'inputs' => ['branch' => $branch],
            ]);

        if ($response->successful()) {
            return back()->with('success', "Deploy of \"$branch\" triggered. Build takes ~15 minutes. Watch: https://github.com/" . self::REPO . "/actions");
        }

        return back()->withErrors(['deploy' => 'GitHub API error: ' . $response->status() . ' — ' . $response->body()]);
    }
}
