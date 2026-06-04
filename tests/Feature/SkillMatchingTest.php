<?php

use App\Enums\SkillLevel;
use App\Livewire\ProjectEditor;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Skill Matching', function () {
    beforeEach(function () {
        // Fake notifications for this test suite (doesn't test notification behavior)
        $this->fakeNotifications();
    });

    it('sorts matched staff by total skill score across the required competencies', function () {
        $laravel = Skill::factory()->create(['name' => 'Laravel']);
        $react = Skill::factory()->create(['name' => 'React']);
        $projectManagement = Skill::factory()->create(['name' => 'Project Management']);

        $multiMatch = User::factory()->create(['surname' => 'MultiMatch']);   // matches both required skills
        $singleMatch = User::factory()->create(['surname' => 'SingleMatch']); // matches one required skill
        $offTopic = User::factory()->create(['surname' => 'OffTopic']);       // only a non-required skill

        $multiMatch->skills()->attach($projectManagement->id, ['skill_level' => SkillLevel::WORKING->value]); // 2
        $multiMatch->skills()->attach($react->id, ['skill_level' => SkillLevel::WORKING->value]); // 2 -> total 4
        $singleMatch->skills()->attach($react->id, ['skill_level' => SkillLevel::WORKING->value]); // 2
        $offTopic->skills()->attach($laravel->id, ['skill_level' => SkillLevel::EXPERT->value]); // not required -> 0

        $matched = (new ProjectEditor)->getUsersMatchedBySkills([$projectManagement->id, $react->id]);

        // Highest combined score first, then the single match, then everyone else on 0.
        expect($matched->first()->id)->toBe($multiMatch->id)
            ->and($matched->first()->total_skill_score)->toBe(4)
            ->and($matched[1]->id)->toBe($singleMatch->id)
            ->and($matched[1]->total_skill_score)->toBe(2)
            ->and($matched->firstWhere('id', $offTopic->id)->total_skill_score)->toBe(0);
    });

    it('can get users matched by skills and sorted by score', function () {

        $skill1 = Skill::factory()->create(['name' => 'Laravel']);
        $skill2 = Skill::factory()->create(['name' => 'React']);
        $skill3 = Skill::factory()->create(['name' => 'Project Management']);

        $user1 = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
        $user2 = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
        $user3 = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Johnson']);

        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::EXPERT->value]); // Score: 4
        $user1->skills()->attach($skill2->id, ['skill_level' => SkillLevel::WORKING->value]); // Score: 2
        // Total: 6

        $user2->skills()->attach($skill1->id, ['skill_level' => SkillLevel::WORKING->value]); // Score: 2
        $user2->skills()->attach($skill3->id, ['skill_level' => SkillLevel::EXPERT->value]); // Score: 4
        // Total: 2 (only skill1 matches required skills)

        $user3->skills()->attach($skill1->id, ['skill_level' => SkillLevel::AWARENESS->value]); // Awareness is excluded from scoring
        // Total: 0

        // Test matching for skills 1 and 2
        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        // Now returns ALL staff (4 total: user1, user2, user3 + 1 from fakeNotifications)
        expect($matchedUsers)->toHaveCount(4);
        expect($matchedUsers->first()->id)->toBe($user1->id); // user1 should be first (score: 6)
        expect($matchedUsers->first()->total_skill_score)->toBe(6);
        expect($matchedUsers[1]->id)->toBe($user2->id); // user2 second (score: 2)
        expect($matchedUsers[1]->total_skill_score)->toBe(2);
        // user3 (Awareness-only) and the fakeNotifications user both score 0
        expect($matchedUsers[2]->total_skill_score)->toBe(0);
        expect($matchedUsers->last()->total_skill_score)->toBe(0);
    });

    it('returns all staff sorted alphabetically by surname when no required skills are provided', function () {
        User::factory()->create(['forenames' => 'Test', 'surname' => 'Zulu']);
        User::factory()->create(['forenames' => 'Test', 'surname' => 'Alpha']);
        User::factory()->create(['forenames' => 'Test', 'surname' => 'Mike']);

        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([]);

        // The known staff come back in ascending surname order (robust to the fakeNotifications user).
        $knownSurnames = array_values(array_intersect(
            $matchedUsers->pluck('surname')->all(),
            ['Alpha', 'Mike', 'Zulu']
        ));

        expect($knownSurnames)->toBe(['Alpha', 'Mike', 'Zulu'])
            ->and($matchedUsers->firstWhere('surname', 'Alpha')->total_skill_score)->toBe(0);
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

    it('does not count Awareness-level ratings towards the skill match score', function () {
        $skill = Skill::factory()->create(['name' => 'Laravel']);

        $awarenessOnly = User::factory()->create(['forenames' => 'Ann', 'surname' => 'Awareness']);
        $working = User::factory()->create(['forenames' => 'Will', 'surname' => 'Working']);

        $awarenessOnly->skills()->attach($skill->id, ['skill_level' => SkillLevel::AWARENESS->value]);
        $working->skills()->attach($skill->id, ['skill_level' => SkillLevel::WORKING->value]);

        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill->id]);

        $scoresById = $matchedUsers->pluck('total_skill_score', 'id');

        expect($scoresById[$awarenessOnly->id])->toBe(0);
        expect($scoresById[$working->id])->toBe(2);
        expect($matchedUsers->first()->id)->toBe($working->id);
    });

    it('returns all staff with matched users sorted first by skill score', function () {
        $skill1 = Skill::factory()->create(['name' => 'Laravel']);
        $skill2 = Skill::factory()->create(['name' => 'React']);
        $skill3 = Skill::factory()->create(['name' => 'Vue']);

        $user1 = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
        $user2 = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
        $user3 = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Johnson']);

        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::EXPERT->value]);
        $user1->skills()->attach($skill2->id, ['skill_level' => SkillLevel::WORKING->value]);

        $user2->skills()->attach($skill3->id, ['skill_level' => SkillLevel::EXPERT->value]);

        $matchedUsers = (new ProjectEditor)->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        // Now returns ALL staff (4 total), with user1 first (score 5), others with score 0
        expect($matchedUsers)->toHaveCount(4);
        expect($matchedUsers->first()->id)->toBe($user1->id);
        expect($matchedUsers->first()->total_skill_score)->toBe(6); // 4 + 2
        // Remaining users have score 0
        expect($matchedUsers[1]->total_skill_score)->toBe(0);
        expect($matchedUsers[2]->total_skill_score)->toBe(0);
        expect($matchedUsers[3]->total_skill_score)->toBe(0);
    });
});
