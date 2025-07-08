<div>
    <div class="flex flex-col gap-4 md:grid md:grid-cols-2 lg:grid-cols-3">
        @foreach ($projects as $project)
            <flux:card class="w-full flex flex-col gap-4">
                <flux:heading size="lg" class="text-lg font-bold">
                    <flux:badge color="zinc">{{ $project->status }}</flux:badge>
                    {{ $project->title }}
                </flux:heading>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.
                    Ratione saepe iure fugiat iusto amet ipsa deleniti exercitationem aliquam odit inventore quo maiores, sed voluptates minima sunt facilis vero dignissimos, nulla asperiores corrupti. Ratione nostrum neque sint minus corporis!
                    Illo facilis dolores in quibusdam corrupti atque, iusto soluta, quos nesciunt delectus ipsa! Laudantium labore impedit, minus cumque delectus maxime? Quo soluta nesciunt provident sapiente error inventore vitae aliquam fugiat.
                </flux:text>
            </flux:card>
        @endforeach
    </div>
</div>
