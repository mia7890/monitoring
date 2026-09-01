<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminEvent extends Model
{
    use HasFactory;

    protected $table = 'admin_events';

    protected $fillable = [
        'title',
        'event_date',
        'description',
        'category',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];
}
