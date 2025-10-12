<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicResponse;

use APP\plugins\generic\deiaSurvey\classes\core\EntityDAO;
use APP\plugins\generic\deiaSurvey\classes\core\traits\EntityWithParent;
use Illuminate\Support\LazyCollection;
use APP\plugins\generic\deiaSurvey\classes\DataEncryption;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicResponse';
    public $table = 'demographic_responses';
    public $primaryKeyColumn = 'demographic_response_id';
    public $settingsTable = 'demographic_response_settings';
    public $primaryTableColumns = [
        'id' => 'demographic_response_id',
        'demographicQuestionId' => 'demographic_question_id',
        'userId' => 'user_id',
        'externalId' => 'external_id',
        'externalType' => 'external_type'
    ];

    public function getParentColumn(): string
    {
        return 'demographic_question_id';
    }

    public function newDataObject(): DemographicResponse
    {
        return app(DemographicResponse::class);
    }

    public function insert(DemographicResponse $demographicResponse): int
    {
        $responseValue = $demographicResponse->getValue();
        $optionsInputValue = $demographicResponse->getOptionsInputValue();

        $this->encryptResponseData($demographicResponse);

        $insertedId = parent::_insert($demographicResponse);

        $this->restoreEncryptedResponseData($demographicResponse, $responseValue, $optionsInputValue);

        return $insertedId;
    }

    public function update(DemographicResponse $demographicResponse)
    {
        $responseValue = $demographicResponse->getValue();
        $optionsInputValue = $demographicResponse->getOptionsInputValue();

        $this->encryptResponseData($demographicResponse);

        parent::_update($demographicResponse);

        $this->restoreEncryptedResponseData($demographicResponse, $responseValue, $optionsInputValue);
    }

    public function delete(DemographicResponse $demographicResponse)
    {
        return parent::_delete($demographicResponse);
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
                yield $row->demographic_response_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): \DataObject
    {
        $demographicResponse = parent::fromRow($row);

        $encrypter = new DataEncryption();
        $value = $demographicResponse->getValue();
        if ($encrypter->textIsEncrypted($value)) {
            $value = $encrypter->decryptString($value);
        }

        if (@unserialize($value)) {
            $demographicResponse->setValue(unserialize($value));
        }

        $optionsInputValue = $demographicResponse->getOptionsInputValue();
        if (!is_null($optionsInputValue) && $encrypter->textIsEncrypted($optionsInputValue)) {
            $optionsInputValue = $encrypter->decryptString($optionsInputValue);
        }

        if (@unserialize($optionsInputValue)) {
            $demographicResponse->setOptionsInputValue(unserialize($optionsInputValue));
        }

        return $demographicResponse;
    }

    private function encryptResponseData(DemographicResponse $demographicResponse)
    {
        $encrypter = new DataEncryption();
        $responseValue = $demographicResponse->getValue();
        $encryptedResponseValue = $encrypter->encryptString(serialize($responseValue));
        $demographicResponse->setValue($encryptedResponseValue);

        $optionsInputValue = $demographicResponse->getOptionsInputValue();
        if (!empty($optionsInputValue)) {
            $encryptedOptionsInputValue = $encrypter->encryptString(serialize($optionsInputValue));
            $demographicResponse->setOptionsInputValue($encryptedOptionsInputValue);
        }
    }

    private function restoreEncryptedResponseData($demographicResponse, $responseValue, $optionsInputValue)
    {
        $demographicResponse->setValue($responseValue);
        if (!empty($optionsInputValue)) {
            $demographicResponse->setOptionsInputValue($optionsInputValue);
        }
    }
}
