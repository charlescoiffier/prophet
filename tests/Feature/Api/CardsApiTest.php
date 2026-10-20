<?php

use App\Enums\CardStatus;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Source;
use App\Models\Theme;
use App\Models\User;
use App\Models\Verdict;
use App\Notifications\ConfirmCardEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('liste seulement les cartes publiques, sans adresse e-mail d’auteur', function () {
    Card::factory()->anonymous('secret@example.test')->published()->create(['email_confirmed_at' => now()]);
    Card::factory()->create();
    Card::factory()->anonymous()->create();

    $this->getJson('/api/v1/cards')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status.value', 'published')
        ->assertJsonPath('data.0.author', null)
        ->assertDontSee('secret@example.test');
});

it('filtre par personnalité, thème, type et échéance', function () {
    $figure = PublicFigure::factory()->create(['slug' => 'jeanne-exemple']);
    $theme = Theme::factory()->create(['slug' => 'sante']);
    $match = Card::factory()->published()->promise()->for($figure)->create(['deadline' => '2027-06-30']);
    $match->themes()->attach($theme);
    Card::factory()->published()->promise()->create(['deadline' => '2027-06-30']);
    Card::factory()->published()->prediction()->for($figure)->create(['deadline' => '2027-06-30']);

    $this->getJson('/api/v1/cards?public_figure=jeanne-exemple&theme=sante&type=promise&deadline_before=2027-12-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('cherche en français dans la citation, avec les variantes de mots', function () {
    $found = Card::factory()->published()->create(['quote' => '« Nous construirons des logements sociaux. »']);
    Card::factory()->published()->create(['quote' => '« L’inflation baissera. »']);

    $this->getJson('/api/v1/cards?q=logement')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $found->id);
});

it('refuse un filtre invalide', function () {
    $this->getJson('/api/v1/cards?status=in_moderation&sort=quote')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'sort']);
});

it('affiche une carte avec ses sources, son verdict et son historique', function () {
    $card = Card::factory()->overdue()->create(['status' => CardStatus::Closed]);
    Source::factory()->for($card)->create();
    Verdict::factory()->approved()->create(['card_id' => $card->id]);
    ActivityLog::factory()->create(['subject_id' => $card->id]);

    $this->getJson("/api/v1/cards/{$card->id}")
        ->assertOk()
        ->assertJsonPath('data.status.label', 'Tranchée')
        ->assertJsonCount(1, 'data.sources')
        ->assertJsonPath('data.verdict.status.value', 'approved')
        ->assertJsonPath('data.history.0.summary', 'statut : En modération → Publiée');
});

it('répond 404 pour une carte non publique ou un identifiant invalide', function () {
    $this->getJson('/api/v1/cards/'.Card::factory()->create()->id)->assertNotFound();
    $this->getJson('/api/v1/cards/pas-un-uuid')->assertNotFound();
});

it('montre sa carte en modération à son auteur', function () {
    $author = User::factory()->create();
    $card = Card::factory()->create(['author_id' => $author->id]);
    Sanctum::actingAs($author);

    $this->getJson("/api/v1/cards/{$card->id}")->assertOk()->assertJsonPath('data.is_own', true);
});

describe('proposition', function () {
    beforeEach(function () {
        Notification::fake();
        Queue::fake();
    });

    function apiProposal(array $overrides = []): array
    {
        return [
            'public_figure_id' => PublicFigure::factory()->create()->id,
            'type' => 'promise',
            'quote' => '« Nous ferons X. »',
            'stated_on' => '2026-10-01',
            'deadline' => '2027-12-31',
            'deadline_nature' => 'explicit',
            'sources' => [['url' => 'https://example.org/entretien', 'kind' => 'video', 'timecode' => '12:34']],
            ...$overrides,
        ];
    }

    it('accepte la proposition d’un visiteur et lui envoie le lien de confirmation', function () {
        $this->postJson('/api/v1/cards', apiProposal(['email' => 'visiteur@example.test']))
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'awaiting_email');

        Notification::assertSentOnDemand(ConfirmCardEmail::class);
    });

    it('exige l’adresse d’un visiteur et au moins une source', function () {
        $this->postJson('/api/v1/cards', apiProposal(['sources' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'sources']);
    });

    it('envoie en modération la proposition d’un membre', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/cards', apiProposal())
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'in_moderation')
            ->assertJsonPath('data.is_own', true);
    });

    it('limite le nombre de propositions par adresse IP', function () {
        foreach (range(1, 10) as $i) {
            $this->postJson('/api/v1/cards', apiProposal(['email' => "v{$i}@example.test"]))->assertCreated();
        }

        $this->postJson('/api/v1/cards', apiProposal(['email' => 'v11@example.test']))->assertTooManyRequests();
    });
});

it('enregistre le signalement d’un visiteur', function () {
    $card = Card::factory()->published()->create();

    $this->postJson("/api/v1/cards/{$card->id}/reports", ['reason' => 'Mauvaise date.', 'email' => 'a@example.test'])
        ->assertCreated()
        ->assertJsonPath('message', 'Merci : votre signalement a été transmis aux modérateurs.');

    expect($card->reports()->sole()->reason)->toBe('Mauvaise date.');
});

it('liste les échéances dépassées en attente de verdict', function () {
    $overdue = Card::factory()->overdue()->create();
    Card::factory()->published()->create(['deadline' => now()->addYear()]);

    $this->getJson('/api/v1/deadlines')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $overdue->id);
});
