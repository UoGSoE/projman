<?php

namespace App\Listeners;

use App\Events\ScopingScheduled;
use App\Jobs\SendEmailJob;
use App\Models\NotificationRule;

class ScopingScheduledListener
{
    public function handle(ScopingScheduled $event): void
    {
        $eventClass = get_class($event);
        $rules = NotificationRule::where('event->class', $eventClass)->where('active', true)->get();

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            SendEmailJob::dispatch($rule, $event);
        }
    }
}
