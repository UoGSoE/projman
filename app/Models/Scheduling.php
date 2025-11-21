<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scheduling extends Model
{
    use CanCheckIfEdited;
    use HasFactory;

    protected $touches = ['project'];

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'key_skills',
        'cose_it_staff',
        'estimated_start_date',
        'estimated_completion_date',
        'change_board_date',
        'assigned_to',
        'technical_lead_id',
        'change_champion_id',
        'change_board_outcome',
        'fields_locked',
        'priority',
        'team_assignment',
        'submitted_to_dcgg_at',
        'submitted_to_dcgg_by',
        'scheduled_at',
    ];

    protected $casts = [
        'estimated_start_date' => 'date',
        'estimated_completion_date' => 'date',
        'change_board_date' => 'date',
        'cose_it_staff' => 'array',
        'change_board_outcome' => \App\Enums\ChangeBoardOutcome::class,
        'fields_locked' => 'boolean',
        'submitted_to_dcgg_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function submittedToDcggBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_to_dcgg_by');
    }

    public function technicalLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_lead_id');
    }

    public function changeChampion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'change_champion_id');
    }
}
