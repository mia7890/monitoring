<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'fae_id',
        'region',
        'course',
        'task_name',
        'description',
        'deadline',
        'status',
        'progress',
        'priority',
    ];

    protected $casts = [
        'deadline' => 'date',
        'progress' => 'integer',
    ];

    public function fae(): BelongsTo
    {
        return $this->belongsTo(FaeUser::class, 'fae_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function isOverdue(): bool
    {
        $today = Carbon::today()->format('Y-m-d');

        return $this->status === 'Overdue'
            || ($this->deadline && $this->deadline->format('Y-m-d') < $today && $this->status !== 'Completed');
    }
}
