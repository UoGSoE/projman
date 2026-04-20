<?php

use App\Enums\Busyness;
use App\Enums\SkillLevel;
use App\Livewire\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Profile Component', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'is_staff' => true,
            'forenames' => 'John',
            'surname' => 'Doe',
        ]);

        $this->skill1 = Skill::factory()->create([
            'name' => 'Laravel',
            'description' => 'PHP framework for web development',
            'skill_category' => 'Programming',
        ]);

        $this->skill2 = Skill::factory()->create([
            'name' => 'React',
            'description' => 'JavaScript library for building user interfaces',
            'skill_category' => 'Frontend',
        ]);

        $this->actingAs($this->user);
        $this->user->updateSkill($this->skill1->id, SkillLevel::AWARENESS->value);
        $this->user->updateSkill($this->skill2->id, SkillLevel::WORKING->value);
    });

    it('renders the component', function () {
        livewire(Profile::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.profile');
    });

    it('displays the users assigned skills in a read-only grid', function () {
        livewire(Profile::class)
            ->assertSeeText('My Skills')
            ->assertSeeText('Laravel')
            ->assertSeeText('PHP framework for web development')
            ->assertSeeText('Programming')
            ->assertSeeText('Awareness')
            ->assertSeeText('React')
            ->assertSeeText('Working');
    });

    it('only shows skills assigned to the user', function () {
        Skill::factory()->create(['name' => 'Python']);

        livewire(Profile::class)
            ->assertSeeText('Laravel')
            ->assertSeeText('React')
            ->assertDontSeeText('Python');
    });

    it('shows empty state for user with no skills', function () {
        $newUser = User::factory()->create(['is_staff' => true]);
        $this->actingAs($newUser);

        livewire(Profile::class)
            ->assertSeeText('No skills recorded');
    });

    it('displays busyness controls', function () {
        livewire(Profile::class)
            ->assertSeeText('My Busy-ness');
    });

    it('persists a busyness selection to the database as the matching enum', function () {
        livewire(Profile::class)
            ->set('busynessWeek1', Busyness::HIGH->value)
            ->set('busynessWeek2', Busyness::MEDIUM->value);

        expect($this->user->fresh()->busyness_week_1)->toBe(Busyness::HIGH)
            ->and($this->user->fresh()->busyness_week_2)->toBe(Busyness::MEDIUM);
    });
});
