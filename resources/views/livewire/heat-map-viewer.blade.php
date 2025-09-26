<div>
    <flux:heading size="xl" level="1">Staff Heatmap</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div>
        <div class="grid grid-cols-11 gap-1 auto-cols-max">

            @foreach ($busynessData['users'] as $user)
                <flux:text class="whitespace-nowrap">{{ $user['name'] }}</flux:text>
                @foreach ($user['days'] as $day)
                    <div class="{{ $day['busyness']['color'] }}">
                    </div>
                @endforeach
            @endforeach
        </div>


    </div>
