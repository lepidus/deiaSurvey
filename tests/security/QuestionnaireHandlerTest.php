<?php

namespace APP\plugins\generic\deiaSurvey\tests\security;

use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class QuestionnaireHandlerTest extends TestCase
{
    /**
     * @dataProvider invalidTokenProvider
     */
    public function testRejectsMissingAuthorOrEmptyTokens(bool $authorPresent, $storedToken, $requestToken): void
    {
        $author = $authorPresent ? $this->author($storedToken) : null;
        self::assertFalse($this->tokenIsValid($author, $requestToken));
    }

    public function invalidTokenProvider(): array
    {
        return [
            'missing author' => [false, null, 'request-token'],
            'null stored token' => [true, null, 'request-token'],
            'empty stored token' => [true, '', 'request-token'],
            'null request token' => [true, 'stored-token', null],
            'empty request token' => [true, 'stored-token', ''],
            'different token' => [true, 'stored-token', 'request-token'],
        ];
    }

    public function testAcceptsUnexpiredTokenMatchingItsStoredHash(): void
    {
        $token = str_repeat('a', 64);
        self::assertTrue($this->tokenIsValid($this->author($token), $token));
    }

    private function tokenIsValid($author, $requestToken): bool
    {
        $method = new ReflectionMethod(QuestionnaireHandler::class, 'authorTokenIsValid');
        $method->setAccessible(true);
        return $method->invoke(new QuestionnaireHandler(), $author, $requestToken);
    }

    private function author($storedToken): \APP\author\Author
    {
        $author = new \APP\author\Author();
        $author->setEmail('token-test@example.org');
        $author->setData('deiaToken', is_string($storedToken) ? hash('sha256', $storedToken) : null);
        $author->setData('deiaTokenExpiresAt', \Carbon\Carbon::now()->addDay()->timestamp);
        $author->setData('deiaTokenEmail', $author->getEmail());
        return $author;
    }
}
