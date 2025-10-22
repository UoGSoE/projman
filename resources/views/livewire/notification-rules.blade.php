<div>

    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl" level="1">Notification Rules</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500">
                Notification rules are used to configure how email notifications are sent to users when certain events
                occur.
            </flux:text>
        </div>
        <flux:modal.trigger name="create-rule-modal">
            <flux:button>Create a new rule</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator variant="subtle" class="mt-6" />

    <livewire:notification-rules-table />

    <flux:modal name="create-rule-modal" variant="flyout">

        <form wire:submit="saveRule">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create a new notification rule</flux:heading>
                    <flux:text class="mt-2 text-wrap">Send notifications to users when certain
                        events occur.
                    </flux:text>
                </div>


                <div class="space-y-4 max-w-sm">
                    <flux:field>
                        <flux:input wire:model.live="ruleName" label="Name" required
                            description="Name of the notification rule" />
                        <flux:error name="ruleName" />
                    </flux:field>
                    <flux:field>
                        <flux:textarea wire:model.live="ruleDescription" label="Description" required
                            description="Description of the notification rule" />
                        <flux:error name="ruleDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:select wire:model.live="ruleEvent" label="Event" required
                            description="When does the notification rule trigger?">
                            @foreach ($events as $event)
                                <flux:select.option value="{{ $event['class'] }}">
                                    {{ $event['label'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="ruleEvent" />
                    </flux:field>

                    @if ($ruleEvent === \App\Events\ProjectStageChange::class)
                        <flux:field>
                            <flux:select wire:model.live="selectedProjectStage" label="Project Stage" required
                                description="Which project stage should trigger this notification?">
                                @foreach ($projectStages as $value => $label)
                                    <flux:select.option value="{{ $value }}">
                                        {{ $label }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="selectedProjectStage" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:select wire:model.live="recipientTypes" label="Recipient" required
                            description="Who recieves notifications?">
                            <flux:select.option value="roles">Roles</flux:select.option>
                            <flux:select.option value="users">Users</flux:select.option>
                        </flux:select>
                        <flux:error name="recipientTypes" />
                    </flux:field>

                    @if ($recipientTypes === 'roles')
                        <flux:field>
                            <flux:pillbox multiple placeholder="Choose roles..." wire:model.live="selectedRoles"
                                searchable :disabled="true">
                                @foreach ($roles as $id => $name)
                                    <flux:pillbox.option value="{{ $id }}">{{ $name }}
                                    </flux:pillbox.option>
                                @endforeach
                            </flux:pillbox>
                            <flux:error name="selectedRoles" />
                        </flux:field>
                    @endif

                    @if ($recipientTypes === 'users')
                        <flux:field>
                            <flux:pillbox multiple placeholder="Choose users..." wire:model.live="selectedUsers"
                                searchable :disabled="true">
                                @foreach ($users as $id => $name)
                                    <flux:pillbox.option value="{{ $id }}">
                                        {{ $name }}
                                    </flux:pillbox.option>
                                @endforeach
                            </flux:pillbox>
                            <flux:error name="selectedUsers" />
                        </flux:field>
                    @endif
                    <flux:field variant="inline">
                        <flux:label>Active?</flux:label>
                        <flux:switch wire:model.live="ruleStatus" required
                            description="Toggle the notification active status" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" wire:click="resetCreateRuleModal">Cancel
                        </flux:button>
                    </flux:modal.close>
                    @if ($formModified)
                        <flux:button variant="primary" type="submit">
                            Save Rule
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </flux:modal>

</div>
