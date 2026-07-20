<?php

namespace App\Http\Controllers\API;

use App\Events\PasswordChanged;
use App\Events\UserRegistered;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Http\Controllers\Controller;
use App\Invite;
use App\Notifications\AdminNewUser;
use App\Notifications\NewGroupMember;
use App\Notifications\ResetPassword;
use App\Party;
use App\User;
use App\UserGroups;
use App\WikiSyncStatus;
use Carbon\Carbon;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token-based auth endpoints for the Nuxt SPA (and, later, the Capacitor app).
 *
 * These run in the plain "api" middleware group: no session, no CSRF. Bearer
 * tokens are not ambient credentials, so CSRF protection is inapplicable here;
 * DualGuardAuthEndpointsTest pins that login works without a CSRF token.
 *
 * We deliberately do NOT fire Illuminate\Auth\Events\Login here: that event
 * triggers LogInToWiki, whose whole purpose is to queue MediaWiki cookies onto
 * the response — but a cross-origin XHR response's Set-Cookie is discarded by
 * the browser, so it would be a synchronous MediaWiki round-trip for nothing.
 * Wiki login (and the Login event) happens at GET /auth/bridge instead, during
 * a top-level navigation where cookies land first-party.
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v2/auth/login",
     *      operationId="loginv2",
     *      tags={"Auth"},
     *      summary="Log in with email and password, returning a bearer token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="invite_code", type="string", nullable=true, description="Shareable group/event invite code being claimed"),
     *              @OA\Property(property="invite_type", type="string", nullable=true, enum={"group","event"}),
     *              @OA\Property(property="invite_hash", type="string", nullable=true, description="Invite hash from an invite email"),
     *              @OA\Property(property="my_name", type="string", nullable=true, description="Honeypot; must be empty")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Authenticated",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="token", type="string"),
     *                  @OA\Property(property="user", type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *      @OA\Response(response=429, ref="#/components/responses/TooManyRequests")
     * )
     */
    public function loginv2(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            // Honeypot: real users never see or fill this field.
            'my_name' => 'nullable|prohibited',
            'invite_code' => 'nullable|string',
            'invite_type' => 'nullable|string|in:group,event|required_with:invite_code',
            'invite_hash' => 'nullable|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (! Auth::guard('web')->validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        $this->recordLogin($user);
        $invite = $this->claimInvitesFor($user, $request);

        return response()->json([
            'data' => [
                'token' => $user->createToken('spa')->plainTextToken,
                'user' => self::userSummary($user),
                'invite' => $invite,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/logout",
     *      operationId="logoutv2",
     *      tags={"Auth"},
     *      summary="Revoke the current token and sync logout to Discourse/Wiki",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Logged out",
     *          @OA\JsonContent(@OA\Property(property="message", type="string"))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function logoutv2(Request $request): JsonResponse
    {
        $user = $request->user();

        // Sanctum-issued tokens are revoked; legacy api_token credentials have
        // no revocation (they are not per-session), so there is nothing to do
        // for them here.
        $token = $user->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        // Fires LogOutOfWiki (cookie forget; harmless on XHR) and the queued
        // Discourse force-logout listener.
        event(new Logout('web', $user));

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/register",
     *      operationId="registerv2",
     *      tags={"Auth"},
     *      summary="Register a new user, returning a bearer token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation","age","country","consent_gdpr","consent_future_data"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password"),
     *              @OA\Property(property="age", type="string"),
     *              @OA\Property(property="country", type="string", description="Country code"),
     *              @OA\Property(property="city", type="string", nullable=true),
     *              @OA\Property(property="gender", type="string", nullable=true),
     *              @OA\Property(property="skills", type="array", nullable=true, @OA\Items(type="integer")),
     *              @OA\Property(property="newsletter", type="boolean", nullable=true),
     *              @OA\Property(property="invites", type="boolean", nullable=true),
     *              @OA\Property(property="consent_gdpr", type="boolean"),
     *              @OA\Property(property="consent_future_data", type="boolean"),
     *              @OA\Property(property="invite_code", type="string", nullable=true),
     *              @OA\Property(property="invite_type", type="string", nullable=true, enum={"group","event"}),
     *              @OA\Property(property="invite_hash", type="string", nullable=true),
     *              @OA\Property(property="my_name", type="string", nullable=true, description="Honeypot; must be empty")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Registered",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="token", type="string"),
     *                  @OA\Property(property="user", type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *      @OA\Response(response=429, ref="#/components/responses/TooManyRequests")
     * )
     */
    public function registerv2(Request $request): JsonResponse
    {
        // Mirrors the rules of the Blade registration form (UserController::
        // postRegister), minus honeytime (which needs a server-rendered form
        // field) and plus the stateless invite parameters.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'age' => 'required',
            'country' => 'required',
            'city' => 'nullable|string',
            'gender' => 'nullable|string',
            'skills' => 'nullable|array',
            'newsletter' => 'nullable|boolean',
            'invites' => 'nullable|boolean',
            'consent_gdpr' => 'required|accepted',
            'consent_future_data' => 'required|accepted',
            'my_name' => 'nullable|prohibited',
            'invite_code' => 'nullable|string',
            'invite_type' => 'nullable|string|in:group,event|required_with:invite_code',
            'invite_hash' => 'nullable|string',
        ]);

        $skills = $request->input('skills');

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
            'country_code' => $request->input('country'),
            'location' => $request->input('city'),
            'gender' => $request->input('gender'),
            'age' => $request->input('age'),
            'calendar_hash' => Str::random(15),
            'username' => '',
        ]);

        // role excluded from $fillable (security); set via direct assignment.
        $user->role = Fixometer::skillsDetermineRole($skills);
        $user->generateAndSetUsername();

        $user->recordConsent(['consent_gdpr', 'consent_future_data'], $request->boolean('newsletter'));

        if ($request->boolean('invites')) {
            $user->invites = 1;
        }

        if ($request->filled('city')) {
            $geocoded = app(Geocoder::class)->geocode("{$request->input('city')}, {$request->input('country')}");
            if (! empty($geocoded)) {
                $user->latitude = $geocoded['latitude'];
                $user->longitude = $geocoded['longitude'];
            }
        }

        // Wiki account creation is attempted when the Login event next fires,
        // i.e. at the first GET /auth/bridge navigation (Talk/Wiki link-out).
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();

        $notify_users = Fixometer::usersWhoHavePreference('admin-new-user');
        Notification::send($notify_users, new AdminNewUser([
            'id' => $user->id,
            'name' => $user->name,
        ]));

        if (! empty($skills)) {
            $user->skillsold()->sync($skills);
        }

        $invite = $this->claimInvitesFor($user, $request);

        event(new UserRegistered($user));

        $this->recordLogin($user);

        return response()->json([
            'data' => [
                'token' => $user->createToken('spa')->plainTextToken,
                'user' => self::userSummary($user),
                'invite' => $invite,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/password/forgot",
     *      operationId="forgotPasswordv2",
     *      tags={"Auth"},
     *      summary="Send a password recovery email",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email"},
     *              @OA\Property(property="email", type="string", format="email")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Recovery email sent",
     *          @OA\JsonContent(@OA\Property(property="message", type="string"))
     *      ),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *      @OA\Response(response=429, ref="#/components/responses/TooManyRequests")
     * )
     */
    public function forgotPasswordv2(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            // Parity with the Blade flow, which also discloses whether the
            // account exists (passwords.user message).
            throw ValidationException::withMessages([
                'email' => [__('passwords.user')],
            ]);
        }

        $user->update([
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
        ]);

        // /user/reset remains a Laravel URL: it serves the Blade page today
        // and becomes a redirector into the SPA at cutover, so emailed links
        // keep working throughout.
        $user->notify(new ResetPassword([
            'url' => url('/user/reset?recovery='.$user->recovery),
        ]));

        return response()->json(['message' => __('passwords.sent')]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/password/reset",
     *      operationId="resetPasswordv2",
     *      tags={"Auth"},
     *      summary="Reset a password using an emailed recovery token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"recovery","password","password_confirmation"},
     *              @OA\Property(property="recovery", type="string"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Password updated",
     *          @OA\JsonContent(@OA\Property(property="message", type="string"))
     *      ),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *      @OA\Response(response=429, ref="#/components/responses/TooManyRequests")
     * )
     */
    public function resetPasswordv2(Request $request): JsonResponse
    {
        $request->validate([
            'recovery' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findByValidRecoveryToken($request->input('recovery'));

        if (! $user) {
            throw ValidationException::withMessages([
                'recovery' => [__('passwords.token')],
            ]);
        }

        $oldPassword = $user->password;
        $user->update([
            'password' => Hash::make($request->input('password')),
            // Rotate the recovery token so the just-used reset link cannot be
            // replayed within its 24h window (an intercepted/forwarded link
            // must be single-use). Mirrors UserController::updateMyPasswordv2.
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
        ]);

        event(new PasswordChanged($user, $oldPassword));

        return response()->json(['message' => __('passwords.updated')]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/auth/password/recovery/{token}",
     *      operationId="recoveryInfov2",
     *      tags={"Auth"},
     *      summary="Check a password recovery token before the reset form is submitted",
     *      description="Used by the reset-password page on load, mirroring the Blade recovery page which validated the token up front and pre-filled a disabled account-email field.",
     *      @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="Token validity and, when valid, the owning account's email",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="valid", type="boolean"),
     *                  @OA\Property(property="email", type="string", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(response=429, ref="#/components/responses/TooManyRequests")
     * )
     */
    public function recoveryInfov2(string $token): JsonResponse
    {
        $user = User::findByValidRecoveryToken($token);

        return response()->json([
            'data' => [
                'valid' => $user !== null,
                'email' => $user->email ?? null,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/auth/email-available",
     *      operationId="emailAvailablev2",
     *      tags={"Auth"},
     *      summary="Check whether an email address is available for registration",
     *      @OA\Parameter(name="email", in="query", required=true, @OA\Schema(type="string", format="email")),
     *      @OA\Response(
     *          response=200,
     *          description="Availability",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="available", type="boolean"),
     *                  @OA\Property(property="message", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function emailAvailablev2(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);

        $available = ! User::where('email', $request->input('email'))->exists();

        return response()->json([
            'data' => [
                'available' => $available,
                'message' => $available ? 'Email is available' : __('auth.email_address_validation'),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/invites/claim",
     *      operationId="claimInvitev2",
     *      tags={"Auth"},
     *      summary="Claim a shareable invite code or emailed invite hash for the current user",
     *      description="Stateless replacement for the session-bridged shareable-link flow (GET /group/invite/{code}, /party/invite/{code}) and the AcceptUserInvites middleware.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="invite_code", type="string", nullable=true),
     *              @OA\Property(property="invite_type", type="string", nullable=true, enum={"group","event"}),
     *              @OA\Property(property="invite_hash", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Invite applied (or already a member)",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="type", type="string", nullable=true, enum={"group","event"}),
     *                  @OA\Property(property="id", type="integer", nullable=true),
     *                  @OA\Property(property="already_member", type="boolean", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, description="Data consent required"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function claimInvitev2(Request $request): JsonResponse
    {
        $request->validate([
            'invite_code' => 'nullable|string|required_without:invite_hash',
            'invite_type' => 'nullable|string|in:group,event|required_with:invite_code',
            'invite_hash' => 'nullable|string|required_without:invite_code',
        ]);

        $result = $this->claimInvitesFor($request->user(), $request);

        if (! $result) {
            abort(404, 'Unknown invite code or hash.');
        }

        return response()->json(['data' => $result]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/sso-ticket",
     *      operationId="ssoTicketv2",
     *      tags={"Auth"},
     *      summary="Issue a one-time ticket for establishing a web session at GET /auth/bridge",
     *      description="Used before top-level navigations to Talk (Discourse SSO) or the Wiki, which need a Laravel web session. Tickets are single-use and expire after 60 seconds.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Ticket issued",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="ticket", type="string"),
     *                  @OA\Property(property="bridge_url", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public function ssoTicketv2(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'ticket' => \App\SsoTicket::issue($request->user()),
                'bridge_url' => url('/auth/bridge'),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/auth/consent",
     *      operationId="consentv2",
     *      tags={"Auth"},
     *      summary="Record outstanding data consents (and profile basics) for the current user",
     *      description="Port of the logged-in branch of the Blade registration form, which doubles as the consent-completion form for users gated by VerifyUserConsent.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"age","country","consent_gdpr","consent_past_data","consent_future_data"},
     *              @OA\Property(property="age", type="string"),
     *              @OA\Property(property="country", type="string"),
     *              @OA\Property(property="city", type="string", nullable=true),
     *              @OA\Property(property="gender", type="string", nullable=true),
     *              @OA\Property(property="consent_gdpr", type="boolean"),
     *              @OA\Property(property="consent_past_data", type="boolean"),
     *              @OA\Property(property="consent_future_data", type="boolean"),
     *              @OA\Property(property="newsletter", type="boolean", nullable=true, description="Opt in to the newsletter; omitted/false leaves the existing preference unchanged")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Consents recorded; returns the refreshed session payload",
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
    public function consentv2(Request $request): JsonResponse
    {
        $request->validate([
            'age' => 'required',
            'country' => 'required',
            'city' => 'nullable|string',
            'gender' => 'nullable|string',
            'consent_gdpr' => 'required|accepted',
            'consent_past_data' => 'required|accepted',
            'consent_future_data' => 'required|accepted',
            'newsletter' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        $user->country_code = $request->input('country');
        $user->age = $request->input('age');

        if ($request->filled('city')) {
            $user->location = $request->input('city');
            $geocoded = app(Geocoder::class)->geocode("{$request->input('city')}, {$request->input('country')}");
            if (! empty($geocoded)) {
                $user->latitude = $geocoded['latitude'];
                $user->longitude = $geocoded['longitude'];
            }
        }

        if ($request->filled('gender')) {
            $user->gender = $request->input('gender');
        }

        $user->recordConsent(['consent_gdpr', 'consent_past_data', 'consent_future_data'], $request->boolean('newsletter'));
        $user->save();

        return response()->json(['data' => SessionController::sessionPayload($user->fresh())]);
    }

    /**
     * The LogSuccessfulLogin listener's behaviour, invoked directly because we
     * intentionally do not fire the Login event on the XHR path (see class
     * docblock).
     */
    private function recordLogin(User $user): void
    {
        $user->last_login_at = Carbon::now()->toDateTimeString();
        $user->number_of_logins += 1;
        $user->save();
    }

    private static function userSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Apply any invite identifiers carried on the request to $user, statelessly.
     * Returns ['type' =>, 'id' =>, 'already_member' =>] or null if nothing claimed.
     */
    private function claimInvitesFor(User $user, Request $request): ?array
    {
        if ($request->filled('invite_code')) {
            return $this->claimShareableCode($user, $request->input('invite_code'), $request->input('invite_type'));
        }

        if ($request->filled('invite_hash')) {
            return $this->claimInviteHash($user, $request->input('invite_hash'));
        }

        return null;
    }

    private function claimShareableCode(User $user, string $code, string $type): ?array
    {
        if ($type === 'group') {
            $group = Group::where('shareable_code', $code)->first();

            if (! $group) {
                return null;
            }

            if ($group->isVolunteer($user->id)) {
                return ['type' => 'group', 'id' => $group->idgroups, 'already_member' => true];
            }

            UserGroups::updateOrCreate([
                'user' => $user->id,
                'group' => $group->idgroups,
            ], [
                'status' => '1',
                'role' => 4,
            ]);

            return ['type' => 'group', 'id' => $group->idgroups, 'already_member' => false];
        }

        $party = Party::where('shareable_code', $code)->first();

        if (! $party) {
            return null;
        }

        if ($party->isVolunteer($user->id)) {
            return ['type' => 'event', 'id' => $party->idevents, 'already_member' => true];
        }

        EventsUsers::updateOrCreate([
            'user' => $user->id,
            'event' => $party->idevents,
        ], [
            'status' => '1',
            'role' => 4,
        ]);

        return ['type' => 'event', 'id' => $party->idevents, 'already_member' => false];
    }

    private function claimInviteHash(User $user, string $hash): ?array
    {
        $acceptance = Invite::where('hash', $hash)->first();

        if (! $acceptance) {
            return null;
        }

        if ($acceptance->type === 'event') {
            $already = Party::find($acceptance->record_id)?->isVolunteer($user->id) ?? false;
            if (! $already) {
                EventsUsers::updateOrCreate([
                    'user' => $user->id,
                    'event' => $acceptance->record_id,
                ], [
                    'status' => '1',
                    'role' => 4,
                ]);
            }
            $acceptance->delete();

            return ['type' => 'event', 'id' => (int) $acceptance->record_id, 'already_member' => $already];
        }

        $group = Group::find($acceptance->record_id);
        $already = $group?->isVolunteer($user->id) ?? false;

        if (! $already && $group) {
            UserGroups::updateOrCreate([
                'user' => $user->id,
                'group' => $group->idgroups,
            ], [
                'status' => '1',
                'role' => 4,
            ]);

            // Parity with the emailed-invite flow: let hosts know someone joined.
            $group_hosts = $group->membersHosts();
            if ($group_hosts->count()) {
                Notification::send($group_hosts->get(), new NewGroupMember([
                    'user_name' => $user->name,
                    'group_name' => $group->name,
                    'group_url' => url('/group/view/'.$group->idgroups),
                ]));
            }
        }
        $acceptance->delete();

        return ['type' => 'group', 'id' => (int) $acceptance->record_id, 'already_member' => $already];
    }
}
