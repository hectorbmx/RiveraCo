@props([
    'name',
    'label',
    'value' => '',
    'placeholder' => '',
    'span' => 'md:col-span-5',
    'type' => 'text',
    'glow' => false,
])

@php
    $inputClass = 'w-full border bg-white rounded-xl px-3 py-2 text-sm transition focus:outline-none focus:bg-white';
    $inputClass .= $glow
        ? ' border-amber-300 focus:border-amber-400 focus:ring-4 focus:ring-yellow-200'
        : ' border-slate-200 focus:ring-2 focus:ring-slate-300/70';
@endphp

<div class="{{ $span }}">
    <label for="{{ $name }}" class="block text-xs font-semibold text-white/85 mb-1">{{ $label }}</label>
    <input id="{{ $name }}"
           type="{{ $type }}"
           name="{{ $name }}"
           value="{{ $value }}"
           placeholder="{{ $placeholder }}"
           {{ $attributes->merge(['class' => $inputClass]) }}
           @if($glow) style="box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.18), 0 0 20px rgba(255, 193, 7, 0.32);" @endif>
</div>
