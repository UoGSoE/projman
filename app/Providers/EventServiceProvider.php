<?php

namespace App\Providers;

use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Listeners\SendProjectCreatedMail;
use App\Listeners\SendProjectStageChangeMail;
use App\Listeners\CreateRelatedForms;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProjectCreated::class => [
            CreateRelatedForms::class,
            SendProjectCreatedMail::class,
        ],
        ProjectStageChange::class => [
            SendProjectStageChangeMail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
