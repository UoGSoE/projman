<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProjectCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Project $project)
    {
    }
}
