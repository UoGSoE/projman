<div>
    <flux:heading size="xl" level="1">Profile</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex flex-col md:flex-row gap-6 pt-6">
        {{-- My Skills --}}
        <div class="flex-1 md:w-1/2">
            <flux:heading size="lg" class="mb-4">My Skills</flux:heading>
            <div class="space-y-6">
                @if ($userSkills->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-4">
                        @foreach ($userSkills as $skill)
                            @php
                                // dd($skill->pivot->skill_level);
                            @endphp
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

                                <div class="cursor-pointer">
                                    <flux:text size="sm" class="mb-2">{{ $skill->description }}</flux:text>
                                    <div class="flex justify-between items-center">
                                        <flux:select size="sm" wire:model="userSkillLevels.{{ $skill->id }}"
                                            wire:change="updateSkillLevel({{ $skill->id }}, $event.target.value)"
                                            value="{{ $skill->pivot->skill_level }}">
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

        {{-- Add Skills --}}
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
                            <flux:dropdown position="bottom-center">
                                <button type="button"
                                    class="w-54 rounded-lg p-2 flex items-center gap-2 bg-zinc-100 hover:bg-zinc-200">
                                    <div class="self-stretch w-0.5 bg-zinc-800 rounded-full"></div>
                                    <div>
                                        <flux:heading>Team sync</flux:heading>
                                        <flux:text class="mt-1">10:00 AM</flux:text>
                                    </div>
                                </button>
                                <flux:card class="p-4 w-full">
                                    <div class="flex justify-between self-stretch w-full">
                                        <div>
                                            <div class="flex items-center gap-2 mb-3">
                                                <flux:text class="font-medium block" variant="strong">
                                                    {{ $skill->name }}
                                                </flux:text> |
                                                <flux:text class="text-sm" variant="subtle">
                                                    {{ $skill->skill_category }}
                                                </flux:text>
                                            </div>
                                            <flux:text size="sm" class="mb-3">{{ $skill->description }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </flux:card>


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
</div>
</div>
