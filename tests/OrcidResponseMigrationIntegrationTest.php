<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(__DIR__ . '/../autoload.php');
require_once(__DIR__ . '/helpers/TestHelperTrait.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponse\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.user.User');
require_once(__DIR__ . '/../DeiaSurveyPlugin.php');

class OrcidResponseMigrationIntegrationTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private const ORCID = 'https://orcid.org/0000-0001-6619-6622';

    private $context;
    private $deiaQuestionId;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'deia_responses',
            'deia_response_settings',
            'users',
            'user_settings',
        ]);

        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->deiaQuestionId = $this->createDeiaQuestion();
        $this->context = \Application::getContextDAO()->getById(1);
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testMigratesExternalOrcidResponseToPersistedUser(): void
    {
        $repository = app(Repository::class);
        $response = $repository->newDataObject([
            'deiaQuestionId' => $this->deiaQuestionId,
            'externalId' => self::ORCID,
            'externalType' => 'orcid',
            'responseValue' => ['en_US' => 'Test response'],
        ]);
        $responseId = $repository->add($response);
        $user = $this->createPersistedUser();
        $form = new \stdClass();
        $form->userId = $user->getId();
        $plugin = $this->createPluginWithContext();

        $plugin->checkMigrateResponsesOrcidAfterUserCreation('userroleform::execute', [$form]);

        $migratedResponse = $repository->get($responseId, $this->deiaQuestionId);
        self::assertSame((int) $user->getId(), (int) $migratedResponse->getUserId());
        self::assertNull($migratedResponse->getExternalId());
        self::assertNull($migratedResponse->getExternalType());
    }

    private function createPersistedUser(): \User
    {
        $user = new \User();
        $user->setUsername('deia-migration-test');
        $user->setPassword('not-used-in-this-test');
        $user->setEmail('deia-migration-test@example.com');
        $user->setLocales(['en_US']);
        $user->setOrcid(self::ORCID);

        $userDao = \DAORegistry::getDAO('UserDAO');
        $userDao->insertObject($user);

        return $user;
    }

    private function createPluginWithContext(): object
    {
        return new class ($this->context) extends \DeiaSurveyPlugin {
            private $context;

            public function __construct($context)
            {
                $this->context = $context;
            }

            protected function getCurrentContext()
            {
                return $this->context;
            }
        };
    }
}
