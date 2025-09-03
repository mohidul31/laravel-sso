<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function __construct()
    {
        $this->clientId         = env('OAUTH_CLIENT_ID');
        $this->clientSecret     = env('OAUTH_CLIENT_SECRET');
        $this->callbackUrl      = env('OAUTH_CLIENT_CALLBACK');

        $this->authorizationUrl = env("OAUTH_SERVER_URI") . '/oauth/authorize';
        $this->tokenUrl         = env("OAUTH_SERVER_URI") . '/oauth/token';
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

        return $tokenResponse->json();
    }
}
