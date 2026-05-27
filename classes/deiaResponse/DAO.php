<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponse;

use APP\plugins\generic\deiaSurvey\classes\DataEncryption;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'deiaResponse';
    public $table = 'deia_responses';
    public $primaryKeyColumn = 'deia_response_id';
    public $settingsTable = 'deia_response_settings';
    public $primaryTableColumns = [
        'id' => 'deia_response_id',
        'deiaQuestionId' => 'deia_question_id',
        'userId' => 'user_id',
        'externalId' => 'external_id',
        'externalType' => 'external_type'
    ];

    public function getParentColumn(): string
    {
        return 'deia_question_id';
    }

    public function newDataObject(): DeiaResponse
    {
        return app(DeiaResponse::class);
    }

    public function insert(DeiaResponse $deiaResponse): int
    {
        $responseValue = $deiaResponse->getValue();
        $optionsInputValue = $deiaResponse->getOptionsInputValue();

        $this->encryptResponseData($deiaResponse);

        $insertedId = parent::_insert($deiaResponse);

        $this->restoreEncryptedResponseData($deiaResponse, $responseValue, $optionsInputValue);

        return $insertedId;
    }

    public function update(DeiaResponse $deiaResponse)
    {
        $responseValue = $deiaResponse->getValue();
        $optionsInputValue = $deiaResponse->getOptionsInputValue();

        $this->encryptResponseData($deiaResponse);

        parent::_update($deiaResponse);

        $this->restoreEncryptedResponseData($deiaResponse, $responseValue, $optionsInputValue);
    }

    public function delete(DeiaResponse $deiaResponse)
    {
        return parent::_delete($deiaResponse);
    }

    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->count();
    }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->deia_response_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): DeiaResponse
    {
        $deiaResponse = parent::fromRow($row);

        $encrypter = new DataEncryption();
        $value = $deiaResponse->getValue();
        if ($encrypter->textIsEncrypted($value)) {
            $value = $encrypter->decryptString($value);
        }

        if (@unserialize($value)) {
            $deiaResponse->setValue(unserialize($value));
        }

        $optionsInputValue = $deiaResponse->getOptionsInputValue();
        if (!is_null($optionsInputValue) && $encrypter->textIsEncrypted($optionsInputValue)) {
            $optionsInputValue = $encrypter->decryptString($optionsInputValue);
        }

        if (@unserialize($optionsInputValue)) {
            $deiaResponse->setOptionsInputValue(unserialize($optionsInputValue));
        }

        return $deiaResponse;
    }

    private function encryptResponseData(DeiaResponse $deiaResponse)
    {
        $encrypter = new DataEncryption();
        $responseValue = $deiaResponse->getValue();
        $encryptedResponseValue = $encrypter->encryptString(serialize($responseValue));
        $deiaResponse->setValue($encryptedResponseValue);

        $optionsInputValue = $deiaResponse->getOptionsInputValue();
        if (!empty($optionsInputValue)) {
            $encryptedOptionsInputValue = $encrypter->encryptString(serialize($optionsInputValue));
            $deiaResponse->setOptionsInputValue($encryptedOptionsInputValue);
        }
    }

    private function restoreEncryptedResponseData($deiaResponse, $responseValue, $optionsInputValue)
    {
        $deiaResponse->setValue($responseValue);
        if (!empty($optionsInputValue)) {
            $deiaResponse->setOptionsInputValue($optionsInputValue);
        }
    }
}
