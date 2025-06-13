<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full">
        <flux:card class="p-8 space-y-6">
            <div class="text-center">
                <flux:heading size="xl">COSE Teaching Software</flux:heading>
                <flux:subheading>Sign in with your university credentials</flux:subheading>
            </div>

            <flux:separator />

            <form wire:submit="login" class="space-y-4">
                @if($error)
                    <div class="p-4 bg-red-50 text-red-600 rounded-lg">
                        {{ $error }}
                    </div>
                @endif
                @error('username')
                    <div class="p-4 bg-red-50 text-red-600 rounded-lg">
                        {{ $message }}
                    </div>
                @enderror
                @error('password')
                    <div class="p-4 bg-red-50 text-red-600 rounded-lg">
                        {{ $message }}
                    </div>
                @enderror

                <flux:input
                    wire:model="username"
                    label="Username"
                    placeholder="Enter your username"
                    icon="user"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="password"
                    label="Password"
                    type="password"
                    icon="lock-closed"
                    required
                />

                <div class="flex items-center justify-between">
                    <flux:checkbox
                        wire:model="remember"
                        label="Remember me"
                    />
                </div>

                <div class="pt-4">
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full"
                    >
                        Sign in
                    </flux:button>
                </div>
            </form>
        </flux:card>

        <div class="mt-4 text-center text-sm text-gray-600">
            Need help? Contact <a href="mailto:it-support@example.com" class="text-blue-600 hover:underline">IT Support</a>
        </div>
    </div>
</div>
