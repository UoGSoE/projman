{{--
    Interactive heat map calculation explainer.

    A read-only sandbox that teaches how one work package's scoped effort is
    spread across the people and working days assigned to it, then compared
    against each person's declared availability for change. The maths is pure
    client-side Alpine — this is a Blade component, not Livewire. Its data
    (effort bands, availability levels, counted roles, colour thresholds) is
    supplied by App\View\Components\HeatmapExplainer.

    The option/checkbox lists are rendered server-side with @foreach over the
    PHP data so each Flux control carries its own label; only the live binding
    is Alpine (x-model). Surfaced beside the "Staff Heatmap" heading on both the
    standalone heat map page and the embedded "Model" heatmap on the scheduling
    form. See ait epic projman-CvFSr.
--}}
<div {{ $attributes }}>
    <flux:modal.trigger name="heatmap-explainer">
        <flux:button icon="information-circle" variant="ghost" size="sm">
            How is this calculated?
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="heatmap-explainer" flyout class="w-full md:w-[50vw]">
        <div
            x-data="{
                bands: @js($bands),
                thresholds: @js($thresholds),

                bandDays: 10,
                availPct: 60,
                workingDays: 10,
                checkedSingleRoles: ['Assigned to'],
                multiCounts: { @foreach ($multiRoles as $role => $default) @js($role): {{ $default }}, @endforeach },

                showCalc: false,
                showAssumptions: false,

                get peopleCount() {
                    const extra = Object.values(this.multiCounts).reduce((total, n) => total + (Number(n) || 0), 0);
                    return this.checkedSingleRoles.length + extra;
                },
                get hasResult() {
                    return this.peopleCount > 0 && this.workingDays > 0;
                },
                get perDayShare() {
                    if (! this.hasResult) { return 0; }
                    return this.bandDays / Math.max(1, this.peopleCount) / Math.max(1, this.workingDays);
                },
                get util() {
                    if (! this.hasResult) { return null; }
                    if (this.availPct === 0) { return Infinity; }
                    return this.perDayShare / (this.availPct / 100) * 100;
                },
                get finiteUtil() {
                    return this.hasResult && this.availPct > 0;
                },
                get headlineText() {
                    if (! this.hasResult) { return '—'; }
                    if (this.availPct === 0) { return 'Over capacity'; }
                    return Math.round(this.util) + '%';
                },
                get resultLabel() {
                    if (! this.hasResult) { return 'Tick a delivery role to see the result'; }
                    if (this.availPct === 0) { return 'No change time declared'; }
                    if (this.util > this.thresholds.black) { return 'Over capacity'; }
                    if (this.util >= this.thresholds.red) { return 'Close to full'; }
                    if (this.util >= this.thresholds.amber) { return 'Filling up'; }
                    return 'Room to spare';
                },
                get barClass() {
                    if (! this.hasResult) { return 'bg-zinc-300 dark:bg-zinc-600'; }
                    if (this.availPct === 0 || this.util > this.thresholds.black) { return 'bg-black'; }
                    if (this.util >= this.thresholds.red) { return 'bg-red-500'; }
                    if (this.util >= this.thresholds.amber) { return 'bg-amber-500'; }
                    return 'bg-green-500';
                },
                get fillWidth() {
                    if (! this.hasResult) { return 0; }
                    if (this.availPct === 0) { return 100; }
                    return Math.min(this.util, 100);
                },
                get bandLabel() {
                    const band = this.bands.find((b) => b.days === this.bandDays);
                    return band ? band.label : this.bandDays + ' days';
                },
                get bandShortLabel() {
                    return this.bandLabel.replace(/\s*\(.*\)\s*$/, '');
                },
                get peopleText() {
                    return this.peopleCount === 1 ? '1 person' : this.peopleCount + ' people';
                },
                get dayShareText() {
                    const f = this.perDayShare;
                    if (f <= 0.12) { return 'a small slice of each working day'; }
                    if (f <= 0.30) { return 'roughly a quarter of each working day'; }
                    if (f <= 0.42) { return 'roughly a third of each working day'; }
                    if (f <= 0.58) { return 'about half of each working day'; }
                    if (f <= 0.80) { return 'most of each working day'; }
                    if (f <= 1.10) { return 'pretty much a whole working day'; }
                    return 'more than a whole working day';
                },
                get sentence() {
                    if (! this.hasResult) {
                        return 'Tick at least one delivery role to see how the work would be shared out.';
                    }
                    if (this.availPct === 0) {
                        return 'A ' + this.bandShortLabel + ' work package shared across ' + this.peopleText +
                            ' over ' + this.workingDays + ' working days still needs time from this person — but they have ' +
                            'none of their time set aside for change work, so any project at all shows as over capacity.';
                    }
                    return 'A ' + this.bandShortLabel + ' work package, shared across ' + this.peopleText + ' over ' +
                        this.workingDays + ' working days, works out to ' + this.dayShareText + ' for this person. They have ' +
                        this.availPct + '% of their time set aside for change work — so this one project would use about ' +
                        this.headlineText + ' of it.';
                },
            }"
            class="space-y-6"
        >
            <div>
                <flux:heading size="lg">How the heat map is calculated</flux:heading>
                <flux:text class="mt-2">
                    Set up an imaginary work package and watch how full it would leave one
                    person. This is just a worked example — it does not change any real data.
                </flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select x-model.number="bandDays" label="How big is the project?">
                    @foreach ($bands as $band)
                        <flux:select.option value="{{ $band['days'] }}">{{ $band['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select x-model.number="availPct" label="Availability for change">
                    @foreach ($availabilityLevels as $level)
                        <flux:select.option value="{{ $level['percent'] }}">{{ $level['label'] }} — {{ $level['percent'] }}%</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input type="number" min="1" x-model.number="workingDays" label="Working days in the window" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:checkbox.group x-model="checkedSingleRoles" label="Who is sharing the work?">
                    @foreach ($singleRoles as $role)
                        <flux:checkbox value="{{ $role }}" label="{{ $role }}" />
                    @endforeach
                </flux:checkbox.group>

                <div class="space-y-3">
                    @foreach ($multiRoles as $role => $default)
                        <flux:input type="number" min="0" x-model.number="multiCounts['{{ $role }}']" label="{{ $role }} (how many?)" />
                    @endforeach
                </div>
            </div>

            <flux:separator variant="subtle" />

            {{-- Result: lead with meaning (a capacity gauge + a plain-English sentence);
                 the arithmetic and assumptions sit behind disclosures. --}}
            <div class="space-y-4">
                <div>
                    <flux:text variant="subtle" class="text-sm">How full this would leave their change capacity</flux:text>
                    <div class="mt-1 flex items-baseline gap-3">
                        <flux:heading size="xl" x-text="headlineText"></flux:heading>
                        <flux:text x-text="resultLabel"></flux:text>
                    </div>
                </div>

                <div>
                    <div class="relative h-8 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <div class="h-full transition-all duration-300" :class="barClass" :style="`width: ${fillWidth}%`"></div>
                        <div class="absolute inset-y-0 w-px bg-white/70" style="left: 70%"></div>
                        <div class="absolute inset-y-0 w-px bg-white/70" style="left: 90%"></div>
                    </div>
                    <flux:text variant="subtle" class="mt-1 text-xs">
                        Green room to spare · amber filling up (70%) · red close to full (90%) · black over capacity
                    </flux:text>
                </div>

                <flux:text x-text="sentence"></flux:text>

                <flux:separator variant="subtle" />

                <div class="space-y-1">
                    <flux:button variant="ghost" size="sm" icon="calculator" x-on:click="showCalc = ! showCalc">
                        <span x-text="showCalc ? 'Hide the calculation' : 'Show the calculation'"></span>
                    </flux:button>
                    <div x-show="showCalc" x-collapse class="space-y-2 pt-1">
                        <flux:text class="font-mono text-sm" x-show="hasResult">
                            <span x-text="bandDays"></span> days
                            ÷ <span x-text="peopleCount"></span> <span x-text="peopleCount === 1 ? 'person' : 'people'"></span>
                            ÷ <span x-text="workingDays"></span> working days
                            ÷ <span x-text="availPct"></span>% available
                            = <span class="font-semibold" x-text="headlineText"></span>
                        </flux:text>
                        <flux:text variant="subtle" class="text-sm">
                            Dividing by their availability is what turns “share of a working day” into “share of the
                            time they have set aside for change work” — so lower availability makes the same project
                            look heavier.
                        </flux:text>
                    </div>
                </div>

                <div class="space-y-1">
                    <flux:button variant="ghost" size="sm" icon="information-circle" x-on:click="showAssumptions = ! showAssumptions">
                        <span x-text="showAssumptions ? 'Hide what this assumes' : 'What this assumes'"></span>
                    </flux:button>
                    <div x-show="showAssumptions" x-collapse class="pt-1">
                        <ul class="list-disc space-y-1 pl-5">
                            <li><flux:text variant="subtle" class="text-sm">Effort is spread evenly across the whole window — no busier or quieter phases.</flux:text></li>
                            <li><flux:text variant="subtle" class="text-sm">Everyone sharing it carries an equal load, whether their role is hands-on, advisory or governance.</flux:text></li>
                            <li><flux:text variant="subtle" class="text-sm">Routine operational work is not counted unless a person's availability has been lowered to reflect it.</flux:text></li>
                            <li><flux:text variant="subtle" class="text-sm">The sizes are rough estimate bands, so read the figure as a planning signal, not a timesheet.</flux:text></li>
                        </ul>
                    </div>
                </div>

                <flux:text variant="subtle" class="text-xs">
                    Each person ticked or counted takes an equal share, so adding anyone — even an advisor or
                    assessor — lightens everyone else's load. In the live app one person in two roles counts once,
                    and a real heat map cell adds together every active work package overlapping the period; this
                    models a single package for clarity.
                </flux:text>
            </div>
        </div>
    </flux:modal>
</div>
