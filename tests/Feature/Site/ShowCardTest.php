<?php

use App\Enums\CardStatus;
use App\Livewire\Cards\ProposeVerdictForm;
use App\Livewire\Cards\ReportForm;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\Source;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('affiche la fiche publique avec verdict, sources, historique et aperçu de partage', function () {
    $card = Card::factory()->overdue()->promise()->create(['status' => CardStatus::Closed, 'quote' => '« Nous ferons X. »']);
    Source::factory()->for($card)->archived()->create(['outlet' => 'Radio E', 'kind' => 'audio', 'timecode' => '12:34']);
    Verdict::factory()->approved()->create(['card_id' => $card->id, 'value' => 'partially_kept']);
    ActivityLog::factory()->create(['subject_id' => $card->id, 'actor_id' => User::factory()->moderator()->create(['username' => 's.leroy'])->id]);

    $this->get(route('cards.show', $card))
        ->assertOk()
        ->assertSee('<meta property="og:title"', escape: false)
        ->assertSee('Verdict : partiellement tenue')
        ->assertSee('Audio, Radio E')
        ->assertSee('à 12:34')
        ->assertSee('Copie archivée')
        ->assertSee('s.leroy')
        ->assertSee('a publié la carte');
});

it('répond 404 pour une carte non publique, sauf à son auteur', function () {
    $author = User::factory()->create();
    $card = Card::factory()->create(['author_id' => $author->id]);

    $this->get(route('cards.show', $card))->assertNotFound();
    $this->actingAs($author)->get(route('cards.show', $card))->assertOk()->assertSee('Cette carte n’est pas publique');
});

it('n’affiche jamais l’adresse e-mail d’un auteur sans compte', function () {
    $card = Card::factory()->anonymous('secret@example.test')->published()->create(['email_confirmed_at' => now()]);

    $this->get(route('cards.show', $card))->assertOk()->assertDontSee('secret@example.test');
});

it('enregistre un signalement de visiteur', function () {
    $card = Card::factory()->published()->create();

    Livewire::test(ReportForm::class, ['card' => $card])
        ->set('open', true)
        ->set('reason', 'Le lien est mort.')
        ->set('email', 'lecteur@example.test')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('votre signalement a été transmis');

    expect($card->reports()->sole()->reason)->toBe('Le lien est mort.');
});

it('exige l’adresse d’un visiteur pour signaler', function () {
    Livewire::test(ReportForm::class, ['card' => Card::factory()->published()->create()])
        ->set('reason', 'Erreur.')
        ->call('submit')
        ->assertHasErrors(['email' => 'required']);
});

it('montre le formulaire de verdict aux membres et l’envoie', function () {
    Queue::fake();
    $member = User::factory()->create();
    $card = Card::factory()->overdue()->prediction()->create();

    $this->actingAs($member)->get(route('cards.show', $card))->assertSee('Proposer un verdict');

    Livewire::actingAs($member)
        ->test(ProposeVerdictForm::class, ['card' => $card])
        ->set('value', 'wrong')
        ->set('justification', 'Le taux publié est de 7,4 %.')
        ->set('sourceUrl', 'https://example.org/chiffres')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('verdict-proposed');

    expect($card->refresh()->status)->toBe(CardStatus::VerdictProposed);
});

it('refuse une valeur de verdict d’un autre type de carte', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ProposeVerdictForm::class, ['card' => Card::factory()->overdue()->prediction()->create()])
        ->set('value', 'kept')
        ->set('justification', 'x')
        ->set('sourceUrl', 'https://example.org')
        ->call('submit')
        ->assertHasErrors(['value' => 'in']);
});

it('invite un visiteur à se connecter pour proposer un verdict', function () {
    $this->get(route('cards.show', Card::factory()->overdue()->create()))
        ->assertSee('pour proposer un verdict sourcé')
        ->assertDontSee('Envoyer pour relecture');
});
