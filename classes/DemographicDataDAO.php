<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use PKP\db\DAO;
use Illuminate\Support\Facades\DB;

class DemographicDataDAO extends DAO
{
    private function getConsentSetting(int $contextId, int $userId): ?array
    {
        $rows = DB::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->get();

        foreach ($rows as $row) {
            $row = get_object_vars($row);
            $value = json_decode($row['setting_value'], true);
            if ($value['contextId'] == $contextId) {
                return ['id' => $row['user_setting_id'], 'consentOption' => $value['consentOption']];
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
