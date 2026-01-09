<x-layouts.export :title="$project->title">
    {{-- Header --}}
    <header class="header">
        <div class="container">
            <p class="header-institution">University of Glasgow</p>
            <h1 class="header-title">{{ $project->title }}</h1>
            <p class="header-reference">Work Package Reference #{{ $project->id }}</p>
        </div>
    </header>

    <main class="main">
        <div class="container">
            {{-- Project Overview --}}
            <section class="card">
                <h2 class="card-title">Overview</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <p class="field-label">Status</p>
                        <p class="field-value">
                            <span class="badge badge-blue">{{ $project->status->label() }}</span>
                        </p>
                    </div>
                    <div class="field">
                        <p class="field-label">Owner</p>
                        <p class="field-value">{{ $project->user->full_name }}</p>
                    </div>
                    @if($project->school_group)
                    <div class="field">
                        <p class="field-label">School/Group</p>
                        <p class="field-value">{{ $project->school_group }}</p>
                    </div>
                    @endif
                    @if($project->deadline)
                    <div class="field">
                        <p class="field-label">Deadline</p>
                        <p class="field-value">{{ $project->deadline->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </section>

            {{-- Stage Sections --}}
            @include('exports.partials._ideation')
            @include('exports.partials._feasibility')
            @include('exports.partials._scoping')
            @include('exports.partials._scheduling')
            @include('exports.partials._detailed-design')

            @if($project->scoping?->requires_software_dev !== false)
                @include('exports.partials._development')
            @endif

            @include('exports.partials._build')
            @include('exports.partials._testing')
            @include('exports.partials._deployed')
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Exported on {{ $exportDate->format('d/m/Y H:i') }}</p>
            <p>{{ config('app.name') }}</p>
        </div>
    </footer>
</x-layouts.export>
