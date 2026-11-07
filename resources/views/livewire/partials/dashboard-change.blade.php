@php
    $direction = $change['direction'] ?? 'flat';
    $tone = match ($direction) {
        'up' => 'text-emerald-700',
        'down' => 'text-red-700',
        default => 'text-stone-400',
    };
@endphp
<span class="{{ ($compact ?? false) ? 'text-xs' : 'mt-2 inline-flex text-xs' }} {{ $tone }}">
    @if ($direction === 'up')
        ▲
    @elseif ($direction === 'down')
        ▼
    @else
        –
    @endif
    {{ $change['text'] }}@if (! ($compact ?? false)) vs {{ $against }}@endif
</span>
