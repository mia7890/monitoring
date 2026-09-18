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

    /**
     * Get attachments as a normalized list of paths.
     * Supports legacy single-file strings as well as JSON arrays of multiple files.
     *
     * @return string[]
     */
    public function getAttachmentsListAttribute(): array
    {
        if (empty($this->attachment)) {
            return [];
        }

        $val = trim($this->attachment);
        if (str_starts_with($val, '[') && str_ends_with($val, ']')) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return array_filter($decoded);
            }
        }

        return [$val];
    }
}

