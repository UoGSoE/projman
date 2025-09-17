<?php

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

        $this->skill3 = Skill::factory()->create([
            'name' => 'Project Management',
            'description' => 'Managing projects and teams',
            'skill_category' => 'Management',
        ]);

        $this->skill4 = Skill::factory()->create([
            'name' => 'Vue.js',
            'description' => 'Progressive JavaScript framework',
            'skill_category' => 'Frontend',
        ]);

        $this->skill5 = Skill::factory()->create([
            'name' => 'Python',
            'description' => 'High-level programming language',
            'skill_category' => 'Programming',
        ]);
        $this->actingAs($this->user);
        $this->user->updateSkillForUser($this->skill1, SkillLevel::BEGINNER->value);
        $this->user->updateSkillForUser($this->skill2, SkillLevel::BEGINNER->value);

    });

    describe('Basic Rendering', function () {
        it('renders the component', function () {
            livewire(Profile::class)
                ->assertStatus(200)
                ->assertViewIs('livewire.profile');
        });
        it('displays user skills', function () {
            livewire(Profile::class)
                ->assertSeeText('My Skills')
                ->assertSeeText('Laravel')
                ->assertSeeText('React');
        });
        it('displays available skills for assignment by default', function () {
            livewire(Profile::class)
                ->assertSeeText('Add Skills')
                ->assertSeeText('Python')
                ->assertSeeText('Project Management');
        });
        it('can add a skill', function () {
            livewire(Profile::class)
                ->call('addSkillWithLevel', $this->skill1->id);

            expect($this->user->fresh()->skills->contains($this->skill1))->toBeTrue();
            expect($this->user->fresh()->skills->first()->pivot->skill_level)->toBe(SkillLevel::BEGINNER->value);
        });
    });

    // Can view user skills
    // Can view available skills for assignment
    // Can add skill with level
    // Can update skill level
    // Can remove skill
    // Can search skills
    // Can filter skills by category
    // Can paginate skills
});
