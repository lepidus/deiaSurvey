<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(__DIR__ . '/../autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.plugins.GenericPlugin');
require_once(__DIR__ . '/../DeiaSurveyPlugin.php');

class OrcidResponseMigrationTest extends \PKPTestCase
{
    public function testDoesNotMigrateResponsesBeforeNewUserIsPersisted(): void
    {
        $user = $this->createUserStub(null);
        $form = new \stdClass();
        $form->user = $user;
        $plugin = $this->createPluginMigrationSpy();

        $plugin->checkMigrateResponsesOrcid('userdetailsform::execute', [$form]);

        self::assertSame([], $plugin->migratedUsers);
    }

    public function testMigratesResponsesAfterNewUserIsPersisted(): void
    {
        $user = $this->createUserStub(123);
        $form = new \stdClass();
        $form->userId = 123;
        $plugin = $this->createPluginMigrationSpy($user);

        $plugin->checkMigrateResponsesOrcidAfterUserCreation('userroleform::execute', [$form]);

        self::assertSame([$user], $plugin->migratedUsers);
    }

    public function testStillMigratesResponsesWhenExistingUserIsEdited(): void
    {
        $user = $this->createUserStub(123);
        $form = new \stdClass();
        $form->user = $user;
        $plugin = $this->createPluginMigrationSpy();

        $plugin->checkMigrateResponsesOrcid('userdetailsform::execute', [$form]);

        self::assertSame([$user], $plugin->migratedUsers);
    }

    private function createUserStub($userId): object
    {
        return new class ($userId) {
            private $userId;

            public function __construct($userId)
            {
                $this->userId = $userId;
            }

            public function getId()
            {
                return $this->userId;
            }

            public function getOrcid(): string
            {
                return 'https://orcid.org/0000-0001-6619-6622';
            }
        };
    }

    private function createPluginMigrationSpy($persistedUser = null): object
    {
        return new class ($persistedUser) extends \DeiaSurveyPlugin {
            public $migratedUsers = [];
            private $persistedUser;

            public function __construct($persistedUser)
            {
                $this->persistedUser = $persistedUser;
            }

            protected function getUserById($userId)
            {
                return $this->persistedUser;
            }

            protected function migrateResponsesOrcid($user): void
            {
                $this->migratedUsers[] = $user;
            }
        };
    }
}
