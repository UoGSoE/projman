<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">


        <title>{{ config('app.name') }}</title>
        @fluxAppearance
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="bg-white dark:bg-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:brand href="/" logo="https://fluxui.dev/img/demo/logo.png" name="{{ config('app.name') }}" class="px-2 dark:hidden" />
            <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="{{ config('app.name') }}" class="px-2 hidden dark:flex" />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="home" href="/" :current="request()->routeIs('home')">Home</flux:navlist.item>
            </flux:navlist>

                <flux:separator />

                <flux:navlist variant="outline">
                    <flux:navlist.item icon="inbox" href="{{ route('home') }}" :current="request()->routeIs('importexport')">Import/Export</flux:navlist.item>
                    <flux:navlist.item icon="document-text" href="/users" :current="request()->routeIs('users')">Manage Users</flux:navlist.item>
                    <flux:navlist.item icon="trash" href="/deleted-software" :current="request()->routeIs('deleted-software')"><span class="flex items-center justify-between"><span>Pending Deletion</span><flux:badge color="amber">3</flux:badge></span></flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth" href="/settings" :current="request()->routeIs('settings')">Settings</flux:navlist.item>
                </flux:navlist>
            <flux:spacer />
            <flux:navlist variant="outline">
                    <flux:navlist.item icon="information-circle" href="/help" :current="request()->routeIs('help')">Help</flux:navlist.item>
            </flux:navlist>
            <div class="flex flex-row gap-2 items-center justify-between">
                <form method="post" action="" class="w-full">
                    @csrf
                    <flux:button class="w-full" icon="arrow-right-start-on-rectangle" type="submit">Logout {{ auth()->check() ? auth()->user()->full_name : '' }}</flux:button>
                </form>
            </div>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" alignt="start">
                <flux:profile name="{{ auth()->check() ? auth()->user()->full_name : 'Guest' }}" />

                <flux:menu>
                    <flux:menu.item icon="arrow-right-start-on-rectangle">
                        <form method="post" action="">
                            @csrf
                            <flux:button type="submit">Logout</flux:button>
                        </form>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        <flux:toast />
        @fluxScripts
    </body>
</html>
