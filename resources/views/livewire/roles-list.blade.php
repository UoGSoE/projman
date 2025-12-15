<div>
    <div class="flex justify-between items-center">
        <flux:heading size="xl" level="1">Roles</flux:heading>
        <flux:modal.trigger wire:click="openCreateRoleModal">
            <flux:button>Create Role</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex items-center gap-4 mt-6">
        <flux:input type="text" wire:model.live="search" placeholder="Search" class="flex-1" />

    </div>

    <flux:separator variant="subtle" class="mt-6" />

    <flux:table :paginate="$roles" class="mt-6">
        <flux:table.columns>
            <flux:table.column sortable="name" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable="description" wire:click="sort('description')">Description</flux:table.column>
            <flux:table.column sortable="is_active" wire:click="sort('is_active')">Active</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($roles as $role)
                <flux:table.row :key="'role-' . $role->id">
                    <flux:table.cell>
                        <div class="flex items-center justify-between">
                            {{ $role->name }}
                            <flux:badge size="sm" variant="outline" inset="top bottom">
                                {{ $role->users->count() }}
                            </flux:badge>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $role->description }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $role->is_active ? 'Yes' : 'No' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                            <flux:menu>

                                <flux:menu.item icon="pencil">
                                    <flux:modal.trigger name="edit-role"
                                        wire:click="openEditRoleModal({{ $role->id }})">
                                        Edit Role
                                    </flux:modal.trigger>
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item variant="danger" icon="trash"
                                    wire:click="openDeleteRoleModal({{ $role->id }})">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="edit-role" variant="flyout">
        <form wire:submit="saveEditRole">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $isCreating ? 'Create Role' : 'Edit Role' }}</flux:heading>
                    <flux:text class="mt-2">{{ $isCreating ? 'Create a new role.' : 'Edit the role details.' }}
                    </flux:text>
                </div>

                <div class="space-y-4 max-w-sm">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model.live="roleName" required />
                        <flux:error name="roleName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model.live="roleDescription" />
                        <flux:error name="roleDescription" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Active</flux:label>
                        <flux:description>
                            Active roles will be shown to users.
                        </flux:description>
                        <flux:switch wire:model.live="roleIsActive" />
                        <flux:error name="roleIsActive" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        {{ $isCreating ? 'Create Role' : 'Save Changes' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>


    <flux:modal name="delete-role" class="min-w-[22rem]" wire:submit="deleteRole">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete role?</flux:heading>

                <flux:text class="mt-2">
                    @if ($selectedRole)
                        @if ($selectedRole->users()->count() > 0)
                            <p>"{{ $selectedRole->name }}" role is assigned to {{ $selectedRole->users->count() }}
                                users. Any users with this role will lose access to the associated work packages and alerts.
                            </p>
                        @else
                            <p>Are you sure you want to delete "{{ $selectedRole->name }}" role?
                            </p>
                        @endif
                    @endif
                    <br />
                    <p class="text-red-500">This action cannot be reversed.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button wire:click="deleteRole">Delete
                    role</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
