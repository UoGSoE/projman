<div>
    <flux:heading size="xl" level="1">Staff</flux:heading>

    <flux:separator variant="subtle" class="mt-6"/>

    <flux:input type="text" wire:model.live="search" placeholder="Search" class="mt-6 w-full" />

    <flux:separator variant="subtle" class="mt-6"/>

    <flux:table :paginate="$users" class="mt-6">
        <flux:table.columns>
            <flux:table.column>Surname</flux:table.column>
            <flux:table.column>Forename</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($users as $user)
                <flux:table.row :key="'user-' . $user->id">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $user->surname }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $user->forenames }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" class="transition-all duration-300" :color="$user->isAdmin() ? 'green' : 'gray'" inset="top bottom">{{ $user->isAdmin() ? 'Admin' : 'User' }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                            <flux:menu>
                                <flux:menu.item icon="plus" wire:click="toggleAdmin({{ $user->id }})">Toggle admin</flux:menu.item>

                                <flux:menu.separator />

                                <flux:menu.submenu heading="Sort by">
                                    <flux:menu.radio.group>
                                        <flux:menu.radio checked>Name</flux:menu.radio>
                                        <flux:menu.radio>Date</flux:menu.radio>
                                        <flux:menu.radio>Popularity</flux:menu.radio>
                                    </flux:menu.radio.group>
                                </flux:menu.submenu>

                                <flux:menu.submenu heading="Filter">
                                    <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
                                    <flux:menu.checkbox checked>Published</flux:menu.checkbox>
                                    <flux:menu.checkbox>Archived</flux:menu.checkbox>
                                </flux:menu.submenu>

                                <flux:menu.separator />

                                <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>
