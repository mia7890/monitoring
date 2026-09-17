<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'status',
        'email',
        'department_id',
        'phone',
        'profile_image',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
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
