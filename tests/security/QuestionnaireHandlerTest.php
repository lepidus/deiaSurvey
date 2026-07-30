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

    public function testAcceptsEqualNonEmptyTokens(): void
    {
        self::assertTrue($this->tokenIsValid($this->author('stored-token'), 'stored-token'));
    }

    private function tokenIsValid($author, $requestToken): bool
    {
        $method = new ReflectionMethod(QuestionnaireHandler::class, 'authorTokenIsValid');
        $method->setAccessible(true);
        return $method->invoke(new QuestionnaireHandler(), $author, $requestToken);
    }

    private function author($storedToken): object
    {
        return new class ($storedToken) {
            private $storedToken;

            public function __construct($storedToken)
            {
                $this->storedToken = $storedToken;
            }

            public function getData(string $settingName)
            {
                return $settingName === 'deiaToken' ? $this->storedToken : null;
            }
        };
    }
}
