<?php
use App\Livewire\ProjectCreator;
use App\Livewire\ProjectEditor;
use App\Mail\ProjectCreatedMail;
use App\Mail\ProjectStageChangeMail;
use App\Models\Project;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;




uses(RefreshDatabase::class);

describe('Form stage notifications', function () {

    it('sends a notification when a project is created', function () {
        Mail::fake();


        $user = User::factory()->create();
        $this->actingAs($user);
        livewire(ProjectCreator::class)
            ->set('projectName', 'Test Project')
            ->call('save')
            ->assertHasNoErrors();

        Mail::assertQueued(ProjectCreatedMail::class);
        Mail::assertQueued(ProjectCreatedMail::class, count(config('projman.mail.project_created')));

        foreach (config('projman.mail.project_created') as $email) {
            Mail::assertQueued(ProjectCreatedMail::class, function ($mail) use ($email) {
                return $mail->hasTo($email);
            });
        }

    });

    // TODO: test content of the email

    it('sends a notification when a project advances to the next stage', function () {
        Mail::fake();

        $project = Project::factory()->create();
        $newStatus = $project->advanceToNextStage();

        Mail::assertQueued(ProjectStageChangeMail::class);
        Mail::assertQueued(ProjectStageChangeMail::class, count(config('projman.mail.stages.' . $newStatus->value)));

        foreach (config('projman.mail.stages.' . $newStatus->value) as $email) {
            Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) use ($email) {
                return $mail->hasTo($email);
            });
        }

    });
});
