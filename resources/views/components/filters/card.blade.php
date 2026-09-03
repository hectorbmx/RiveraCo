@props([
    'action' => null,
    'method' => 'GET',
    'columns' => 'grid grid-cols-1 md:grid-cols-12 gap-3 items-end',
])

@php
    $formMethod = strtoupper($method) === 'GET' ? 'GET' : 'POST';
@endphp

<form method="{{ $formMethod }}" @if($action) action="{{ $action }}" @endif {{ $attributes->merge(['class' => 'bg-[#0B265A] rounded-2xl shadow-lg p-4']) }}>
    @if($formMethod !== 'GET')
        @csrf
        @method($method)
    @endif

    <div class="{{ $columns }}">
        {{ $slot }}
    </div>
</form>
