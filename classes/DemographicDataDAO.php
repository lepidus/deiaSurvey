<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use PKP\db\DAO;
use Illuminate\Support\Facades\DB;

class DemographicDataDAO extends DAO
{
    public function getConsentSetting(int $userId): ?array
    {
        $result = DB::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->first();

        if (is_null($result)) {
            return null;
        }

        $setting = get_object_vars($result);
        $settingValue = json_decode($setting['setting_value'], true);

        // Handles wrong data structure used in previous versions
        if (array_key_exists('contextId', $settingValue)) {
            $settingValue = [$settingValue];
        }

        return ['id' => $setting['user_setting_id'], 'value' => $settingValue];
    }

    public function userHasDemographicConsent(int $userId): bool
    {
        return $this->getConsentSetting($userId) !== null;
    }

    public function getDemographicConsentOption(int $contextId, int $userId): ?bool
    {
        $setting = $this->getConsentSetting($userId);

        foreach ($setting['value'] as $contextConsent) {
            if ($contextConsent['contextId'] == $contextId) {
                return $contextConsent['consentOption'];
            }
        }

        return null;
    }

    public function updateDemographicConsent(int $contextId, int $userId, bool $consentOption)
    {
        $consentSetting = $this->getConsentSetting($userId);

        if (is_null($consentSetting)) {
            $settingValue = json_encode([['contextId' => $contextId, 'consentOption' => $consentOption]]);
            DB::table('user_settings')->insert([
                'user_id' => $userId,
                'setting_name' => 'demographicDataConsent',
                'setting_value' => $settingValue
            ]);

            return;
        }

        $settingValue = $consentSetting['value'];
        $contextIsPresent = false;
        foreach ($settingValue as $key => $contextConsent) {
            if ($contextConsent['contextId'] == $contextId) {
                $settingValue[$key]['consentOption'] = $consentOption;
                $contextIsPresent = true;
                break;
            }
        }

        if (!$contextIsPresent) {
            $settingValue[] = ['contextId' => $contextId, 'consentOption' => $consentOption];
        }

        DB::table('user_settings')
            ->where('user_setting_id', $consentSetting['id'])
            ->update([
                'setting_value' => $settingValue
            ]);
    }

    public function thereIsUserWithSetting(string $value, string $type): bool
    {
        if ($type == 'email') {
            $countUsers = DB::table('users')
                ->where('email', '=', $value)
                ->count();
        } elseif ($type == 'orcid') {
            $countUsers = DB::table('user_settings')
                ->where('setting_name', 'orcid')
                ->where('setting_value', $value)
                ->count();
        }

        return $countUsers > 0;
    }
}
