<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use Author;
use Illuminate\Database\Capsule\Manager as DB;

class QuestionnaireTokenService
{
    public function create(Author $author): string
    {
        $token = bin2hex(random_bytes(32));
        DB::connection()->transaction(function () use ($author, $token): void {
            if (!DB::table('authors')->where('author_id', $author->getId())->lockForUpdate()->first()) {
                throw new \DomainException('Questionnaire author no longer exists.');
            }
            $author = \Services::get('author')->get($author->getId());
            $params = [
                'deiaToken' => hash('sha256', $token),
                'deiaTokenExpiresAt' => \Carbon\Carbon::now()->addDays(7)->timestamp,
                'deiaTokenEmail' => $author->getData('email'),
            ];
            // Legacy links cannot prove the provenance of an existing ORCID association.
            if ($author->getData('deiaTokenEmail') !== $author->getData('email')) {
                $params['deiaOrcid'] = null;
            }
            \Services::get('author')->edit($author, $params, \Application::get()->getRequest());
        });
        return $token;
    }

    public function isValid(?Author $author, $token): bool
    {
        if (!$author || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return false;
        }

        $hash = $author->getData('deiaToken');
        $expiresAt = $author->getData('deiaTokenExpiresAt');
        // Missing expiration rejects all tokens issued before this format was introduced.
        return $author->getData('deiaTokenEmail') === $author->getData('email')
            && is_string($hash) && is_numeric($expiresAt) && (int) $expiresAt > \Carbon\Carbon::now()->timestamp
            && hash_equals($hash, hash('sha256', $token));
    }

    public function canRenew(?Author $author, $token): bool
    {
        if (!$author || !is_string($token) || $this->isValid($author, $token)) {
            return false;
        }
        $stored = $author->getData('deiaToken');
        if (!is_string($stored) || $stored === '') {
            return false;
        }
        // Legacy secrets permit email recovery only; they never grant access to responses.
        if (preg_match('/^[a-f0-9]{32}$/D', $token)) {
            return hash_equals($stored, $token);
        }
        return $author->getData('deiaTokenEmail') === $author->getData('email')
            && preg_match('/^[a-f0-9]{64}$/D', $token)
            && hash_equals($stored, hash('sha256', $token));
    }
}
