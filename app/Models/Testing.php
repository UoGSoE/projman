<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testing extends Model
{
    use CanCheckIfEdited;
    use HasFactory;

    protected $touches = ['project'];

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'test_lead',
        'service_function',
        'functional_testing_title',
        'functional_tests',
        'non_functional_testing_title',
        'non_functional_tests',
        'test_repository',
        'testing_sign_off',
        'user_acceptance',
        'testing_lead_sign_off',
        'service_delivery_sign_off',
        'service_resilience_sign_off',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
