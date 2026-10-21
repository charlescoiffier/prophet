{{--
    Atelier portrait : aperçu de la photo enregistrée et fenêtre de travail (recadrer, découper, tons).
    Le calcul se fait dans le navigateur (resources/js/portrait-studio) ; Livewire ne reçoit que le résultat.
--}}
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="portraitStudio({
            statePath: @js($getStatePath()),
            originalUrl: @js($getOriginalUrl()),
            studio: @js($getStudioDefaults()),
        })"
        x-on:keydown.window="keyDown($event)"
        {{ $getExtraAttributeBag()->class(['flex flex-col gap-3']) }}
    >
        @if ($imageUrl = $getImageUrl())
            <img src="{{ $imageUrl }}" alt="Portrait enregistré" class="aspect-square w-full border border-gray-300 bg-[#F7F4EA] object-cover">
        @endif

        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" x-ref="file" x-on:change="fileChosen($event)">

        <div class="flex flex-wrap gap-2">
            <x-filament::button size="sm" x-on:click="chooseFile()">
                {{ $imageUrl ? 'Choisir une autre photo' : 'Choisir une photo' }}
            </x-filament::button>
            @if ($getOriginalUrl() && filled($getState()['settings'] ?? null))
                <x-filament::button size="sm" color="gray" x-on:click="retouch()">Retoucher</x-filament::button>
            @endif
            @if ($imageUrl)
                <x-filament::button size="sm" color="danger" outlined x-on:click="if (window.confirm('Retirer la photo de cette personnalité ? Elle sera supprimée à l’enregistrement de la fiche.')) remove()">Retirer la photo</x-filament::button>
            @endif
        </div>

        <p x-show="error && ! open" x-text="error" x-cloak class="text-sm text-danger-600"></p>

        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-on:mouseup.window="pointerUp()"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-950/70 p-4"
            >
                <div class="flex w-full max-w-[640px] flex-col gap-3 rounded-lg bg-white p-4 shadow-xl dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Atelier portrait</h2>
                        <div class="flex gap-1" role="tablist">
                            <button type="button" role="tab" x-on:click="goTo('crop')" x-bind:disabled="! canOpen('crop')" x-bind:aria-selected="step === 'crop'" x-bind:class="step === 'crop' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'" class="rounded-md px-2 py-1 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40">1. Recadrer</button>
                            <button type="button" role="tab" x-on:click="goTo('cut')" x-bind:disabled="! canOpen('cut')" x-bind:aria-selected="step === 'cut'" x-bind:class="step === 'cut' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'" class="rounded-md px-2 py-1 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40">2. Découper</button>
                            <button type="button" role="tab" x-on:click="goTo('tones')" x-bind:disabled="! canOpen('tones')" x-bind:aria-selected="step === 'tones'" x-bind:class="step === 'tones' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'" class="rounded-md px-2 py-1 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40">3. Tons</button>
                        </div>
                    </div>

                    {{-- Marge cliquable autour de l'image : un clic juste au-delà du bord s'y aimante. --}}
                    <div
                        x-on:mousedown.prevent="pointerDown($event)"
                        x-on:mousemove="pointerMove($event)"
                        x-bind:style="{ cursor: step === 'crop' ? cursor : 'crosshair' }"
                        class="mx-auto w-full max-w-[calc(100vh-15rem+2rem)] p-4"
                    >
                        <canvas
                            x-ref="canvas"
                            width="600"
                            height="600"
                            class="aspect-square w-full border border-gray-300 bg-[#F7F4EA]"
                        ></canvas>
                    </div>

                    <div x-show="step === 'crop'" class="flex flex-col gap-1 text-sm">
                        <p class="text-gray-500">Déplacez le cadre ou tirez l’un de ses coins.</p>
                        <p x-show="lowResolution" class="text-warning-600">La photo sera agrandie, la qualité baissera.</p>
                    </div>

                    <div x-show="step === 'cut'" class="flex flex-col gap-2 text-sm">
                        <p x-text="hint" class="text-gray-700 dark:text-gray-300"></p>
                        <p class="text-gray-500">Retour arrière : retirer le dernier angle. Échap : abandonner la coupe en cours.</p>
                        <div class="flex gap-2">
                            <x-filament::button size="xs" color="gray" x-on:click="undoCut()" x-bind:disabled="settings.cuts.length === 0 && ! pendingCut && currentCut.length === 0">Annuler la dernière coupe</x-filament::button>
                            <x-filament::button size="xs" color="gray" x-on:click="resetCuts()">Tout recommencer</x-filament::button>
                        </div>
                    </div>

                    <div x-show="step === 'tones'" class="flex flex-col gap-1 text-sm">
                        <label class="flex items-center gap-3">
                            <span class="w-24">Luminosité</span>
                            <input type="range" min="-100" max="100" class="flex-1" x-model.number="settings.brightness">
                        </label>
                        <label class="flex items-center gap-3">
                            <span class="w-24">Contraste</span>
                            <input type="range" min="-100" max="100" class="flex-1" x-model.number="settings.contrast">
                        </label>
                        <label class="flex items-center gap-3">
                            <span class="w-24">Seuil</span>
                            <input type="range" min="0" max="255" class="flex-1" x-model.number="settings.threshold">
                        </label>
                        <label class="flex items-center gap-3">
                            <span class="w-24">Nuances</span>
                            <select class="rounded-md border-gray-300 py-0.5 text-sm dark:bg-gray-800" x-model.number="settings.shades">
                                <option value="0">Aucune (deux tons)</option>
                                <option value="1">Une nuance</option>
                                <option value="2">Deux nuances</option>
                            </select>
                        </label>
                        <label x-show="settings.shades > 0" class="flex items-center gap-3">
                            <span class="w-24">Écart</span>
                            <input type="range" min="10" max="80" class="flex-1" x-model.number="settings.spread">
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" x-model="settings.halo.enabled">
                            <span>Liseré clair</span>
                        </label>
                        <label x-show="settings.halo.enabled" class="flex items-center gap-3">
                            <span class="w-24">Épaisseur</span>
                            <input type="range" min="2" max="20" class="flex-1" x-model.number="settings.halo.width">
                        </label>
                    </div>

                    <p x-show="error" x-text="error" class="text-sm text-danger-600"></p>

                    <div class="flex justify-end gap-2">
                        <x-filament::button color="gray" x-on:click="cancel()">Annuler</x-filament::button>
                        <x-filament::button x-on:click="next()" x-bind:disabled="busy">
                            <span x-text="isLastStep ? 'Valider' : 'Suivant'"></span>
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
