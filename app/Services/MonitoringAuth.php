<?php

namespace App\Services;

use App\Models\FaeUser;
use App\Models\Setting;
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
        $dbKey = Setting::get('admin_key');
        if ($dbKey !== null && $dbKey !== '') {
            return true;
        }

        return is_string(config('monitoring.admin_key')) && config('monitoring.admin_key') !== '';
    }

    public static function adminKey(): string
    {
        $dbKey = Setting::get('admin_key');
        if ($dbKey !== null && $dbKey !== '') {
            return (string) $dbKey;
        }

        return (string) config('monitoring.admin_key');
    }


    public static function adminEmail(): ?string
    {
        $dbEmail = Setting::get('admin_email');
        if (is_string($dbEmail) && trim($dbEmail) !== '') {
            return trim($dbEmail);
        }

        $email = config('monitoring.admin_email');
        if (is_string($email) && trim($email) !== '') {
            return trim($email);
        }

        return null;
    }

    public static function currentFae(): ?FaeUser
    {
        $faeId = self::faeId();
        return $faeId ? FaeUser::find($faeId) : null;
    }

    public static function currentProfileImage(): ?string
    {
        if (self::isAdmin()) {
            return Session::get('monitoring_admin_profile_image');
        }
        $fae = self::currentFae();
        if ($fae) {
            return $fae->profile_image ?: null;
        }
        return null;
    }
}

