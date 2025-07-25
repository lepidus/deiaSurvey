<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use PKP\db\DAO;
use Illuminate\Support\Facades\DB;

class DemographicDataDAO extends DAO
{
    public function getConsentSetting(int $userId, int $contextId = null): ?array
    {
        $result = DB::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->first();

        if (is_null($result)) {
            return null;
        }

        $setting = get_object_vars($result);
        $value = json_decode($setting['setting_value'], true);
        if (is_null($contextId) || $value['contextId'] == $contextId) {
            return [
                'id' => $setting['user_setting_id'],
                'contextId' => $value['contextId'],
                'consentOption' => $value['consentOption']
            ];
        }

        return null;
    }

    public function userHasDemographicConsent(int $userId): bool
    {
        return $this->getConsentSetting($userId) !== null;
    }

    public function getDemographicConsentOption(int $contextId, int $userId): ?bool
    {
        $setting = $this->getConsentSetting($userId, $contextId);

        return $setting ? $setting['consentOption'] : null;
    }

    public function updateDemographicConsent(int $contextId, int $userId, bool $consentOption)
    {
        $consentSetting = $this->getConsentSetting($userId, $contextId);
        $settingValue = json_encode(['contextId' => $contextId, 'consentOption' => $consentOption]);

        if (is_null($consentSetting)) {
            DB::table('user_settings')->insert([
                'user_id' => $userId,
                'setting_name' => 'demographicDataConsent',
                'setting_value' => $settingValue
            ]);

            return;
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
