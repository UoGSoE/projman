<div class="p-6 space-y-6">
    <flux:heading size="xl">Settings</flux:heading>
    <flux:text>Manage API tokens used by PowerBI and other external tools.</flux:text>

    @if ($plainTextToken)
        <flux:callout icon="key" variant="success">
            <flux:callout.heading>New API token created</flux:callout.heading>
            <flux:callout.text>
                Copy this token now — it will not be shown again.
            </flux:callout.text>
            <flux:text class="font-mono break-all mt-2">{{ $plainTextToken }}</flux:text>
            <x-slot name="actions">
                <flux:button size="sm" wire:click="dismissPlainTextToken">Dismiss</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    <flux:card class="space-y-4">
        <flux:heading size="lg">Create API token</flux:heading>
        <form wire:submit="createToken" class="flex gap-2 items-end">
            <flux:input wire:model="newTokenName" label="Token name" placeholder="e.g. PowerBI Production" />
            <flux:button type="submit" variant="primary">Create token</flux:button>
        </form>
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Existing tokens</flux:heading>
        @if ($tokens->isEmpty())
            <flux:text>No tokens yet. Create one above.</flux:text>
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
</div>
