<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use Illuminate\Database\Capsule\Manager as Capsule;

class DeiaDataDAO extends \DAO
{
    public function getConsentSetting(int $userId): ?array
    {
        $result = Capsule::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'deiaDataConsent')
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

    public function userHasDeiaConsent(int $userId): bool
    {
        return $this->getConsentSetting($userId) !== null;
    }

    public function getDeiaConsentOption(int $contextId, int $userId): ?bool
    {
        $setting = $this->getConsentSetting($userId);

        if (is_null($setting)) {
            return null;
        }

        foreach ($setting['value'] as $contextConsent) {
            if ($contextConsent['contextId'] == $contextId) {
                return $contextConsent['consentOption'];
            }
        }

        return null;
    }

    public function updateDeiaConsent(int $contextId, int $userId, bool $consentOption)
    {
        $consentSetting = $this->getConsentSetting($userId);

        if (is_null($consentSetting)) {
            $settingValue = json_encode([['contextId' => $contextId, 'consentOption' => $consentOption]]);
            Capsule::table('user_settings')->insert([
                'user_id' => $userId,
                'setting_name' => 'deiaDataConsent',
                'setting_value' => $settingValue,
                'setting_type' => 'object'
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

        Capsule::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'deiaDataConsent')
            ->update([
                'setting_value' => json_encode($settingValue)
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

    public function getConsentStatsByContext(int $contextId): array
    {
        $results = Capsule::table('user_settings')
            ->where('setting_name', '=', 'deiaDataConsent')
            ->get();

        $consentCount = 0;
        $noConsentCount = 0;

        foreach ($results as $result) {
            $setting = get_object_vars($result);
            $settingValue = json_decode($setting['setting_value'], true);

            // Handles wrong data structure used in previous versions
            if (array_key_exists('contextId', $settingValue)) {
                $settingValue = [$settingValue];
            }

            foreach ($settingValue as $contextConsent) {
                if ($contextConsent['contextId'] == $contextId) {
                    if ($contextConsent['consentOption']) {
                        $consentCount++;
                    } else {
                        $noConsentCount++;
                    }
                    break;
                }
            }
        }

        return [
            'consentCount' => $consentCount,
            'noConsentCount' => $noConsentCount
        ];
    }
}
