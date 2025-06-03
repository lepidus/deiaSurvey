<?php

namespace APP\plugins\generic\demographicData\classes;

use Illuminate\Database\Capsule\Manager as Capsule;

class DemographicDataDAO extends \DAO
{
    private function getConsentSetting(int $contextId, int $userId): ?array
    {
        $rows = Capsule::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->get();

        foreach ($rows as $row) {
            $row = get_object_vars($row);
            $value = json_decode($row['setting_value'], true);
            if ($value['contextId'] == $contextId) {
                return ['id' => $row['user_id'], 'consentOption' => $value['consentOption']];
            }
        }

        return null;
    }

    public function getDemographicConsent(int $contextId, int $userId): ?bool
    {
        $setting = $this->getConsentSetting($contextId, $userId);

        return $setting ? $setting['consentOption'] : null;
    }

    public function updateDemographicConsent(int $contextId, int $userId, bool $consentOption)
    {
        $consentSetting = $this->getConsentSetting($contextId, $userId);
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
