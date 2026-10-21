<?php

namespace App\Actions\Themes;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\Theme;
use App\Models\User;
use App\Support\Names;
use Illuminate\Support\Facades\Gate;

/**
 * Ajoute un thème tapé en saisissant une carte, ou renvoie celui qui existe déjà sous une autre graphie.
 */
class CreateTheme
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $actor, string $name): Theme
    {
        Gate::forUser($actor)->authorize('create', Theme::class);

        $name = trim($name);
        $existing = Theme::all()->first(fn (Theme $theme) => Names::normalize($theme->name) === Names::normalize($name));

        if ($existing !== null) {
            return $existing;
        }

        $theme = Theme::create(['name' => $name]);
        $this->recordActivity->handle($theme, $actor, ActivityAction::ThemeCreated, after: ['name' => $name]);

        return $theme;
    }
}
