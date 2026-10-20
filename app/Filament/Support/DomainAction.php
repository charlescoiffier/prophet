<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Fait remonter dans Filament les erreurs levées par les classes d'action.
 */
class DomainAction
{
    /**
     * Dans une action à fenêtre : l'erreur s'affiche en notification et la fenêtre reste ouverte.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T|null
     */
    public static function run(Action $action, Closure $callback, ?string $success = null): mixed
    {
        try {
            $result = $callback();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
            $action->halt();

            return null;
        }

        if ($success !== null) {
            Notification::make()->success()->title($success)->send();
        }

        return $result;
    }

    /**
     * Dans une page de formulaire : l'erreur s'affiche sous le champ concerné (data.<champ>).
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function inForm(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key) => ["data.{$key}" => $messages])->all()
            );
        }
    }
}
