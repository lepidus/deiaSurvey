<?php

namespace APP\plugins\generic\demographicData\classes;

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
}
