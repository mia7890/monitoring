<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaeUser extends Model
{
    use HasFactory;

    protected $table = 'fae_users';

    protected $fillable = [
        'name',
        'fae_code',
        'email',
        'department',
        'phone',
        'profile_image',
    ];

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
