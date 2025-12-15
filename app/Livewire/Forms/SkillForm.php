<?php

namespace App\Livewire\Forms;

use App\Models\Skill;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SkillForm extends Form
{
    public ?Skill $skill = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|min:3|max:255')]
    public string $description = '';

    #[Validate('required|string|min:3|max:255')]
    public string $category = '';

    public function setSkill(Skill $skill): void
    {
        $this->skill = $skill;
        $this->name = $skill->name;
        $this->description = $skill->description;
        $this->category = $skill->skill_category;
    }

    public function clear(): void
    {
        $this->skill = null;
        $this->name = '';
        $this->description = '';
        $this->category = '';
    }

    public function save(): Skill
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'skill_category' => $this->category,
        ];

        if ($this->skill) {
            $this->skill->update($data);

            return $this->skill;
        }

        return Skill::create($data);
    }

    public function isEditing(): bool
    {
        return $this->skill !== null;
    }
}
