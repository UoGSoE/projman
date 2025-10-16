<div>

    <flux:input type="text" wire:model.live="search" placeholder="Search" class="mt-6 w-full" />

    <div class="flex items-center gap-6">
        <flux:heading size="lg" class="mb-4 mt-6">Rules</flux:heading>

        <flux:radio.group wire:model="status" variant="pills">
            <flux:radio value="all" label="All" />
            <flux:radio value="active" label="Active" />
            <flux:radio value="inactive" label="Inactive" />
        </flux:radio.group>


    </div>

    <flux:table :paginate="$rules">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Event</flux:table.column>
            <flux:table.column>Applies To</flux:table.column>
            <flux:table.column>Recipients</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($rules as $rule)
                <flux:table.row key="rule-row-{{ $rule->id }}">

                    <flux:table.cell>{{ $rule->name }}</flux:table.cell>
                    <flux:table.cell>{{ $rule->description }}</flux:table.cell>
                    <flux:table.cell>{{ class_basename($rule->event) }}</flux:table.cell>

                    <flux:table.cell>
                        @if (is_array($rule->applies_to) && in_array('all', $rule->applies_to))
                            <flux:badge color="green">All Projects</flux:badge>
                        @elseif (is_array($rule->applies_to))
                            @foreach ($rule->applies_to as $applyId)
                                @php
                                    $project = App\Models\Project::find($applyId);
                                @endphp
                                @if ($project)
                                    <flux:badge color="blue">{{ $project->title }}</flux:badge>
                                @else
                                    <flux:badge color="gray">Unknown Project</flux:badge>
                                @endif
                            @endforeach
                        @else
                            <flux:badge color="gray">—</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if (isset($rule->recipients['roles']) && !empty($rule->recipients['roles']))
                            <div class="mb-1">
                                <span class="text-xs text-gray-500">Roles:</span>
                                @foreach ($rule->recipients['roles'] as $role)
                                    <flux:badge color="purple">{{ ucfirst($role) }}</flux:badge>
                                @endforeach
                            </div>
                        @endif

                        @if (isset($rule->recipients['users']) && !empty($rule->recipients['users']))
                            <div class="mt-1">
                                <span class="text-xs text-gray-500">Users:</span>
                                @foreach ($rule->recipients['users'] as $userId)
                                    @php
                                        $user = App\Models\User::find($userId);
                                    @endphp
                                    @if ($user)
                                        <flux:badge color="amber">{{ $user->forenames }} {{ $user->surname }}
                                        </flux:badge>
                                    @else
                                        <flux:badge color="gray">Unknown User</flux:badge>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($rule->active)
                            <flux:badge color="green">Active</flux:badge>
                        @else
                            <flux:badge color="red">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil">
                                    <flux:modal.trigger name="edit-notification-rule"
                                        wire:click="openEditNotificationRuleModal({{ $rule->id }})">
                                        Edit Notification Rule
                                    </flux:modal.trigger>
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item variant="danger" icon="trash"
                                    wire:click="openDeleteNotificationRuleModal({{ $rule->id }})">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>
