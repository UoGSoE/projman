<div>
    <flux:heading size="xl" level="1">Profile</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex flex-col md:flex-row gap-6 pt-6">
        <div class="flex-1 md:w-1/2">
            <flux:heading size="lg" class="mb-4">My Skills</flux:heading>
            <div class="space-y-6">
                @if ($userSkills->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-4">
                        @foreach ($userSkills as $skill)
                            <flux:card class="">
                                <div class="flex">
                                    <div class="flex-1">
                                        <div class="flex flex-col justify-between mb-3 pr-8">
                                            <flux:text class="font-medium block" variant="strong">{{ $skill->name }}
                                            </flux:text>
                                            <flux:text class="text-sm" variant="subtle">{{ $skill->skill_category }}
                                            </flux:text>
                                        </div>
                                    </div>
                                    <div class="-mx-2 -mt-2">
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical"
                                                inset="top bottom" />

                                            <flux:menu>
                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="removeSkill({{ $skill->id }})">
                                                    Remove Skill
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>

                                <div class="cursor-pointer" wire:click="openUpdateSkillLevelModal({{ $skill->id }})">
                                    <flux:text size="sm" class="mb-2">{{ $skill->description }}</flux:text>
                                    <div class="flex justify-between items-center">
                                        <flux:select size="sm" wire:model="newSkillLevel">
                                            @foreach ($skillLevels as $level)
                                                <flux:select.option value="{{ $level->value }}">
                                                    {{ $level->getDisplayName() }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="academic-cap" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">No skills yet</flux:heading>
                        <flux:text class="mb-6">You haven't added any skills to your profile yet. Start by adding some
                            skills to showcase your expertise.</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex-1 md:w-1/2">
            <flux:heading size="lg" class="mb-4">Add Skills</flux:heading>
            <div class="space-y-6">

                <div class="flex gap-4">
                    <flux:input wire:model.live.debounce.300ms="skillSearchQuery"
                        placeholder="Search skills by name, description, or category..." class="w-full" />

                    <flux:field>
                        <flux:select wire:model.live="selectedCategory" size="sm">
                            <flux:select.option value="">All Skill categories</flux:select.option>
                            @foreach ($skillCategories as $category)
                                <flux:select.option value="{{ $category }}">{{ $category }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                @if ($availableSkills->count() > 0)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach ($availableSkills as $skill)
                            <flux:card class="hover:shadow-lg transition-shadow p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <flux:text class="font-medium block" variant="strong">{{ $skill->name }}
                                    </flux:text> |
                                    <flux:text class="text-sm" variant="subtle">{{ $skill->skill_category }}
                                    </flux:text>
                                </div>
                                <flux:text size="sm" class="mb-3">{{ $skill->description }}</flux:text>
                                <div class="flex justify-between items-center">
                                    <flux:dropdown>
                                        <flux:button size="sm" icon="plus" variant="ghost">
                                            Add Skill
                                        </flux:button>
                                        <flux:popover class="min-w-[200px]">
                                            <div class="space-y-4">
                                                <flux:field>
                                                    <flux:label>Select your skill level</flux:label>
                                                    <flux:select wire:model="addSkillLevel"
                                                        wire:change="addSkillWithLevel({{ $skill->id }})">
                                                        @foreach ($skillLevels as $level)
                                                            <flux:select.option value="{{ $level->value }}">
                                                                {{ $level->getDisplayName() }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>
                                            </div>
                                        </flux:popover>
                                    </flux:dropdown>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $availableSkills->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="magnifying-glass" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">No skills found</flux:heading>
                        <flux:text class="mb-6">No skills match your current search criteria. Try adjusting your
                            filters
                            or create a new skill.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>


    <flux:modal name="update-skill-level" variant="flyout" @close="closeUpdateSkillLevelModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Update Skill Level</flux:heading>
                <flux:text class="mt-2">Update your proficiency level for this skill.</flux:text>
            </div>
            @if ($selectedSkill)
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm">{{ $selectedSkill->name }}</flux:heading>
                        <flux:text size="sm">{{ $selectedSkill->description }}</flux:text>
                    </div>
                    <flux:field>
                        <flux:label>Skill Level</flux:label>
                        <flux:select wire:model="newSkillLevel">
                            @foreach ($skillLevels as $level)
                                <flux:select.option value="{{ $level->value }}">
                                    {{ $level->getDisplayName() }} - {{ $level->getDescription() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            @endif
            <div class="flex gap-3">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeUpdateSkillLevelModal">Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button wire:click="updateSkillLevel">Update Level</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="add-existing-skill" variant="flyout" @close="closeAddExistingSkillModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Skill</flux:heading>
                <flux:text class="mt-2">Add this skill to your profile with your proficiency level.
                </flux:text>
            </div>
            @if ($selectedSkill)
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm">{{ $selectedSkill->name }}</flux:heading>
                        <flux:text size="sm">{{ $selectedSkill->description }}</flux:text>
                    </div>
                    <flux:field>
                        <flux:label>Skill Level</flux:label>
                        <flux:select wire:model="addSkillLevel">
                            @foreach ($skillLevels as $level)
                                <flux:select.option value="{{ $level->value }}">
                                    {{ $level->getDisplayName() }} - {{ $level->getDescription() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            @endif
            <div class="flex gap-3">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeAddExistingSkillModal">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirmAddExistingSkill">Add Skill</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
</div>
