<?php

use App\Enums\SkillLevel;
use App\Livewire\ProjectEditor;
use App\Livewire\UserList;
use App\Models\Project;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Skill Matching', function () {
//display a list of skills
//

it('can sort a list of people with most applicable skill level for a given competency',function(){
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
    $project->scoping->skills_required = [1,3];
    $skillsRequired = $project->scoping->skills_required;
    // dd(Skill::all()->pluck('id'));
    // dd($skillsRequired);

    $userList =[];
    $requiredSkillIds = [3, 2];

    //* Approach 1**************************************************** */

    foreach(User::with('skills')->get() as $user){
        foreach(Skill::all() as $skill){
            if($user->hasSkill($skill->id)){
                $userList[$user->id][$skill->id] = $user->getSkillLevel($skill);
            }
        }
    }
    // dd($userList);
    // expect($userList)->toBeArray();

    //* Approach 2***************************************************** */
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
    ->map(function ($user)  {
        $totalScore = $user->skills->sum(function ($skill) use ($user) {
            $level = SkillLevel::from($user->getSkillLevel($skill));
            return $level->getNumericValue();
        });
        $user->total_skill_score = $totalScore;
        return $user;
    })
    ->sortByDesc('total_skill_score')
    ->toArray();
    // })
    // ->mapWithKeys(function ($user) use ($requiredSkillIds) {
    //     $skills = $user->skills->mapWithKeys(function ($skill) use ($user) {
    //         // Use the method since no pivot data
    //         // dd($skill instanceof Skill, $skill);
    //         $level = SkillLevel::from($user->getSkillLevel($skill));
    //         dd($level);
    //         return [$skill->id => $level];
    //     });

    //     return [$user->id => $skills];
    // })
    // ->toArray();

    //***************************************************** */
    // dd($users, $userList);
    //Calculate score for each user for each skill (later to be used to sort the list for scheduling form)

    // $component = livewire(ProjectEditor::class, ['project' => $this->project]);
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
        // Total: 5

        $user3->skills()->attach($skill1->id, ['skill_level' => SkillLevel::BEGINNER->value]); // Score: 1
        // Total: 1

        // Test matching for skills 1 and 2
        $matchedUsers = (new ProjectEditor())->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        expect($matchedUsers)->toHaveCount(3); // user1, user2, and user3 (all have skill1)
        expect($matchedUsers->first()->id)->toBe($user1->id); // user1 should be first (score: 5)
        expect($matchedUsers->first()->total_skill_score)->toBe(5);
        expect($matchedUsers->last()->id)->toBe($user3->id); // user3 should be last (score: 1)
        expect($matchedUsers->last()->total_skill_score)->toBe(1);
    });

    it('returns empty collection when no required skills provided', function () {
        $matchedUsers = (new ProjectEditor())->getUsersMatchedBySkills([]);
        expect($matchedUsers)->toBeEmpty();
    });

    it('returns empty collection when no users have required skills', function () {
        $skill = Skill::factory()->create();
        $user = User::factory()->create();

        $matchedUsers = (new ProjectEditor())->getUsersMatchedBySkills([$skill->id]);
        expect($matchedUsers)->toBeEmpty();
    });

    it('only returns users who have at least one of the required skills', function () {
        $skill1 = Skill::factory()->create(['name' => 'Laravel']);
        $skill2 = Skill::factory()->create(['name' => 'React']);
        $skill3 = Skill::factory()->create(['name' => 'Vue']);

        $user1 = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
        $user2 = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
        $user3 = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Johnson']);

        $user1->skills()->attach($skill1->id, ['skill_level' => SkillLevel::ADVANCED->value]);
        $user1->skills()->attach($skill2->id, ['skill_level' => SkillLevel::INTERMEDIATE->value]);

        $user2->skills()->attach($skill3->id, ['skill_level' => SkillLevel::ADVANCED->value]);

        $matchedUsers = (new ProjectEditor())->getUsersMatchedBySkills([$skill1->id, $skill2->id]);

        expect($matchedUsers)->toHaveCount(1); // Only user1 should match
        expect($matchedUsers->first()->id)->toBe($user1->id);
        expect($matchedUsers->first()->total_skill_score)->toBe(5); // 3 + 2
    });
});
