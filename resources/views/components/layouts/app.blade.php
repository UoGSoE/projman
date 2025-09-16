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
    @auth
        <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 print:hidden">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="#"                l
                    logo="https://fluxui.dev/img/demo/logo.png"
                    logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                    name="{{  config('app.name') }}"
                />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>
            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" href="/" :current="request()->routeIs('home')" wire:navigate>Home</flux:sidebar.item>
                <flux:sidebar.item icon="plus-circle" href="{{ route('project.create') }}"
                    :current="request()->routeIs('project.create')" wire:navigate>New work package</flux:sidebar.item>
                <flux:separator class="my-2" />
                <flux:sidebar.item badge="3" icon="list-bullet" href="/projects" :current="request()->routeIs('projects')" wire:navigate>
                        All work packages
                </flux:sidebar.item>
                <flux:sidebar.item icon="chart-bar" href="/staff/heatmap" :current="request()->routeIs('project.heatmap')" wire:navigate>Staff heatmap</flux:sidebar.item>
                <flux:sidebar.item icon="users" href="/staff" :current="request()->routeIs('staff')" wire:navigate>Staff</flux:sidebar.item>
                <flux:sidebar.item icon="user-group" href="/roles" :current="request()->routeIs('groups')" wire:navigate>Roles</flux:sidebar.item>
                <flux:sidebar.item icon="academic-cap" href="/skills" :current="request()->routeIs('skills.manage')" wire:navigate>Skills</flux:sidebar.item>
            </flux:sidebar.nav>
            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
                <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
            </flux:sidebar.nav>
            <flux:sidebar.nav>
                <flux:sidebar.item tooltip="Logout" icon="arrow-right-start-on-rectangle">
                    <form method="post" action="{{ route('auth.logout') }}">
                        @csrf
                        <flux:button class="w-full" type="submit">
                            <span class="hidden sm:block">Logout</span>
                        </flux:button>
                    </form>
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>
    @endauth
    <flux:header class="lg:hidden print:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" alignt="start">

            <flux:menu>
                <flux:menu.item icon="arrow-right-start-on-rectangle">
                    <form method="post" action="{{ route('auth.logout') }}">
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
    @stack('scripts')
</body>

</html>
