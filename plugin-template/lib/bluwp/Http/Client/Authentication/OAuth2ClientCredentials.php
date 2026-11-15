<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client\Authentication;

use Forouzandea\Bluwp\Http\Client\Contracts\AccessTokenCache;
use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\Contracts\RequestAuthenticator;
use Forouzandea\Bluwp\Http\Client\Exceptions\HttpException;
use Forouzandea\Bluwp\Http\Client\HttpRequest;

final class OAuth2ClientCredentials implements RequestAuthenticator
{
    private string $cacheKey;

    /** @param string[] $scopes */
    public function __construct(
        private HttpClient $client,
        private AccessTokenCache $cache,
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private array $scopes = []
    ) {
        if (filter_var($tokenUrl, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($tokenUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \InvalidArgumentException(__('A valid HTTPS OAuth token URL is required.', BLUWP));
        }
        if ($clientId === '' || $clientSecret === '' || preg_match('/[\x00-\x1F\x7F]/', $clientId . $clientSecret)) {
            throw new \InvalidArgumentException(__('Invalid OAuth client credentials.', BLUWP));
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope) || $scope === '' || preg_match('/[\x00-\x20\x7F]/', $scope)) {
                throw new \InvalidArgumentException(__('Invalid OAuth scope.', BLUWP));
            }
        }
        $this->cacheKey = 'bluwp_oauth_' . hash('sha256', $tokenUrl . "\0" . $clientId . "\0" . implode(' ', $scopes));
    }

    public function authenticate(HttpRequest $request): HttpRequest
    {
        $token = $this->cache->get($this->cacheKey);
        if ($token === null) {
            $token = $this->requestToken();
        }
        return (new BearerAuthentication($token))->authenticate($request);
    }

    private function requestToken(): string
    {
        $parameters = ['grant_type' => 'client_credentials'];
        if ($this->scopes !== []) {
            $parameters['scope'] = implode(' ', $this->scopes);
        }
        $request = (new BasicAuthentication($this->clientId, $this->clientSecret))
            ->authenticate(HttpRequest::postForm($this->tokenUrl, $parameters));
        $response = $this->client->send($request);
        if (!$response->successful()) {
            throw new HttpException(__('The OAuth token request failed.', BLUWP));
        }
        try {
            $payload = json_decode($response->body, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new HttpException(__('The OAuth token response is invalid.', BLUWP));
        }
        $token = is_array($payload) ? ($payload['access_token'] ?? null) : null;
        $type = is_array($payload) ? ($payload['token_type'] ?? 'Bearer') : null;
        if (!is_string($token) || $token === '' || preg_match('/[\x00-\x20\x7F]/', $token)
            || !is_string($type) || strcasecmp($type, 'Bearer') !== 0) {
            throw new HttpException(__('The OAuth token response is invalid.', BLUWP));
        }
        $expires = $payload['expires_in'] ?? 3600;
        if (!is_int($expires) || $expires < 1) {
            throw new HttpException(__('The OAuth token response is invalid.', BLUWP));
        }
        $this->cache->put($this->cacheKey, $token, max(1, $expires - min(60, intdiv($expires, 10))));
        return $token;
    }
}
