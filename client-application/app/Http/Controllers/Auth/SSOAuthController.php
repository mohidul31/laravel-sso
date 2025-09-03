<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SSOAuthController extends Controller
{
    /**
     * OAuth2 configuration
     */
    private string $clientId;
    private string $clientSecret;
    private string $callbackUrl;
    private string $authorizationUrl;
    private string $tokenUrl;
    private string $userInfoUrl;

    public function __construct()
    {
        $this->clientId         = env('OAUTH_CLIENT_ID');
        $this->clientSecret     = env('OAUTH_CLIENT_SECRET');
        $this->callbackUrl      = env('OAUTH_CLIENT_CALLBACK');

        //SSO Server URLs
        $this->authorizationUrl = env("OAUTH_SERVER_URI") . '/oauth/authorize';
        $this->tokenUrl         = env("OAUTH_SERVER_URI") . '/oauth/token';
        $this->userInfoUrl      = env("OAUTH_SERVER_URI") . '/api/userinfo';
    }

    /**
     * Redirect user to the SSO OAuth2 server for authorization.
     */
    public function redirect(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $queryParams = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->callbackUrl,
            'response_type' => 'code',
            'scope'         => '',
            'state'         => $state,
        ]);

        return redirect($this->authorizationUrl . '?' . $queryParams);
    }

    /**
     * Handle callback from SSO OAuth2 server.
     */
    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class,
            'Invalid state value.'
        );

        $tokenResponse = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->callbackUrl,
            'code'          => $request->code,
        ]);

        if ($tokenResponse->failed()) {
            return response()->json(['error' => 'Token exchange failed'], 400);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Fetch user info (email) from OAuth2 SSO server
        $userResponse = Http::withToken($accessToken)->get($this->userInfoUrl);

        if ($userResponse->failed()) {
            return response()->json(['error' => 'Failed to fetch user info'], 400);
        }

        $userData = $userResponse->json();

        // Check if user exists in local database
        $user = User::where('email', $userData['email'])->first();

        if ($user) {
            // Log the user in
            Auth::login($user);

            return response()->json([
                'message' => 'User authenticated successfully',
                'user' => $user,
            ]);
        } else {
            // User not registered
            return response()->json([
                'message' => 'User is not registered in this system',
                'email' => $userData['email'],
            ], 404);
        }
    }
}
