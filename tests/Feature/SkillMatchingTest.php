<?php

use App\Enums\SkillLevel;
use App\Livewire\UserList;
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
    $skill1 = Skill::factory()->create();
    $skill2 = Skill::factory()->create();
    $skill3 = Skill::factory()->create();
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
    $userList =[];

    foreach(User::with('skills')->get() as $user){
        foreach(Skill::all() as $skill){
            if($user->hasSkill($skill->id)){
                $userList[$user->id][$skill->id] = $user->getSkillLevel($skill);
            }
        }
    }
    dd($userList);

    //TODO:
    //Calculate score for each user for each skill (later to be used to sort the list for scheduling form)
});
});
