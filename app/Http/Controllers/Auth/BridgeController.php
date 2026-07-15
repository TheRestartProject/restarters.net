<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\SsoTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * GET /auth/bridge?ticket=…&redirect=…
 *
 * The token-authenticated SPA cannot carry an Authorization header on a
 * top-level navigation, but Discourse SSO (GET /discourse/sso, web+auth) and
 * MediaWiki silent login both need a real Laravel web session in the browser.
 * The SPA obtains a one-time ticket (POST /api/v2/auth/sso-ticket) and sends
 * the browser here; we establish the web session — firing the Login event, so
 * LogInToWiki queues the mw_* cookies onto this first-party response — and
 * redirect onward to an allowlisted target.
 */
class BridgeController extends Controller
{
    public function bridge(Request $request): RedirectResponse
    {
        $redirect = $this->safeRedirect($request->query('redirect'));

        // Target the web guard explicitly: the session is what we are here to
        // establish, regardless of what the default guard has been set to.
        if (Auth::guard('web')->check()) {
            // Already have a web session (e.g. second bridge hop); no ticket needed.
            return redirect($redirect);
        }

        $user = SsoTicket::consume($request->query('ticket'));

        if (! $user) {
            // Invalid/expired ticket: bounce to the SPA login, preserving the
            // intended destination so the user can retry after logging in.
            return redirect(config('restarters.frontend_url').'/login?redirect='.urlencode($redirect));
        }

        // Fires Illuminate\Auth\Events\Login → LogSuccessfulLogin + LogInToWiki.
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect($redirect);
    }

    /**
     * Only ever redirect to: this host's Discourse SSO entrypoint, the wiki,
     * Discourse itself, or the SPA. Anything else falls back to the SPA —
     * this is what stops /auth/bridge being an open redirect.
     */
    private function safeRedirect(?string $target): string
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        if (! $target) {
            return $frontend;
        }

        $allowedPrefixes = array_filter([
            url('/discourse/sso'),
            '/discourse/sso',
            config('restarters.wiki.base_url'),
            config('services.discourse.url'),
            $frontend,
        ]);

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($target, $prefix)) {
                return $target;
            }
        }

        return $frontend;
    }
}
