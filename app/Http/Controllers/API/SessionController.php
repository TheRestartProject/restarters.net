<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The SPA's bootstrap endpoint: one call returning the current user (or null),
 * client-relevant config, and UI flags. Replaces every window.* global,
 * Auth::user() navbar read and env() value previously inlined into Blade.
 */
class SessionController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/session",
     *      operationId="getSessionv2",
     *      tags={"Session"},
     *      summary="Client bootstrap: current user, config and flags",
     *      description="Callable anonymously (guest bootstrap) - the route carries no auth middleware, so 'user' is simply null when there is no valid token.",
     *      @OA\Response(
     *          response=200,
     *          description="Session context",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user", type="object", nullable=true,
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string"),
     *                      @OA\Property(property="username", type="string", nullable=true),
     *                      @OA\Property(property="avatar_url", type="string", nullable=true),
     *                      @OA\Property(property="role", type="integer"),
     *                      @OA\Property(property="role_name", type="string"),
     *                      @OA\Property(property="language", type="string", nullable=true),
     *                      @OA\Property(property="consent", type="object",
     *                          @OA\Property(property="gdpr", type="boolean"),
     *                          @OA\Property(property="past_data", type="boolean"),
     *                          @OA\Property(property="future_data", type="boolean"),
     *                          @OA\Property(property="given", type="boolean")
     *                      ),
     *                      @OA\Property(property="networks", type="array", @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string")
     *                      ))
     *                  ),
     *                  @OA\Property(property="config", type="object",
     *                      @OA\Property(property="discourse_url", type="string", nullable=true),
     *                      @OA\Property(property="wiki_url", type="string", nullable=true),
     *                      @OA\Property(property="gtm_id", type="string", nullable=true),
     *                      @OA\Property(property="frontend_url", type="string"),
     *                      @OA\Property(property="community_test", type="boolean"),
     *                      @OA\Property(property="branch_banner", type="object", nullable=true,
     *                          @OA\Property(property="label", type="string", nullable=true),
     *                          @OA\Property(property="mailpit_url", type="string", nullable=true)
     *                      )
     *                  ),
     *                  @OA\Property(property="flags", type="object",
     *                      @OA\Property(property="onboarding", type="boolean")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function getSessionv2(): JsonResponse
    {
        return response()->json(['data' => self::sessionPayload(self::currentUser())]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/session",
     *      operationId="patchSessionv2",
     *      tags={"Session"},
     *      summary="Update session preferences (locale)",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="locale", type="string", example="fr-BE")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Updated session context",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user", type="object", nullable=true),
     *                  @OA\Property(property="config", type="object"),
     *                  @OA\Property(property="flags", type="object")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function patchSessionv2(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:'.implode(',', self::availableLocales()),
        ]);

        $user = $request->user();

        if ($request->filled('locale')) {
            $user->language = $request->input('locale');
            $user->save();
        }

        return response()->json(['data' => self::sessionPayload($user)]);
    }

    public static function currentUser(): ?User
    {
        // Session (phpunit actingAs), then SPA bearer token, then legacy token.
        return Auth::user() ?? auth('sanctum')->user() ?? auth('api')->user();
    }

    public static function sessionPayload(?User $user): array
    {
        return [
            'user' => $user ? self::userPayload($user) : null,
            'config' => [
                'discourse_url' => config('services.discourse.url'),
                'wiki_url' => config('restarters.wiki.base_url'),
                'gtm_id' => config('restarters.client.gtm_id'),
                'frontend_url' => config('restarters.frontend_url'),
                'community_test' => (bool) config('restarters.client.community_test'),
                'branch_banner' => config('restarters.client.show_branch_banner') ? [
                    'label' => config('restarters.client.branch_label') ?: config('app.env'),
                    'mailpit_url' => config('restarters.client.mailpit_url'),
                ] : null,
            ],
            'flags' => [
                // Parity with develop: the legacy Blade layout gates the onboarding
                // modal on a $onboarding view variable that is never assigned by any
                // controller there, so the modal (and its /onboarding-complete route)
                // is dead code on develop and no user ever sees it. We match that by
                // always returning false here. The client component
                // (DashboardOnboardingModal.vue) and the completion endpoint
                // (POST /api/v2/users/me/onboarding-complete) are left in place but
                // dormant; to deliberately revive the feature, replace this literal
                // with real gating logic (e.g. $user->number_of_logins < 2).
                'onboarding' => false,
            ],
        ];
    }

    private static function userPayload(User $user): array
    {
        $profile = User::getProfile($user->id);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'avatar_url' => ($profile && $profile->path) ? url('/uploads/thumbnail_'.$profile->path) : null,
            'role' => (int) $user->role,
            'role_name' => optional($user->role()->first())->role,
            'language' => $user->language,
            'consent' => [
                'gdpr' => ! is_null($user->consent_gdpr),
                'past_data' => ! is_null($user->consent_past_data),
                'future_data' => ! is_null($user->consent_future_data),
                'given' => (bool) $user->hasUserGivenConsent(),
            ],
            'networks' => $user->networks->map(function ($network) {
                return [
                    'id' => (int) $network->id,
                    'name' => $network->name,
                ];
            })->values()->all(),
        ];
    }

    public static function availableLocales(): array
    {
        return collect(scandir(base_path('lang')))
            ->filter(fn ($entry) => is_dir(base_path('lang/'.$entry)) && ! str_starts_with($entry, '.'))
            ->values()
            ->all();
    }
}
