<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'event',
        'recipients',
        'active',
    ];

    protected $casts = [
        'event' => 'array',
        'recipients' => 'array',
        'active' => 'boolean',
    ];
}
