<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} }"
        {{ $getExtraAttributeBag() }}
    >
        @if ($imageUrl = $getImageUrl())
            <img src="{{ $imageUrl }}" alt="" class="aspect-square w-full bg-[#F7F4EA] object-cover">
        @endif
    </div>
</x-dynamic-component>
