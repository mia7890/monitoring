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
        'reason',
        'status',
        'admin_comment',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    public function fae(): BelongsTo
    {
        return $this->belongsTo(FaeUser::class, 'fae_id');
    }
}
