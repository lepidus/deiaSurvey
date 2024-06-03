<?php

namespace APP\plugins\generic\demographicData\classes;

use PKP\db\DAO;
use Illuminate\Support\Facades\DB;

class DemographicDataDAO extends DAO
{
    public function userGaveDemographicConsent(int $contextId, int $userId): bool
    {
        $result = DB::table('user_settings')
            ->where('user_id', '=', $userId)
            ->where('setting_name', '=', 'demographicDataConsent')
            ->where('setting_value', '=', $contextId)
            ->first();

        return !is_null($result);
    }

    public function updateDemographicConsent(int $contextId, int $userId, bool $consentOption)
    {
        $previousConsent = $this->userGaveDemographicConsent($contextId, $userId);

        if ($previousConsent == $consentOption) {
            return;
        }

        if ($consentOption) {
            DB::table('user_settings')->insert([
                'user_id' => $userId,
                'setting_name' => 'demographicDataConsent',
                'setting_value' => $contextId
            ]);
        } else {
            DB::table('user_settings')
                ->where('user_id', '=', $userId)
                ->where('setting_name', '=', 'demographicDataConsent')
                ->where('setting_value', '=', $contextId)
                ->delete();
        }
    }
}
