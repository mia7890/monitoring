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
        'attachment',
        'links',
        'deadline',
        'status',
        'progress',
        'priority',
    ];

    protected $casts = [
        'deadline' => 'date',
        'progress' => 'integer',
    ];

    /**
     * Get attachments as a normalized list of paths.
     * Supports single file path string as well as JSON arrays of multiple files.
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
                return array_values(array_filter($decoded));
            }
        }

        return [$val];
    }

    /**
     * Get reference links as a normalized array of URL strings.
     * Supports newline/comma-separated strings or JSON arrays.
     *
     * @return string[]
     */
    public function getLinksListAttribute(): array
    {
        if (empty($this->links)) {
            return [];
        }

        $val = trim($this->links);
        if (str_starts_with($val, '[') && str_ends_with($val, ']')) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
        }

        // Split by lines or commas
        $lines = preg_split('/[\r\n,]+/', $val);
        $result = [];
        foreach ($lines as $line) {
            $item = trim($line);
            if (!empty($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }

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
