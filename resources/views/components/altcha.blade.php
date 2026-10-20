{{--
    Vérification anti-robot ALTCHA (sans cookie). La solution est transmise à la propriété
    Livewire « model » ; l'événement « altcha-reset » relance un nouveau défi après un envoi.
--}}
@props(['model' => 'altcha'])
<div class="flex flex-col gap-1">
    <div wire:ignore
         x-data
         x-on:altcha-reset.window="$el.querySelector('altcha-widget')?.reset()">
        <altcha-widget
            challenge="{{ route('altcha.challenge') }}"
            language="fr"
            name="altcha"
            hidelogo
            x-on:verified="$wire.set('{{ $model }}', $event.detail.payload, false)"
            x-on:expired="$wire.set('{{ $model }}', '', false)"
        ></altcha-widget>
    </div>
    @error($model) <span class="text-xs text-negative-text">{{ $message }}</span> @enderror
</div>
