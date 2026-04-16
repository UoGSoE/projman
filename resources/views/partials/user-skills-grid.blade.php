{{-- @param $skills - Collection of Skill models with pivot->skill_level loaded --}}

@if ($skills->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @foreach ($skills as $skill)
            <flux:card wire:key="skill-{{ $skill->id }}">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <flux:text variant="strong">{{ $skill->name }}</flux:text>
                        <flux:text class="text-sm" variant="subtle">{{ $skill->skill_category }}</flux:text>
                    </div>
                    @if ($skill->pivot?->skill_level)
                        <flux:badge size="sm" :color="\App\Enums\SkillLevel::from($skill->pivot->skill_level)->getColor()">
                            {{ \App\Enums\SkillLevel::from($skill->pivot->skill_level)->getDisplayName() }}
                        </flux:badge>
                    @endif
                </div>
                <flux:text size="sm">{{ $skill->description }}</flux:text>
            </flux:card>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <flux:icon name="academic-cap" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
        <flux:heading size="lg" class="mb-2">No skills recorded</flux:heading>
        <flux:text>No skills have been assigned yet.</flux:text>
    </div>
@endif
