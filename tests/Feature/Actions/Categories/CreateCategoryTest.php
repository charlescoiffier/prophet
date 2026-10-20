<?php

use App\Actions\Categories\CreateCategory;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

it('reprend les cinq catégories d’origine', function () {
    expect(Category::orderBy('slug')->pluck('slug')->all())
        ->toBe(['autre', 'economiste', 'editorialiste', 'journaliste', 'politique']);
});

it('laisse un modérateur créer une catégorie, sans doublon d’écriture', function () {
    $moderator = User::factory()->moderator()->create();

    $syndicaliste = app(CreateCategory::class)->handle($moderator, 'Syndicaliste');
    $again = app(CreateCategory::class)->handle($moderator, ' syndicaliste ');

    expect($syndicaliste->slug)->toBe('syndicaliste')
        ->and($again->is($syndicaliste))->toBeTrue()
        ->and(Category::where('slug', 'syndicaliste')->count())->toBe(1);
});

it('refuse la création à un membre', function () {
    expect(fn () => app(CreateCategory::class)->handle(User::factory()->create(), 'Autre chose'))
        ->toThrow(AuthorizationException::class);
});
