@props(['label', 'status' => 'pending', 'display' => null])

<flux:callout
    variant="{{ ['approved' => 'success', 'rejected' => 'danger'][strtolower($status)] ?? 'secondary' }}"
    icon="{{ ['approved' => 'check-circle', 'rejected' => 'x-circle'][strtolower($status)] ?? 'clock' }}"
    color="{{ ['approved' => 'green', 'rejected' => 'red'][strtolower($status)] ?? 'zinc' }}"
>
    <flux:callout.heading>{{ $label }}</flux:callout.heading>
    <flux:callout.text class="text-xl font-semibold">{{ $display ?? ucfirst($status) }}</flux:callout.text>
</flux:callout>
