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

    <flux:card class="space-y-4">
        <flux:heading size="lg">API endpoints</flux:heading>
        <flux:text>All endpoints require a Bearer token in the Authorization header.</flux:text>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Endpoint</flux:table.column>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Example</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($endpoints as $ep)
                    <flux:table.row wire:key="ep-{{ $ep['path'] }}">
                        <flux:table.cell class="font-mono whitespace-nowrap">{{ $ep['method'] }} {{ $ep['path'] }}</flux:table.cell>
                        <flux:table.cell>{{ $ep['description'] }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs break-all">{{ $ep['curl'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Connecting PowerBI</flux:heading>
        <flux:text>Use these steps to wire the API up as a PowerBI data source.</flux:text>
        <ol class="list-decimal list-inside space-y-2">
            <li><flux:text>In PowerBI Desktop choose <strong>Get Data</strong> then <strong>Web</strong>.</flux:text></li>
            <li><flux:text>Paste the API base URL: <span class="font-mono">{{ config('app.url') }}/api</span></flux:text></li>
            <li><flux:text>Open <strong>Advanced</strong> and add an HTTP request header — <span class="font-mono">Authorization: Bearer &lt;your-token&gt;</span></flux:text></li>
            <li><flux:text>Set a daily refresh schedule. The skills data changes slowly; daily is plenty.</flux:text></li>
        </ol>
    </flux:card>

    <flux:modal name="add-token" variant="flyout" wire:close="resetTokenModal">
        <div class="space-y-6">
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
                        <flux:button variant="ghost">
                            {{ $plainTextToken ? 'Close' : 'Cancel' }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Create token</flux:button>
                </div>
            </form>

            @if ($plainTextToken)
                <flux:separator variant="subtle" />
                <flux:callout icon="key" variant="success">
                    <flux:callout.heading>Token created</flux:callout.heading>
                    <flux:callout.text>Copy this token now — it will not be shown again.</flux:callout.text>
                    <div x-data="{
                            copied: false,
                            copy() {
                                navigator.clipboard.writeText(@js($plainTextToken)).then(() => {
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                });
                            }
                         }"
                         class="mt-3 flex items-center gap-3">
                        <flux:text class="font-mono break-all flex-1">{{ $plainTextToken }}</flux:text>
                        <flux:button size="sm" icon="clipboard" x-on:click="copy()">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </flux:button>
                    </div>
                </flux:callout>
            @endif
        </div>
    </flux:modal>
</div>
