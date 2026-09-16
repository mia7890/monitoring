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

        $googleEmail = Setting::get('admin_google_email');
        if (is_string($googleEmail) && trim($googleEmail) !== '') {
            return trim($googleEmail);
        }

        return null;
    }

    public static function adminGoogleConnected(): bool
    {
        $id = Setting::get('admin_google_id');
        return is_string($id) && trim($id) !== '';
    }

    public static function adminGoogleEmail(): ?string
    {
        $email = Setting::get('admin_google_email');
        return is_string($email) && trim($email) !== '' ? trim($email) : null;
    }

    public static function adminGoogleName(): ?string
    {
        $name = Setting::get('admin_google_name');
        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    public static function adminGoogleAvatar(): ?string
    {
        $avatar = Setting::get('admin_google_avatar');
        return is_string($avatar) && trim($avatar) !== '' ? trim($avatar) : null;
    }

    public static function currentFae(): ?FaeUser
    {
        $faeId = self::faeId();
        return $faeId ? FaeUser::find($faeId) : null;
    }

    public static function currentGoogleConnected(): bool
    {
        if (self::isAdmin()) {
            return self::adminGoogleConnected();
        }

        $fae = self::currentFae();
        return $fae && !empty($fae->google_id);
    }

    public static function currentGoogleEmail(): ?string
    {
        if (self::isAdmin()) {
            return self::adminGoogleEmail();
        }

        $fae = self::currentFae();
        return $fae ? ($fae->google_email ?: $fae->email) : null;
    }

    public static function currentGoogleAvatar(): ?string
    {
        if (self::isAdmin()) {
            return self::adminGoogleAvatar();
        }

        $fae = self::currentFae();
        return $fae ? $fae->google_avatar : null;
    }

    public static function currentProfileImage(): ?string
    {
        if (self::isAdmin()) {
            $adminImg = Session::get('monitoring_admin_profile_image');
            if ($adminImg) {
                return $adminImg;
            }
            return self::adminGoogleAvatar();
        }
        $fae = self::currentFae();
        if ($fae) {
            if ($fae->profile_image) {
                return $fae->profile_image;
            }
            if ($fae->google_avatar) {
                return $fae->google_avatar;
            }
        }
        return null;
    }
}

