<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scheduling extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'key_skills',
        'cose_it_staff',
        'estimated_start_date',
        'estimated_completion_date',
        'change_board_date',
        'assigned_to',
        'priority',
        'team_assignment',
    ];

    protected $casts = [
        'estimated_start_date' => 'date',
        'estimated_completion_date' => 'date',
        'change_board_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
