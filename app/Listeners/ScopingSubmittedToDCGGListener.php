<?php

namespace App\Listeners;

use App\Events\ScopingSubmittedToDCGG;
use App\Jobs\SendEmailJob;
use App\Models\NotificationRule;

class ScopingSubmittedToDCGGListener
{
    public function handle(ScopingSubmittedToDCGG $event): void
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
