<?php

namespace App\Http\Controllers\API;

use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Admin PR-preview-deploy tooling, moved out of the Blade
 * app/Http/Controllers/PreviewDeployController into /api/v2 so the SPA (which
 * authenticates with a sanctum bearer, not a web session) can drive it - the
 * Blade page could not authenticate the post-cutover admin. Backs
 * client/app/pages/admin/preview-deploy.vue.
 */
class PreviewDeployController extends Controller
{
    private const REPO = 'TheRestartProject/restarters.net';
    private const WORKFLOW = 'preview-deploy.yml';

    /**
     * @OA\Get(
     *      path="/api/v2/admin/preview-deploys",
     *      operationId="listPreviewDeploysv2",
     *      tags={"Admin"},
     *      summary="List open PRs that can be preview-deployed (Administrator only)",
     *      security={{"apiToken":{}}},
     *      @OA\Response(response=200, description="Open pull requests",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="prs", type="array", @OA\Items(
     *                  @OA\Property(property="number", type="integer"),
     *                  @OA\Property(property="title", type="string"),
     *                  @OA\Property(property="branch", type="string"),
     *                  @OA\Property(property="author", type="string")
     *              )),
     *              @OA\Property(property="error", type="string", nullable=true)
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index(): JsonResponse
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $token = config('services.github.deploy_pat');

        if (! $token) {
            return response()->json([
                'data' => [
                    'prs' => [],
                    'error' => 'GITHUB_DEPLOY_PAT is not configured. Set it as a Fly secret on restarters-dev.',
                ],
            ]);
        }

        $response = Http::withToken($token)
            ->get('https://api.github.com/repos/'.self::REPO.'/pulls', [
                'state' => 'open',
                'per_page' => 50,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'data' => [
                    'prs' => [],
                    'error' => 'Could not fetch PRs from GitHub (status '.$response->status().')',
                ],
            ]);
        }

        $prs = collect($response->json())->map(fn ($pr) => [
            'number' => $pr['number'],
            'title' => $pr['title'],
            'branch' => $pr['head']['ref'],
            'author' => $pr['user']['login'],
        ])->all();

        return response()->json(['data' => ['prs' => $prs, 'error' => null]]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/preview-deploys",
     *      operationId="triggerPreviewDeployv2",
     *      tags={"Admin"},
     *      summary="Trigger a PR preview deploy to restarters-dev (Administrator only)",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(required={"branch"},
     *          @OA\Property(property="branch", type="string"))),
     *      @OA\Response(response=200, description="Deploy triggered",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="message", type="string")))),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *      @OA\Response(response=502, description="GITHUB_DEPLOY_PAT is not configured, or the GitHub API call failed",
     *          @OA\JsonContent(@OA\Property(property="message", type="string")))
     * )
     */
    public function deploy(Request $request): JsonResponse
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $validated = $request->validate(['branch' => 'required|string']);

        $token = config('services.github.deploy_pat');

        if (! $token) {
            return response()->json(['message' => 'GITHUB_DEPLOY_PAT is not configured.'], 502);
        }

        $response = Http::withToken($token)
            ->post('https://api.github.com/repos/'.self::REPO.'/actions/workflows/'.self::WORKFLOW.'/dispatches', [
                'ref' => 'develop',
                'inputs' => ['branch' => $validated['branch']],
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'GitHub API error: '.$response->status().' — '.$response->body(),
            ], 502);
        }

        return response()->json([
            'data' => [
                'message' => 'Deploy of "'.$validated['branch'].'" triggered. Build takes ~15 minutes.',
            ],
        ]);
    }
}
