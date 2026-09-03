@props([
    'name',
    'label',
    'value' => '',
    'options' => [],
    'span' => 'md:col-span-2 md:max-w-44',
    'placeholder' => null,
])

<div class="{{ $span }}">
    <label for="{{ $name }}" class="block text-xs font-semibold text-white/85 mb-1">{{ $label }}</label>
    <select id="{{ $name }}"
            name="{{ $name }}"
            {{ $attributes->merge(['class' => 'w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300/70']) }}>
        @if(!is_null($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
