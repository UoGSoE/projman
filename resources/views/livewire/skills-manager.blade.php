<div>
    <div class="flex justify-between items-center">
        <flux:heading size="xl" level="1">Skills Management</flux:heading>
        <div class="flex items-center gap-2">
            <flux:link :href="route('skills.import')" variant="ghost" icon="arrow-up-tray">Import from Spreadsheet</flux:link>
            <flux:dropdown>
                <flux:button icon="arrow-down-tray">Download</flux:button>
                <flux:menu>
                    <flux:menu.item icon="table-cells" wire:click="downloadExcel">Excel (.xlsx)</flux:menu.item>
                    <flux:menu.item icon="document-text" wire:click="downloadCsv">CSV (.csv)</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    <flux:separator variant="subtle" class="mt-6" />

    <flux:tab.group class="mt-6">
        <flux:tabs wire:model="activeTab">
            <flux:tab name="available-skills">Available Skills</flux:tab>
            <flux:tab name="user-skills">User Skills</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="available-skills">
            <div>
                <flux:input type="text" wire:model.live="skillSearchQuery"
                    placeholder="Search skills by name, description, or category..." class="w-full" />
                <div class="overflow-x-auto">
                    <flux:table :paginate="$skills" class="mt-6">
                        <flux:table.columns>
                            <flux:table.column sortable wire:click="sort('name')">Name</flux:table.column>
                            <flux:table.column sortable wire:click="sort('description')">Description</flux:table.column>
                            <flux:table.column sortable wire:click="sort('skill_category')">Category</flux:table.column>
                            <flux:table.column>Users Count</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($skills as $skill)
                                <flux:table.row :key="'skill-' . $skill->id">
                                    <flux:table.cell>{{ $skill->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->description }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->skill_category }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->users_count }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="user-skills">
            <div>
                <flux:input type="text" wire:model.live="userSearchQuery" placeholder="Search users by name..."
                    class="w-full" />

                <flux:table class="mt-6">
                    <flux:table.columns>
                        <flux:table.column>User</flux:table.column>
                        <flux:table.column>Skills</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($users as $user)
                            <flux:table.row :key="'user-' . $user->id">
                                <flux:table.cell>
                                    <flux:link :href="route('user.show', $user)">{{ $user->full_name }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @forelse($user->skills as $skill)
                                        @if ($loop->index < $maxDisplayedSkills)
                                            <flux:tooltip>
                                                <flux:badge size="sm" variant="outline" inset="top bottom"
                                                    :color="\App\Enums\SkillLevel::from($skill->pivot->skill_level)->colour()" class="cursor-help">
                                                    {{ $skill->name }}
                                                </flux:badge>
                                                <flux:tooltip.content>
                                                    <div class="text-center">
                                                        <div class="font-semibold">{{ \App\Enums\SkillLevel::from($skill->pivot->skill_level)->getDisplayName() }}
                                                        </div>
                                                    </div>
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        @endif
                                    @empty
                                        <flux:text class="text-sm">No skills assigned</flux:text>
                                    @endforelse

                                    @if ($user->skills->count() > $maxDisplayedSkills)
                                        <flux:tooltip>
                                            <flux:badge size="sm" variant="outline" color="gray"
                                                class="cursor-help">
                                                +{{ $user->skills->count() - $maxDisplayedSkills }} more
                                            </flux:badge>
                                            <flux:tooltip.content>
                                                <div class="max-w-xs max-h-48 overflow-y-auto space-y-1">
                                                    @foreach ($user->skills->skip($maxDisplayedSkills) as $skill)
                                                        <div class="flex">
                                                            <flux:tooltip>
                                                                <flux:badge size="sm" variant="outline"
                                                                    :color="\App\Enums\SkillLevel::from($skill->pivot->skill_level)->colour()"
                                                                    class="cursor-help">
                                                                    {{ $skill->name }}
                                                                </flux:badge>
                                                                <flux:tooltip.content>
                                                                    <div class="text-center">
                                                                        <div class="font-semibold">
                                                                            {{ \App\Enums\SkillLevel::from($skill->pivot->skill_level)->getDisplayName() }}</div>
                                                                    </div>
                                                                </flux:tooltip.content>
                                                            </flux:tooltip>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="2">No users found</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>
