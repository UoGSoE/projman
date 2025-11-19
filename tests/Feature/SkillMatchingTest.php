<?php

use App\Enums\SkillLevel;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Skill Matching', function () {
    beforeEach(function () {
        // Fake notifications for this test suite (doesn't test notification behavior)
        $this->fakeNotifications();
    });

    it('can sort a list of people with most applicable skill level for a given competency', function () {
        $skill1 = Skill::factory()->create(['name' => 'Laravel', 'skill_category' => 'Programming Languages', 'description' => 'PHP framework for web development']);
        $skill2 = Skill::factory()->create(['name' => 'React', 'skill_category' => 'Programming Languages', 'description' => 'JavaScript library for building user interfaces']);
        $skill3 = Skill::factory()->create(['name' => 'Project Management', 'skill_category' => 'Management', 'description' => 'Managing projects and teams']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();
        $user5 = User::factory()->create();
        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::BEGINNER->value]);
        $user2->skills()->attach($skill2->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]);
        $user3->skills()->attach($skill1->id, ['skill_level' => SkillLevel::ADVANCED->value]);
        $user5->skills()->attach($skill3->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]);
        $user5->skills()->attach($skill2->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]);
        // dd($user5->hasSkill($skill3->id));

        $project = Project::factory()->create();
        $project->scoping->skills_required = [1, 3];

        $requiredSkillIds = [3, 2];

        $users = User::with(['skills' => function ($query) use ($requiredSkillIds) {
            // eager load only skills with ids in the array requiredSkillIds for each user
            // this helps to not include skills we dont need to match
            $query->whereIn('skill_id', $requiredSkillIds);
        }])
            ->whereHas('skills', function ($query) use ($requiredSkillIds) {
                // whereHas filters the users to only include those with skills with ids in the array requiredSkillIds
                $query->whereIn('skill_id', $requiredSkillIds);
            })
            ->get()
            ->map(function ($user) {
                $totalScore = $user->skills->sum(function ($skill) use ($user) {
                    $level = SkillLevel::from($user->getSkillLevel($skill));

                    return $level->getNumericValue();
                });
                $user->total_skill_score = $totalScore;

                return $user;
            })
            ->sortByDesc('total_skill_score')
            ->values();

        // dd($users);
        expect($users)->toHaveCount(2);
        expect($users->pluck('id')->toArray())->toBe([$user5->id, $user2->id]);
        expect($users->pluck('total_skill_score')->toArray())->toBe([4, 2]);
    });

    it('can get users matched by skills and sorted by score', function () {

        $skill1 = Skill::factory()->create(['name' => 'Laravel']);
        $skill2 = Skill::factory()->create(['name' => 'React']);
        $skill3 = Skill::factory()->create(['name' => 'Project Management']);

        $user1 = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
        $user2 = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
        $user3 = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Johnson']);

        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::ADVANCED->value]); // Score: 3
        $user1->skills()->attach($skill2->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]); // Score: 2
        // Total: 5

        $user2->skills()->attach($skill1->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]); // Score: 2
        $user2->skills()->attach($skill3->id, ['skill_level' => SkillLevel::ADVANCED->value]); // Score: 3
        // Total: 2 (only skill1 matches required skills)

        $user3->skills()->attach($skill1->id, ['skill_level' => SkillLevel::BEGINNER->value]); // Score: 1
        // Total: 1

        // Test matching for skills 1 and 2
        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        // Now returns ALL staff (4 total: user1, user2, user3 + 1 from fakeNotifications)
        expect($matchedUsers)->toHaveCount(4);
        expect($matchedUsers->first()->id)->toBe($user1->id); // user1 should be first (score: 5)
        expect($matchedUsers->first()->total_skill_score)->toBe(5);
        expect($matchedUsers[1]->id)->toBe($user2->id); // user2 second (score: 2)
        expect($matchedUsers[1]->total_skill_score)->toBe(2);
        expect($matchedUsers[2]->id)->toBe($user3->id); // user3 third (score: 1)
        expect($matchedUsers[2]->total_skill_score)->toBe(1);
        // Fourth user (from fakeNotifications) has score: 0
        expect($matchedUsers->last()->total_skill_score)->toBe(0);
    });

    it('returns all staff sorted alphabetically when no required skills provided', function () {
        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([]);

        // Should return all staff users sorted by surname (1 from fakeNotifications)
        expect($matchedUsers)->toHaveCount(1);
        expect($matchedUsers->first()->total_skill_score)->toBe(0);
    });

    it('returns all staff with score 0 when no users have required skills', function () {
        $skill = Skill::factory()->create();
        $user = User::factory()->create();

        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill->id]);

        // Should return all staff (2 total: test user + 1 from fakeNotifications), all with score 0
        expect($matchedUsers)->toHaveCount(2);
        expect($matchedUsers->first()->total_skill_score)->toBe(0);
        expect($matchedUsers->last()->total_skill_score)->toBe(0);
    });

    it('returns all staff with matched users sorted first by skill score', function () {
        $skill1 = Skill::factory()->create(['name' => 'Laravel']);
        $skill2 = Skill::factory()->create(['name' => 'React']);
        $skill3 = Skill::factory()->create(['name' => 'Vue']);

        $user1 = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
        $user2 = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
        $user3 = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Johnson']);

        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::ADVANCED->value]);
        $user1->skills()->attach($skill2->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]);

        $user2->skills()->attach($skill3->id, ['skill_level' => SkillLevel::ADVANCED->value]);

        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        // Now returns ALL staff (4 total), with user1 first (score 5), others with score 0
        expect($matchedUsers)->toHaveCount(4);
        expect($matchedUsers->first()->id)->toBe($user1->id);
        expect($matchedUsers->first()->total_skill_score)->toBe(5); // 3 + 2
        // Remaining users have score 0
        expect($matchedUsers[1]->total_skill_score)->toBe(0);
        expect($matchedUsers[2]->total_skill_score)->toBe(0);
        expect($matchedUsers[3]->total_skill_score)->toBe(0);
    });
});
