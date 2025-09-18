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
        $this->user->updateSkill($this->skill1->id, SkillLevel::BEGINNER->value);
        $this->user->updateSkill($this->skill2->id, SkillLevel::INTERMEDIATE->value);

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

        it('displays all skills', function () {
            livewire(Profile::class)
                ->set('showMySkills', false)
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management')
                ->assertSeeText('Vue.js')
                ->assertSeeText('Python');
        });

        it('renders skill card with proper structure', function () {
            livewire(Profile::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('PHP framework for web development')
                ->assertSeeText('Programming')
                ->assertSeeText('React')
                ->assertSeeText('JavaScript library for building user interfaces')
                ->assertSeeText('Frontend');
        });

        it('renders skill level radio group in correct position', function () {
            livewire(Profile::class)
                ->assertSee('Level')
                ->assertSee('None')
                ->assertSee('Beginner')
                ->assertSee('Intermediate')
                ->assertSee('Advanced');
        });
    });

    describe('Search Functionality', function () {
        it('filters skills by name', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', 'Laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React');
        });

        it('filters skills by description', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', 'PHP framework')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React');
        });

        it('requires minimum 2 characters for skill search', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', 'L')
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management');
        });

        it('shows all skills when skill search is empty', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', '')
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management')
                ->assertSeeText('Vue.js')
                ->assertSeeText('Python');
        });

        it('is case insensitive for skill search', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', 'laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React');
        });

        it('resets page when skill search changes', function () {
            livewire(Profile::class)
                ->set('skillSearchQuery', 'Laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React');
        });

        it('orders skills by name', function () {
            $component = livewire(Profile::class);
            $skills = $component->viewData('allSkills');

            $skillNames = $skills->pluck('name')->toArray();
            expect($skillNames)->toBe(['Laravel', 'Project Management', 'Python', 'React', 'Vue.js']);
        });
    });

    describe('Toggle Show My Skills', function () {
        it('shows only my skills when toggled to true', function () {
            livewire(Profile::class)
                ->set('showMySkills', true)
                ->assertSeeText('Laravel')
                ->assertSeeText('React');
        });

        it('shows all skills when toggled to false', function () {
            livewire(Profile::class)
                ->set('showMySkills', false)
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management')
                ->assertSeeText('Vue.js')
                ->assertSeeText('Python');
        });
    });

    describe('Update User Skill', function () {
        it('updates user skill when radio group is changed', function () {
            livewire(Profile::class)
                ->set('userSkill.{$this->skill1->id}.skill_level', SkillLevel::INTERMEDIATE->value)
                ->assertSeeText('Laravel');
        });
    });

    describe('Edge Cases', function () {
        it('handles user with no skills', function () {
            $newUser = User::factory()->create(['is_staff' => true]);
            $this->actingAs($newUser);

            $component = livewire(Profile::class);
            $userSkills = $component->get('userSkill');

            foreach ($userSkills as $skillId => $skillData) {
                expect($skillData['skill_level'])->toBe('none');
            }

            $component->set('showMySkills', true);
            $component->assertSeeText('No skills found');
        });

        it('handles search with special characters', function () {
            $component = livewire(Profile::class);
            $component->set('skillSearchQuery', 'Vue.js');

            $skills = $component->viewData('allSkills');
            expect($skills)->toHaveCount(1);
            expect($skills->first()->name)->toBe('Vue.js');
        });

        it('handles empty search query', function () {
            $component = livewire(Profile::class);
            $component->set('skillSearchQuery', '');

            $skills = $component->viewData('allSkills');
            expect($skills)->toHaveCount(5);
        });
    });
});
