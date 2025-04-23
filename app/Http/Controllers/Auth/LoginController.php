<?php

namespace App\Http\Controllers\Auth;

use App\Providers\AppServiceProvider;
use Illuminate\View\View;
use App\Device;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Party;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Msurguy\Honeypot\Honeypot;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = AppServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['index', 'logout']);
    }

    /**
     * Override login from AuthenticateUsers
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            \Cookie::queue(\Cookie::make('authenticated', $request->email, config('session.lifetime'), null, config('session.domain')));

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Override validateLogin from AuthenticateUsers
     */
    protected function validateLogin(Request $request): void
    {
        if (env('HONEYPOT_DISABLE', false)) {
            // This is used in Playwright testing where we get many requests in a short time.
            // TODO There is probably a better place to put this code.
            app('honeypot')->disable();
        }

        $this->validate($request, [
            $this->username() => 'required|email',
            'password' => 'required|string',
            'my_name'   => 'honeypot',
            'my_time'   => 'required|honeytime:1',
        ]);
    }

    /**
     * Override showLoginForm from AuthenticateUsers
     */
    public function showLoginForm(): View
    {
        $stats = Fixometer::loginRegisterStats();

        $deviceCount = array_key_exists(0, $stats['device_count_status']) ? $stats['device_count_status'][0]->counter : 0;

        return view('auth.login', [
          'co2Total' => $stats['waste_stats'][0]->powered_footprint + $stats['waste_stats'][0]->unpowered_footprint,
          'wasteTotal' => $stats['waste_stats'][0]->powered_waste + $stats['waste_stats'][0]->unpowered_waste,
          'partiesCount' => count($stats['allparties']),
          'deviceCount' => $deviceCount,
        ]);
    }
}
