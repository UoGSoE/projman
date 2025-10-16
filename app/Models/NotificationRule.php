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
        'applies_to',
        'recipients',
        'active',
    ];

    protected $casts = [
        'applies_to' => 'array',
        'recipients' => 'array',
        'active' => 'boolean',
    ];
}
