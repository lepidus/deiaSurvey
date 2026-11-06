<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\facades\Repo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\context\Context;

class EmailAssociationService
{
    public const SETTING_NAME = 'deiaEmailAssociation';

    public function create(Context $context, int $userId): ?string
    {
        return DB::transaction(function () use ($context, $userId): ?string {
            $account = DB::table('users')->where('user_id', $userId)->lockForUpdate()->first();
            if (!$account || $account->disabled) {
                return null;
            }
            $user = Repo::user()->get($userId, true);
            if (!(new DeiaDataService())->emailNeedsAssociationConfirmation($user)) {
                return null;
            }
            $previous = json_decode($this->settings($userId)->value('setting_value') ?? '{}', true);
            if (($previous['createdAt'] ?? 0) > now()->timestamp - 60) {
                return null;
            }

            $token = bin2hex(random_bytes(32));
            $this->settings($userId)->updateOrInsert([
                'user_id' => $userId,
                'locale' => '',
                'setting_name' => self::SETTING_NAME,
            ], ['setting_value' => json_encode([
                'contextId' => $context->getId(),
                'email' => $account->email,
                'tokenHash' => hash('sha256', $token),
                'createdAt' => now()->timestamp,
                'expiresAt' => now()->addDay()->timestamp,
            ], JSON_THROW_ON_ERROR)]);

            return $token;
        });
    }

    public function confirm(Context $context, int $userId, string $token): bool
    {
        if ($userId <= 0 || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return false;
        }
        return DB::transaction(function () use ($context, $userId, $token): bool {
            // Serialize confirmation, resend and questionnaire saves on the same account row.
            $account = DB::table('users')->where('user_id', $userId)->lockForUpdate()->first();
            $pending = json_decode($this->settings($userId)->value('setting_value') ?? '{}', true);
            if (!$account || $account->disabled
                || ($pending['contextId'] ?? null) !== $context->getId()
                || ($pending['email'] ?? null) !== $account->email
                || ($pending['expiresAt'] ?? 0) <= now()->timestamp
                || !hash_equals($pending['tokenHash'] ?? '', hash('sha256', $token))) {
                return false;
            }

            $user = Repo::user()->get($userId, true);
            $associated = (new DeiaDataService())->migrateResponsesByConfirmedEmail($context, $user, $account->email);
            $this->settings($userId)->delete();
            return $associated;
        });
    }

    private function settings(int $userId): Builder
    {
        return DB::table('user_settings')->where('user_id', $userId)
            ->where('locale', '')->where('setting_name', self::SETTING_NAME);
    }
}
