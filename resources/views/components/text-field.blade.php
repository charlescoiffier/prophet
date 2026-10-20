@props(['label', 'name', 'type' => 'text'])
<label class="flex flex-col gap-1">
    <span class="text-xs font-semibold text-muted">{{ $label }}</span>
    <input type="{{ $type }}" wire:model="{{ $name }}" {{ $attributes->merge(['class' => 'min-h-11 rounded-md border border-line-strong px-3 outline-none focus:border-accent']) }}>
    @error($name) <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
</label>
