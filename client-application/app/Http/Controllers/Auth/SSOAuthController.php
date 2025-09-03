<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

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
     * Handle callback action received from SSO OAuth2 server.
     */
    public function callback(Request $request)
    {
        $this->validateCallbackRequest($request);

        $accessToken = $this->fetchAccessTokenFromSSOServer($request->code);
        $userData = $this->fetchUserInfoFromSSOServer($accessToken);

        return $this->authenticateUser($userData['email']);
    }

    /**
     * Validate the callback request parameters and state.
     */
    private function validateCallbackRequest(Request $request): void
    {
        $state = $request->session()->pull('state');

        if (!$request->has('code') || !$request->has('state')) {
            throw new InvalidArgumentException('Missing required callback parameters');
        }

        if (strlen($state) === 0 || $state !== $request->state) {
            throw new InvalidArgumentException('Invalid state value');
        }
    }

    /**
     * Exchange authorization code for access token.
     */
    private function fetchAccessTokenFromSSOServer(string $code): string
    {
        $tokenResponse = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->callbackUrl,
            'code'          => $code,
        ]);

        if ($tokenResponse->failed()) {
            throw new RuntimeException('Token exchange failed', 400);
        }

        return $tokenResponse->json()['access_token'] ?? throw new RuntimeException('Access token not found in response');
    }

    /**
     * Fetch user information from SSO server.
     */
    private function fetchUserInfoFromSSOServer(string $accessToken): array
    {
        $userResponse = Http::withToken($accessToken)->get($this->userInfoUrl);

        if ($userResponse->failed()) {
            throw new RuntimeException('Failed to fetch user info', 400);
        }

        $userData = $userResponse->json();

        if (!isset($userData['email'])) {
            throw new RuntimeException('User email not found in response');
        }

        return $userData;
    }

    /**
     * Authenticate user or return appropriate response.
     */
    private function authenticateUser(string $email): JsonResponse
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            Auth::login($user);
            return response()->json([
                'message' => 'User authenticated successfully',
                'user'    => $user,
            ]);
        }

        return response()->json([
            'message' => 'User is not registered in this system',
            'email'   => $email,
        ], 404);
    }
}
