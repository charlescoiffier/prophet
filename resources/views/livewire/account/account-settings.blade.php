@php
    $label = 'mb-2 block text-xs font-semibold';
    $field = 'w-full border border-line-strong bg-field px-3 py-3 text-[13px] text-ink outline-none focus:border-ink';
    $hint = 'mt-1.5 text-[11px] leading-relaxed text-muted';
    $error = 'mt-1.5 block text-xs text-negative-text';
    $section = 'border-t border-ink py-6';
    $heading = 'mb-5 font-serif text-[27px] leading-none font-extrabold';
    $button = 'inline-flex min-h-12 cursor-pointer items-center bg-accent px-5 text-[13px] font-semibold text-white hover:bg-accent-dark disabled:opacity-60';
    $status = 'mb-5 border border-ink bg-signal-yellow px-4 py-3 text-[13px]';
@endphp
<div class="flex flex-col">
    <header class="pt-7 pb-6 lg:pt-[35px] lg:pb-[30px]">
        <h1 class="font-serif text-[clamp(40px,4.4vw,64px)] leading-[0.97] font-extrabold tracking-[-2px]">Mon compte</h1>
        <p class="mt-4 max-w-[580px] text-[13px] leading-relaxed text-subtle">Seul votre pseudo est public. Votre nom et votre adresse e-mail ne sont jamais publiés.</p>
    </header>

    <div class="grid grid-cols-1 gap-[38px] lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="flex max-w-[760px] flex-col">
            <form wire:submit="saveProfile" class="{{ $section }}">
                <h2 class="{{ $heading }}">Vous</h2>

                @if (session('profile-status'))
                    <p role="status" class="{{ $status }}">{{ session('profile-status') }}</p>
                @endif

                <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="{{ $label }}">Prénom</label>
                        <input id="first_name" type="text" wire:model="first_name" autocomplete="given-name" class="{{ $field }}">
                        @error('first_name') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="last_name" class="{{ $label }}">Nom</label>
                        <input id="last_name" type="text" wire:model="last_name" autocomplete="family-name" class="{{ $field }}">
                        @error('last_name') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-5">
                    <label for="username" class="{{ $label }}">Pseudo *</label>
                    <input id="username" type="text" wire:model="username" autocomplete="username" required class="{{ $field }}">
                    <p class="{{ $hint }}">Affiché publiquement, dans l’historique des cartes. Lettres, chiffres, tirets.</p>
                    @error('username') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label for="email" class="{{ $label }}">Adresse e-mail *</label>
                    <input id="email" type="email" wire:model="email" autocomplete="email" required class="{{ $field }}">
                    <p class="{{ $hint }}">Si vous la changez, un lien de confirmation partira vers la nouvelle adresse.</p>
                    @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" class="{{ $button }}">Enregistrer</button>
            </form>

            <form wire:submit="savePassword" class="{{ $section }}">
                <h2 class="{{ $heading }}">Le mot de passe</h2>

                @if (session('password-status'))
                    <p role="status" class="{{ $status }}">{{ session('password-status') }}</p>
                @endif

                <div class="mb-5 sm:max-w-[360px]">
                    <label for="current_password" class="{{ $label }}">Mot de passe actuel *</label>
                    <input id="current_password" type="password" wire:model="current_password" autocomplete="current-password" required class="{{ $field }}">
                    @error('current_password') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
                <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $label }}">Nouveau mot de passe *</label>
                        <input id="password" type="password" wire:model="password" autocomplete="new-password" required class="{{ $field }}">
                        @error('password') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $label }}">Confirmer *</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required class="{{ $field }}">
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="{{ $button }}">Changer le mot de passe</button>
            </form>
        </div>

        <aside class="self-start border border-ink bg-field lg:sticky lg:top-6">
            <h2 class="border-b border-ink px-5 py-4 font-serif text-[24px] leading-none font-extrabold">Le compte</h2>
            <dl class="flex flex-col px-5 text-[13px] [&>div]:flex [&>div]:flex-col [&>div]:gap-1 [&>div]:border-b [&>div]:border-line [&>div]:py-3 [&>div:last-child]:border-b-0">
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Rôle</dt>
                    <dd class="font-semibold">{{ $user->role->label() }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">E-mail confirmé</dt>
                    <dd>
                        @if ($user->hasVerifiedEmail())
                            le {{ $user->email_verified_at->translatedFormat('j F Y') }}
                        @else
                            <span class="font-semibold text-negative-text">Pas encore</span>
                            <button type="button" wire:click="resendVerification" class="mt-1 block cursor-pointer text-left text-xs underline">Renvoyer le lien</button>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Inscrit le</dt>
                    <dd>{{ $user->created_at->translatedFormat('j F Y') }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</div>
