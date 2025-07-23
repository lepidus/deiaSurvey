<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use Illuminate\Database\Capsule\Manager as Capsule;

class DemographicDataDAO extends \DAO
{
    private function getConsentSetting(int $userId, int $contextId = null): ?array
    {
        $result = Capsule::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->first();

        if (is_null($result)) {
            return null;
        }

        $setting = get_object_vars($result);
        $settingValue = json_decode($setting['setting_value'], true);
        if (is_null($contextId) || $settingValue['contextId'] == $contextId) {
            return [
                'id' => $setting['user_setting_id'],
                'contextId' => $settingValue['contextId'],
                'consentOption' => $settingValue['consentOption']
            ];
        }

        return null;
    }

    public function userHasDemographicConsent(int $userId): bool
    {
        return $this->getConsentSetting($userId) !== null;
    }

    public function getDemographicConsent(int $contextId, int $userId): ?bool
    {
        $setting = $this->getConsentSetting($userId, $contextId);

        return $setting ? $setting['consentOption'] : null;
    }

    public function updateDemographicConsent(int $contextId, int $userId, bool $consentOption)
    {
        $consentSetting = $this->getConsentSetting($userId, $contextId);
        $settingValue = json_encode(['contextId' => $contextId, 'consentOption' => $consentOption]);

        if (is_null($consentSetting)) {
            Capsule::table('user_settings')->insert([
                'user_id' => $userId,
                'setting_name' => 'demographicDataConsent',
                'setting_value' => $settingValue,
                'setting_type' => 'object'
            ]);

            return;
        }

        Capsule::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->update([
                'setting_value' => $settingValue
            ]);
    }

    public function thereIsUserWithSetting(string $value, string $type): bool
    {
        if ($type == 'email') {
            $countUsers = Capsule::table('users')
                ->where('email', '=', $value)
                ->count();
        } elseif ($type == 'orcid') {
            $countUsers = Capsule::table('user_settings')
                ->where('setting_name', 'orcid')
                ->where('setting_value', $value)
                ->count();
        }

        return $countUsers > 0;
    }
}
