<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">Staff</flux:heading>
        <flux:button variant="primary" size="sm" icon="plus" wire:click="openUserModal">Create User</flux:button>
    </div>

    <flux:separator variant="subtle" class="mt-6" />

    <flux:input type="text" wire:model.live="search" placeholder="Search" class="mt-6 w-full" />

    <flux:separator variant="subtle" class="mt-6" />

    <flux:table :paginate="$users" class="mt-6" >
        <flux:table.columns>
            <flux:table.column sortable wire:click="sort('surname')">Surname</flux:table.column>
            <flux:table.column sortable wire:click="sort('forenames')">Forename</flux:table.column>
            <flux:table.column sortable wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column >Roles</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($users as $user)
                <flux:table.row :key="'user-' . $user->id">
                    <flux:table.cell>
                        <flux:link :href="route('user.show', $user)" class="hover:underline">
                            {{ $user->surname }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->forenames }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" class="transition-all duration-300"
                            :color="$user->typeColour()" inset="top bottom">
                            {{ $user->typeLabel() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->roles->count() > 0)
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" inset="top bottom">
                                    {{ $user->roles->first()->name }}
                                </flux:badge>
                                @if ($user->roles->count() > 1)
                                    <flux:tooltip>
                                        <flux:badge size="sm" variant="outline" inset="top bottom">
                                            +{{ $user->roles->count() - 1 }}
                                        </flux:badge>
                                        <flux:tooltip.content>
                                            <div class="space-y-1 flex flex-wrap gap-3 flex-col">
                                                <flux:text class="font-bold">Roles:</flux:text>
                                                @foreach ($user->roles->take(10) as $role)
                                                    <flux:badge size="sm" variant="outline" inset="top bottom">
                                                        {{ $role->name }}
                                                    </flux:badge>
                                                @endforeach
                                                @if ($user->roles->count() > 11)
                                                    <flux:text class="text-sm text-gray-500">... and
                                                        {{ $user->roles->count() - 11 }} more</flux:text>
                                                @endif
                                            </div>
                                        </flux:tooltip.content>
                                    </flux:tooltip>
                                @endif
                            </div>
                        @else
                            <flux:text class="text-gray-500 text-sm">No roles</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square"
                                    wire:click="openUserModal({{ $user->id }})">Edit User</flux:menu.item>
                                <flux:menu.item icon="plus" wire:click="toggleAdmin({{ $user->id }})">Toggle
                                    admin</flux:menu.item>
                                <flux:menu.item icon="wrench-screwdriver" wire:click="toggleItStaff({{ $user->id }})">Toggle
                                    IT staff</flux:menu.item>
                                <flux:menu.item icon="user-group">
                                    <flux:modal.trigger name="change-user-role"
                                        wire:click="openChangeUserRoleModal({{ $user->id }})">
                                        Change User Role
                                    </flux:modal.trigger>
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.submenu heading="Sort by">
                                    <flux:menu.radio.group>
                                        <flux:menu.radio checked>Name</flux:menu.radio>
                                        <flux:menu.radio>Date</flux:menu.radio>
                                        <flux:menu.radio>Popularity</flux:menu.radio>
                                    </flux:menu.radio.group>
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
    <flux:modal name="user-form" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $userAttributes['id'] ? 'Edit User' : 'Create User' }}</flux:heading>
                <flux:text class="mt-2">
                    {{ $userAttributes['id'] ? 'Update this user\'s details.' : 'Create a new staff user account.' }}
                </flux:text>
            </div>
            <form wire:submit="saveUser">
                <div class="space-y-4 max-w-sm">
                    <flux:input wire:model="userAttributes.username" label="Username" placeholder="e.g. jsmith" />
                    <flux:input wire:model="userAttributes.email" label="Email" type="email" placeholder="e.g. john.smith@example.ac.uk" />
                    <flux:input wire:model="userAttributes.surname" label="Surname" />
                    <flux:input wire:model="userAttributes.forenames" label="Forenames" />
                    <flux:checkbox wire:model="userAttributes.is_admin" label="Administrator" description="Grant this user admin privileges" />
                    <flux:checkbox wire:model="userAttributes.is_itstaff" label="IT staff" description="Show this user in IT-team project assignment dropdowns" />
                </div>

                <div class="flex gap-3 mt-6">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">
                        {{ $userAttributes['id'] ? 'Save Changes' : 'Create User' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal name="change-user-role" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Change User Role</flux:heading>
                <flux:text class="mt-2">Click on roles to assign or remove them from this user.
                </flux:text>
            </div>
            <form wire:submit="saveUserRoles">
                <div class="space-y-4 max-w-sm">
                    <flux:input label="User"
                        :value="$selectedUser ? $selectedUser->forenames . ' ' . $selectedUser->surname : ''"
                        readonly disabled />

                    <flux:checkbox.group wire:model.live="userRoles"
                     label="User Roles"
                     variant="cards"
                     class="flex-col">
                        @foreach ($availableRoles as $role)
                            <flux:checkbox value="{{ $role->name }}"
                                label="{{ ucfirst($role->name) }}"
                                :checked="in_array($role->name, (array)$userRoles)"
                                description="{{ ucfirst($role->description) }}" />
                        @endforeach
                    </flux:checkbox.group>

                </div>

                <div class="flex gap-3 mt-6">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Save Changes</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
