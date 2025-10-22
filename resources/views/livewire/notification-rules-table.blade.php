<div>

    <flux:input type="text" wire:model.live="search" placeholder="Search" class="mt-6 w-full" />

    <div class="flex items-center gap-6">
        <flux:heading size="lg" class="mb-4 mt-6">Rules</flux:heading>

        {{-- <flux:radio.group wire:model="status" variant="pills">
            <flux:radio value="all" label="All" />
            <flux:radio value="active" label="Active" />
            <flux:radio value="inactive" label="Inactive" />
        </flux:radio.group> --}}


    </div>

    <flux:table :paginate="$rules">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Event</flux:table.column>
            <flux:table.column>Recipients</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($rules as $rule)
                <flux:table.row key="rule-row-{{ $rule->id }}">

                    <flux:table.cell>{{ $rule->name }}</flux:table.cell>
                    <flux:table.cell>{{ $rule->description }}</flux:table.cell>
                    <flux:table.cell>
                        {{ class_basename($rule->event['class']) }}
                        @if (isset($rule->event['project_stage']))
                            <flux:badge color="blue" size="sm" class="ml-2">
                                {{ ucfirst(str_replace('-', ' ', $rule->event['project_stage'])) }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>


                    <flux:table.cell>

                        @if (isset($rule->recipients['roles']) && !empty($rule->recipients['roles']))
                            @foreach ($rule->recipients['roles'] as $roleId)
                                @if ($loop->index < $maxDisplayedRoles && isset($roles[$roleId]))
                                    <flux:badge color="purple">{{ ucfirst($roles[$roleId]) }}</flux:badge>
                                @endif
                            @endforeach

                            @if (count($rule->recipients['roles']) > $maxDisplayedRoles)
                                <flux:tooltip>
                                    <flux:badge color="gray" variant="outline" size="sm">
                                        +{{ count($rule->recipients['roles']) - $maxDisplayedRoles }} more
                                    </flux:badge>
                                    <flux:tooltip.content>
                                        <div class="space-y-1 flex flex-wrap gap-1 flex-col">
                                            @foreach ($rule->recipients['roles'] as $roleId)
                                                @if (isset($roles[$roleId]))
                                                    <flux:badge color="purple">{{ ucfirst($roles[$roleId]) }}
                                                    </flux:badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            @endif
                        @endif

                        @if (isset($rule->recipients['users']) && !empty($rule->recipients['users']))
                            <div class="mt-1">
                                @foreach ($rule->recipients['users'] as $userId)
                                    @if ($loop->index < $maxDisplayedUsers && isset($users[$userId]))
                                        <flux:badge color="amber">{{ $users[$userId] }}</flux:badge>
                                    @endif
                                @endforeach
                                @if (count($rule->recipients['users']) > $maxDisplayedUsers)
                                    <flux:tooltip>
                                        <flux:badge color="gray" variant="outline" size="sm">
                                            +{{ count($rule->recipients['users']) - $maxDisplayedUsers }} more
                                        </flux:badge>
                                        <flux:tooltip.content>
                                            <div class="space-y-1 flex flex-wrap gap-1 flex-col">
                                                @foreach ($rule->recipients['users'] as $userId)
                                                    @if (isset($users[$userId]))
                                                        <flux:badge color="amber">{{ $users[$userId] }}</flux:badge>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </flux:tooltip.content>
                                    </flux:tooltip>
                                @endif
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

    <flux:modal name="edit-notification-rule" variant="flyout">
        <form wire:submit="updateRule">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit notification rule</flux:heading>
                    <flux:text class="mt-2 text-wrap">Update the notification rule settings.
                    </flux:text>
                </div>

                <div class="space-y-4 max-w-sm">
                    <flux:field>
                        <flux:input wire:model.live="editRuleName" label="Name" required
                            description="Name of the notification rule" />
                        <flux:error name="editRuleName" />
                    </flux:field>
                    <flux:field>
                        <flux:textarea wire:model.live="editRuleDescription" label="Description" required
                            description="Description of the notification rule" />
                        <flux:error name="editRuleDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:select wire:model.live="editRuleEvent" label="Event" required
                            description="When does the notification rule trigger?">
                            @foreach ($events as $event)
                                <flux:select.option value="{{ $event['class'] }}">
                                    {{ $event['label'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editRuleEvent" />
                    </flux:field>

                    @if ($editRuleEvent === \App\Events\ProjectStageChange::class)
                        <flux:field>
                            <flux:select wire:model.live="editSelectedProjectStage" label="Project Stage" required
                                description="Which project stage should trigger this notification?">
                                @foreach ($projectStages as $value => $label)
                                    <flux:select.option value="{{ $value }}">
                                        {{ $label }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="editSelectedProjectStage" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:select wire:model.live="editRecipientTypes" label="Recipient Types" required
                            description="Who recieves notifications?">
                            <flux:select.option value="roles">Roles</flux:select.option>
                            <flux:select.option value="users">Users</flux:select.option>
                        </flux:select>
                        <flux:error name="editRecipientTypes" />
                    </flux:field>

                    @if ($editRecipientTypes === 'roles')
                        <flux:field>
                            <flux:pillbox multiple placeholder="Choose roles..." wire:model.live="editSelectedRoles"
                                searchable :disabled="true">
                                @foreach ($roles as $id => $name)
                                    <flux:pillbox.option value="{{ $id }}">{{ $name }}
                                    </flux:pillbox.option>
                                @endforeach
                            </flux:pillbox>
                            <flux:error name="editSelectedRoles" />
                        </flux:field>
                    @endif

                    @if ($editRecipientTypes === 'users')
                        <flux:field>
                            <flux:pillbox multiple placeholder="Choose users..." wire:model.live="editSelectedUsers"
                                searchable :disabled="true">
                                @foreach ($users as $id => $name)
                                    <flux:pillbox.option value="{{ $id }}">
                                        {{ $name }}
                                    </flux:pillbox.option>
                                @endforeach
                            </flux:pillbox>
                            <flux:error name="editSelectedUsers" />
                        </flux:field>
                    @endif
                    <flux:field variant="inline">
                        <flux:label>Active?</flux:label>
                        <flux:switch wire:model.live="editRuleStatus" required
                            description="Toggle the notification active status" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" wire:click="resetEditForm">Cancel
                        </flux:button>
                    </flux:modal.close>
                    @if ($editFormModified)
                        <flux:button variant="primary" wire:click="updateRule">
                            Update Rule
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-notification-rule">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete notification rule</flux:heading>
                <flux:text class="mt-2 text-wrap">
                    Are you sure you want to delete this notification rule? This action cannot be undone.
                </flux:text>
            </div>

            @if ($deletingRule)
                <div class="border rounded-lg p-4">
                    <flux:text class="font-medium">{{ $deletingRule->name }}</flux:text>
                    <flux:text class="text-sm mt-1">{{ $deletingRule->description }}</flux:text>
                </div>
            @endif

            <div class="flex gap-3">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRule">
                    Delete Rule
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
