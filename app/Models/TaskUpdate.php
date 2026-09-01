<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskUpdate extends Model
{
    use HasFactory;

    protected $table = 'task_updates';

    protected $fillable = [
        'task_id',
        'fae_id',
        'author_role',
        'author_name',
        'message',
        'attachment',
        'progress_at_update',
        'status_at_update',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function fae(): BelongsTo
    {
        return $this->belongsTo(FaeUser::class, 'fae_id');
    }
}
