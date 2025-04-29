<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomApiTokenAuth
{
    /**
     * Handle an incoming request.
     * This middleware will try multiple methods of authentication to ensure API requests succeed.
     */
    public function handle(Request $request, Closure $next)
    {
        // STEP 1: Log detailed request information
        Log::debug("API Request", [
            'path' => $request->path(),
            'method' => $request->method(),
            'cookies' => array_keys($request->cookies->all()),
            'has_api_token_cookie' => $request->hasCookie('restarters_apitoken'),
            'cookie_value_length' => $request->cookie('restarters_apitoken') ? strlen($request->cookie('restarters_apitoken')) : 0,
            'has_session_cookie' => $request->hasCookie(config('session.cookie')),
            'has_authorization_header' => $request->hasHeader('Authorization'),
            'has_query_token' => $request->has('api_token')
        ]);
        
        // STEP 2: Check if the user is already authenticated via session
        if (Auth::check()) {
            $user = Auth::user();
            Log::debug("User already authenticated via session", ['user_id' => $user->id]);
            
            // Make sure the Authorization header is set for downstream middleware/guards
            if ($user->api_token) {
                $request->headers->set('Authorization', 'Bearer ' . $user->api_token);
                return $next($request);
            }
        }
        
        // STEP 3: Try to authenticate using various methods
        $token = $this->extractToken($request);
        
        if ($token) {
            $user = User::where('api_token', $token)->first();
            
            if ($user) {
                // Authenticate the user
                Auth::login($user);
                
                // Set Authorization header for downstream middleware
                $request->headers->set('Authorization', 'Bearer ' . $token);
                
                Log::debug("API Authentication successful", [
                    'user_id' => $user->id,
                    'token_source' => $this->tokenSource
                ]);
                
                return $next($request);
            } else {
                Log::warning("Invalid API token", [
                    'token_length' => strlen($token),
                    'token_source' => $this->tokenSource
                ]);
            }
        }
        
        // STEP 4: If we've reached this point, authentication failed
        Log::warning("API Authentication failed", [
            'path' => $request->path(),
            'attempted_methods' => [
                'session' => Auth::check(),
                'cookie' => $request->hasCookie('restarters_apitoken'),
                'header' => $request->hasHeader('Authorization'),
                'query' => $request->has('api_token'),
            ]
        ]);
        
        // For AJAX or API requests, return a JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'debug' => [
                    'token_source_tried' => $this->tokenSource,
                    'session_auth' => Auth::check(),
                    'cookie_auth_attempted' => $request->hasCookie('restarters_apitoken'),
                    'header_auth_attempted' => $request->hasHeader('Authorization'),
                    'query_auth_attempted' => $request->has('api_token'),
                ]
            ], 401);
        }
        
        // For web requests, redirect to login page
        throw new AuthenticationException(
            'Unauthenticated.',
            ['api'],
            $request->expectsJson() ? null : route('login')
        );
    }
    
    /**
     * The source of the token that was used
     */
    private $tokenSource = 'none';
    
    /**
     * Try all possible methods to extract an API token from the request
     */
    private function extractToken(Request $request)
    {
        // Method 1: Check for token in query string
        $token = $request->query('api_token');
        if (!empty($token)) {
            $this->tokenSource = 'query';
            Log::debug("Found token in query string", ['length' => strlen($token)]);
            return $token;
        }
        
        // Method 2: Check for token in request body
        $token = $request->input('api_token');
        if (!empty($token)) {
            $this->tokenSource = 'body';
            Log::debug("Found token in request body", ['length' => strlen($token)]);
            return $token;
        }
        
        // Method 3: Check for token in Authorization header
        $bearerToken = $request->bearerToken();
        if (!empty($bearerToken)) {
            $this->tokenSource = 'bearer';
            Log::debug("Found token in bearer header", ['length' => strlen($bearerToken)]);
            return $bearerToken;
        }
        
        // Method 4: Check for token in regular Authorization header
        $header = $request->header('Authorization');
        if (strpos($header, 'Bearer ') === 0) {
            $token = substr($header, 7);
            if (!empty($token)) {
                $this->tokenSource = 'auth_header';
                Log::debug("Found token in Authorization header", ['length' => strlen($token)]);
                return $token;
            }
        }
        
        // Method 5: Check for token in custom header
        $token = $request->header('X-API-TOKEN');
        if (!empty($token)) {
            $this->tokenSource = 'custom_header';
            Log::debug("Found token in X-API-TOKEN header", ['length' => strlen($token)]);
            return $token;
        }
        
        // Method 6: Check for token in cookie
        $token = $request->cookie('restarters_apitoken');
        if (!empty($token)) {
            $this->tokenSource = 'cookie';
            Log::debug("Found token in cookie", ['length' => strlen($token)]);
            return $token;
        }
        
        // Method 7: If user is already authenticated, use their API token
        if (Auth::check()) {
            $user = Auth::user();
            if (!empty($user->api_token)) {
                $this->tokenSource = 'session_user';
                Log::debug("Using token from authenticated user", ['user_id' => $user->id]);
                return $user->api_token;
            }
        }
        
        Log::warning("No token found in request");
        return null;
    }
} 