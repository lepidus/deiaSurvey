<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;

class OrcidClient
{
    private const OAUTH_SESSION_KEY = 'deiaOrcidOAuth';
    public const ORCID_URL = 'https://orcid.org/';
    public const ORCID_URL_SANDBOX = 'https://sandbox.orcid.org/';
    public const ORCID_API_URL_PUBLIC = 'https://pub.orcid.org/';
    public const ORCID_API_URL_PUBLIC_SANDBOX = 'https://pub.sandbox.orcid.org/';
    public const ORCID_API_URL_MEMBER = 'https://api.orcid.org/';
    public const ORCID_API_URL_MEMBER_SANDBOX = 'https://api.sandbox.orcid.org/';
    public const ORCID_API_SCOPE_PUBLIC = '/authenticate';
    public const ORCID_API_SCOPE_MEMBER = '/activities/update';

    private $plugin;
    private $contextId;
    private $orcidConfiguration;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;

        $orcidConfiguration = new OrcidConfiguration();
        $this->orcidConfiguration = $orcidConfiguration->getOrcidConfiguration($contextId);
    }

    public function requestOrcid(string $code, array $redirectParams)
    {
        if (is_null($this->orcidConfiguration)) {
            return null;
        }

        $httpClient = Application::get()->getHttpClient();

        $tokenUrl = $this->orcidConfiguration['apiPath'] . 'oauth/token';
        $requestHeaders = ['Accept' => 'application/json'];
        $requestData = [
            'client_id' => $this->orcidConfiguration['clientId'],
            'client_secret' => $this->orcidConfiguration['clientSecret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectUrl($redirectParams),
        ];

        $response = $httpClient->request(
            'POST',
            $tokenUrl,
            [
                'headers' => $requestHeaders,
                'form_params' => $requestData,
            ]
        );

        $responseJson = json_decode($response->getBody(), true);
        return $responseJson['orcid'] ?? null;
    }

    public function buildOAuthUrl($redirectParams)
    {
        if (is_null($this->orcidConfiguration)) {
            return '';
        }

        $request = Application::get()->getRequest();

        if ($this->isMemberApiEnabled($this->contextId)) {
            $scope = self::ORCID_API_SCOPE_MEMBER;
        } else {
            $scope = self::ORCID_API_SCOPE_PUBLIC;
        }

        $state = bin2hex(random_bytes(32));
        $request->getSession()->put(self::OAUTH_SESSION_KEY, [
            'stateHash' => hash('sha256', $state),
            'authorId' => (int) $redirectParams['authorId'],
            'tokenHash' => hash('sha256', $redirectParams['authorToken']),
            'contextId' => $this->contextId,
            'expiresAt' => now()->addMinutes(10)->timestamp,
        ]);

        return $this->getOauthPath() . 'authorize?' . http_build_query(
            [
                'client_id' => $this->orcidConfiguration['clientId'],
                'response_type' => 'code',
                'scope' => $scope,
                'redirect_uri' => $this->getRedirectUrl($redirectParams),
                'state' => $state,
            ]
        );
    }

    public function consumeOAuthState($state, int $authorId, string $authorToken): bool
    {
        $pending = Application::get()->getRequest()->getSession()->pull(self::OAUTH_SESSION_KEY);
        return is_string($state) && is_array($pending)
            && ($pending['authorId'] ?? null) === $authorId
            && ($pending['contextId'] ?? null) === $this->contextId
            && ($pending['expiresAt'] ?? 0) > now()->timestamp
            && hash_equals($pending['tokenHash'], hash('sha256', $authorToken))
            && hash_equals($pending['stateHash'], hash('sha256', $state));
    }

    private function getRedirectUrl(array $params): string
    {
        $request = Application::get()->getRequest();
        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'deiaQuestionnaire',
            'orcidVerify',
            null,
            $params
        );
    }

    private function isMemberApiEnabled()
    {
        $apiUrl = $this->orcidConfiguration['apiPath'];
        return ($apiUrl == self::ORCID_API_URL_MEMBER || $apiUrl == self::ORCID_API_URL_MEMBER_SANDBOX);
    }

    private function getOauthPath()
    {
        return $this->getOrcidUrl() . 'oauth/';
    }

    private function getOrcidUrl()
    {
        $apiPath = $this->orcidConfiguration['apiPath'];
        if ($apiPath == self::ORCID_API_URL_PUBLIC || $apiPath == self::ORCID_API_URL_MEMBER) {
            return self::ORCID_URL;
        } else {
            return self::ORCID_URL_SANDBOX;
        }
    }
}
