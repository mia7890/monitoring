<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'fae_id',
        'user_name',
        'appointment_date',
        'start_time',
        'end_time',
        'reason',
        'status',
        'admin_comment',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    protected $appends = [
        'time_slot',
        'start_time_formatted',
        'end_time_formatted',
        'is_past',
        'effective_status',
    ];

    public function fae(): BelongsTo
    {
        return $this->belongsTo(FaeUser::class, 'fae_id');
    }

    public function setStartTimeAttribute($value): void
    {
        if ($value && strlen($value) === 5) {
            $value .= ':00';
        }
        $this->attributes['start_time'] = $value;
    }

    public function setEndTimeAttribute($value): void
    {
        if ($value && strlen($value) === 5) {
            $value .= ':00';
        }
        $this->attributes['end_time'] = $value;
    }

    /**
     * Get human-readable time slot range (e.g. "09:00 AM - 12:00 PM").
     */
    public function getTimeSlotAttribute(): ?string
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        try {
            $startStr = strlen($this->start_time) === 5 ? $this->start_time . ':00' : $this->start_time;
            $endStr = strlen($this->end_time) === 5 ? $this->end_time . ':00' : $this->end_time;
            $start = \Carbon\Carbon::createFromFormat('H:i:s', $startStr)->format('h:i A');
            $end = \Carbon\Carbon::createFromFormat('H:i:s', $endStr)->format('h:i A');
            return "{$start} - {$end}";
        } catch (\Throwable $e) {
            return "{$this->start_time} - {$this->end_time}";
        }
    }

    /**
     * Get start time formatted in 12-hour format with AM/PM.
     */
    public function getStartTimeFormattedAttribute(): ?string
    {
        if (!$this->start_time) return null;
        try {
            $startStr = strlen($this->start_time) === 5 ? $this->start_time . ':00' : $this->start_time;
            return \Carbon\Carbon::createFromFormat('H:i:s', $startStr)->format('h:i A');
        } catch (\Throwable $e) {
            return $this->start_time;
        }
    }

    /**
     * Get end time formatted in 12-hour format with AM/PM.
     */
    public function getEndTimeFormattedAttribute(): ?string
    {
        if (!$this->end_time) return null;
        try {
            $endStr = strlen($this->end_time) === 5 ? $this->end_time . ':00' : $this->end_time;
            return \Carbon\Carbon::createFromFormat('H:i:s', $endStr)->format('h:i A');
        } catch (\Throwable $e) {
            return $this->end_time;
        }
    }

    /**
     * Determine if appointment date is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        if (!$this->appointment_date) return false;
        return $this->appointment_date->format('Y-m-d') < \Carbon\Carbon::today()->format('Y-m-d');
    }

    /**
     * Get effective status (marks unreviewed past pending as expired).
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'pending' && $this->is_past) {
            return 'expired';
        }
        return $this->status ?? 'pending';
    }
}
