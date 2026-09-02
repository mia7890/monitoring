<?php

namespace App\Services;

use App\Models\FaeUser;
use Illuminate\Support\Facades\Session;

class MonitoringAuth
{
    public static function role(): ?string
    {
        return Session::get('monitoring_role');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isFae(): bool
    {
        return self::role() === 'fae';
    }

    public static function faeId(): ?int
    {
        return self::isFae() ? (int)Session::get('monitoring_fae_id') : null;
    }

    public static function faeName(): string
    {
        return (string)Session::get('monitoring_fae_name', 'FAE User');
    }

    public static function faeCode(): string
    {
        return (string)Session::get('monitoring_fae_code', '');
    }

    public static function adminKeyConfigured(): bool
    {
        return is_string(config('monitoring.admin_key')) && config('monitoring.admin_key') !== '';
    }

    public static function adminKey(): string
    {
        return (string) config('monitoring.admin_key');
    }

    public static function currentProfileImage(): ?string
    {
        if (self::isAdmin()) {
            return Session::get('monitoring_admin_profile_image');
        }
        $faeId = self::faeId();
        if ($faeId) {
            $fae = FaeUser::find($faeId);
            return $fae ? $fae->profile_image : null;
        }
        return null;
    }
}
