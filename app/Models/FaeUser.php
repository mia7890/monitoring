<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaeUser extends Model
{
    use HasFactory;

    protected $table = 'fae_users';

    protected $fillable = [
        'name',
        'fae_code',
        'email',
        'department_id',
        'phone',
        'profile_image',
        'google_id',
        'google_email',
        'google_avatar',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];

    protected $hidden = [
        'google_access_token',
        'google_refresh_token',
    ];

    public function hasGoogleAccount(): bool
    {
        return !empty($this->google_id) || !empty($this->google_email);
    }


    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'fae_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'fae_id');
    }

    public function taskUpdates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class, 'fae_id');
    }
}
