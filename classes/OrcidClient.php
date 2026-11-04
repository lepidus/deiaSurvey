<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\plugins\generic\deiaSurvey\classes\OrcidConfiguration;

class OrcidClient
{
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
    private $httpClient;

    public function __construct($plugin, $contextId, ?\GuzzleHttp\ClientInterface $httpClient = null)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->httpClient = $httpClient;

        $orcidConfiguration = new OrcidConfiguration();
        $this->orcidConfiguration = $orcidConfiguration->getOrcidConfiguration($contextId);
    }

    public function requestOrcid(string $code, array $redirectParams = [], string $page = 'deiaQuestionnaire', string $op = 'orcidVerify')
    {
        if (is_null($this->orcidConfiguration)) {
            return null;
        }

        $httpClient = $this->httpClient ?: \Application::get()->getHttpClient();

        $tokenUrl = $this->getOauthPath() . 'token';
        $requestHeaders = ['Accept' => 'application/json'];
        $requestData = [
            'client_id' => $this->orcidConfiguration['clientId'],
            'client_secret' => $this->orcidConfiguration['clientSecret'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];

        if ($redirectParams) {
            $requestData['redirect_uri'] = $this->redirectUrl($redirectParams, $page, $op);
        }

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

    public function buildOAuthUrl($redirectParams, string $page = 'deiaQuestionnaire', string $op = 'orcidVerify', ?string $state = null)
    {
        if (is_null($this->orcidConfiguration)) {
            return '';
        }

        $request = \Application::get()->getRequest();

        if ($this->isMemberApiEnabled($this->contextId)) {
            $scope = self::ORCID_API_SCOPE_MEMBER;
        } else {
            $scope = self::ORCID_API_SCOPE_PUBLIC;
        }

        $redirectUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            $page,
            $op,
            null,
            $redirectParams
        );

        return $this->getOauthPath() . 'authorize?' . http_build_query(
            array(
                'client_id' => $this->orcidConfiguration['clientId'],
                'response_type' => 'code',
                'scope' => $scope,
                'redirect_uri' => $redirectUrl,
                'state' => $state)
        );
    }

    private function redirectUrl(array $params, string $page, string $op): string
    {
        $request = \Application::get()->getRequest();
        return $request->getDispatcher()->url($request, ROUTE_PAGE, null, $page, $op, null, $params);
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
