<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">Settings</flux:heading>
        <flux:modal.trigger name="add-token">
            <flux:button variant="primary" size="sm" icon="plus">Add token</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:text>Manage API tokens used by PowerBI and other external tools.</flux:text>

    <flux:separator variant="subtle" />

    <flux:card class="space-y-4">
        <flux:heading size="lg">Existing tokens</flux:heading>
        @if ($tokens->isEmpty())
            <flux:text>No tokens yet. Use the "Add token" button above to create one.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                    <flux:table.column>Last used</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($tokens as $token)
                        <flux:table.row wire:key="token-{{ $token->id }}">
                            <flux:table.cell>{{ $token->name }}</flux:table.cell>
                            <flux:table.cell>{{ $token->created_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="danger"
                                    wire:click="revokeToken({{ $token->id }})"
                                    wire:confirm="Revoke this token? This cannot be undone.">
                                    Revoke
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="add-token" variant="flyout" wire:close="resetTokenModal">
        <div class="space-y-6">
            @if ($plainTextToken)
                <div>
                    <flux:heading size="lg">Token created</flux:heading>
                    <flux:text class="mt-2">Copy this token now — it will not be shown again.</flux:text>
                </div>
                <flux:callout icon="key" variant="success">
                    <flux:text class="font-mono break-all">{{ $plainTextToken }}</flux:text>
                </flux:callout>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="primary">Done</flux:button>
                    </flux:modal.close>
                </div>
            @else
                <div>
                    <flux:heading size="lg">Create API token</flux:heading>
                    <flux:text class="mt-2">Name the token so you can identify it later (e.g. "PowerBI Production").</flux:text>
                </div>
                <form wire:submit="createToken">
                    <div class="space-y-4 max-w-sm">
                        <flux:input wire:model="newTokenName" label="Token name" placeholder="e.g. PowerBI Production" />
                    </div>
                    <div class="flex gap-3 mt-6">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" type="submit">Create token</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>
