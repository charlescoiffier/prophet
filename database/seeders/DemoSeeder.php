<?php

namespace Database\Seeders;

use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\PublicFigureCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\SourceKind;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Report;
use App\Models\Theme;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration fictif, repris des maquettes (docs/maquettes).
 * Mot de passe de tous les comptes : « password ».
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, PublicFigure> */
    private array $figures = [];

    public function run(): void
    {
        $this->seedUsers();
        $this->seedPublicFigures();
        $this->seedCards();
    }

    private function seedUsers(): void
    {
        foreach ([
            'admin' => Role::Admin,
            's.leroy' => Role::Moderator,
            'm.durand' => Role::Member,
            'j.martin' => Role::Member,
        ] as $username => $role) {
            $this->users[$username] = User::factory()->create([
                'username' => $username,
                'email' => $username.'@example.test',
                'role' => $role,
            ]);
        }
    }

    private function seedPublicFigures(): void
    {
        foreach ([
            ['Jeanne Exemple', PublicFigureCategory::Politician, 'Candidate à l’élection présidentielle', 'Parti A', 'Candidate'],
            ['Paul Modèle', PublicFigureCategory::Columnist, 'Éditorialiste politique', 'Journal B', 'Éditorialiste'],
            ['Lucie Témoin', PublicFigureCategory::Politician, 'Ancienne ministre', 'Gouvernement C', 'Ministre'],
            ['Marc Hypothèse', PublicFigureCategory::Economist, 'Économiste', 'Institut D', 'Directeur des études'],
            ['Hugo Fiction', PublicFigureCategory::Journalist, 'Chroniqueur radio', 'Radio E', 'Chroniqueur'],
            ['Anne Scénario', PublicFigureCategory::Politician, 'Ministre', 'Gouvernement C', 'Ministre'],
            ['Claire Récit', PublicFigureCategory::Politician, 'Députée', 'Parti F', 'Députée'],
        ] as [$name, $category, $description, $organization, $role]) {
            $figure = PublicFigure::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'category' => $category,
                'description' => $description,
            ]);

            $figure->affiliations()->create([
                'organization' => $organization,
                'role' => $role,
                'started_on' => '2022-01-01',
            ]);

            $this->figures[$name] = $figure;
        }
    }

    private function seedCards(): void
    {
        $sLeroy = $this->users['s.leroy'];

        // Tranchées
        $creches = $this->card('Lucie Témoin', CardType::Promise, '« Nous ouvrirons 10 000 places en crèche d’ici 2025. »', '2023-01-14', '2025-12-31', ['Famille', 'Petite enfance'], CardStatus::Closed, [
            'context' => 'Réponse à une question sur la petite enfance lors d’une interview radio, après l’annonce du plan famille. La ministre précise que l’objectif porte sur la France entière.',
            'testable_statement' => '10 000 places d’accueil collectif supplémentaires ouvertes entre janvier 2023 et le 31 décembre 2025.',
            'verdict_criterion' => 'Solde net de places publié par l’organisme statistique de référence pour 2023 à 2025. Tenue si le total atteint 10 000, partiellement tenue au-delà de la moitié.',
            'author_email' => 'visiteur@example.test',
        ], SourceKind::Audio, 'Radio E', '12:34');
        $this->verdict($creches, VerdictValue::PartiallyKept, $this->users['m.durand'], VerdictStatus::Approved, $sLeroy, '2026-02-12',
            'Les données publiées font état d’environ [N] places nettes ouvertes sur la période, soit un peu plus de la moitié de l’objectif. Le plan a été maintenu jusqu’à son terme.');
        $this->log($creches, null, ActivityAction::Proposed, '2025-03-18');
        $this->log($creches, $sLeroy, ActivityAction::Approved, '2025-03-20', ['status' => 'in_moderation'], ['status' => 'published']);

        $reforme = $this->card('Hugo Fiction', CardType::Prediction, '« Cette réforme ne passera jamais au Parlement. »', '2024-11-05', '2025-06-30', ['Institutions'], CardStatus::Closed);
        $this->verdict($reforme, VerdictValue::Wrong, $this->users['j.martin'], VerdictStatus::Approved, $sLeroy, '2025-07-10',
            'Le texte a été adopté en lecture définitive avant l’échéance.');

        $inflation = $this->card('Marc Hypothèse', CardType::Prediction, '« L’inflation repassera sous 2 % avant la fin de l’année. »', '2024-03-02', '2024-12-31', ['Économie'], CardStatus::Closed);
        $this->verdict($inflation, VerdictValue::Correct, $this->users['m.durand'], VerdictStatus::Approved, $sLeroy, '2025-01-20',
            'L’indice des prix publié pour décembre est passé sous le seuil indiqué.');

        // Échéance dépassée, verdict proposé
        $loi = $this->card('Anne Scénario', CardType::Promise, '« Le projet de loi sera déposé avant l’été. »', '2025-01-15', '2025-06-21', ['Institutions'], CardStatus::VerdictProposed);
        $this->verdict($loi, VerdictValue::Abandoned, $this->users['j.martin'], VerdictStatus::Proposed, null, null,
            'Aucun dépôt n’apparaît au registre des textes à la date d’échéance, et le gouvernement a annoncé le report sine die.', sources: 2);

        // Prochaines échéances
        $this->card('Paul Modèle', CardType::Prediction, '« Aucun candidat ne dépassera 25 % au premier tour. »', '2026-09-10', '2027-04-18', ['Présidentielle 2027'], CardStatus::Published);
        $this->card('Jeanne Exemple', CardType::Promise, '« Je supprimerai la taxe X dès la première année du mandat. »', '2026-10-01', '2028-05-31', ['Présidentielle 2027', 'Économie'], CardStatus::Published, [
            'deadline_nature' => DeadlineNature::Inferred,
            'author_id' => $this->users['m.durand']->id,
        ]);
        $this->card('Hugo Fiction', CardType::Prediction, '« Le chômage repassera sous les 7 % d’ici 2028. »', '2026-06-12', '2028-12-31', ['Emploi'], CardStatus::Published);

        // En modération : une proposition de la modératrice, qu'elle ne peut pas traiter elle-même
        $this->card('Claire Récit', CardType::Promise, '« Le budget de la culture sera sanctuarisé. »', '2026-10-12', '2027-12-31', ['Culture'], CardStatus::InModeration, [
            'author_id' => $sLeroy->id,
        ]);

        // E-mail pas encore confirmé
        $this->card('Jeanne Exemple', CardType::Promise, '« Chaque département aura son centre de santé public. »', '2026-10-15', '2029-12-31', ['Santé', 'Présidentielle 2027'], CardStatus::AwaitingEmail, [
            'author_email' => 'nouveau.visiteur@example.test',
        ]);

        Report::factory()->create([
            'card_id' => $inflation->id,
            'email' => 'lecteur@example.test',
            'reason' => 'Le lien vers la source du verdict ne fonctionne plus.',
            'status' => ReportStatus::Open,
        ]);
    }

    /**
     * @param  list<string>  $themes
     * @param  array<string, mixed>  $overrides
     */
    private function card(
        string $figure,
        CardType $type,
        string $quote,
        string $statedOn,
        string $deadline,
        array $themes,
        CardStatus $status,
        array $overrides = [],
        SourceKind $sourceKind = SourceKind::Article,
        string $outlet = 'Journal B',
        ?string $timecode = null,
    ): Card {
        $card = Card::factory()->create([
            'public_figure_id' => $this->figures[$figure]->id,
            'type' => $type,
            'quote' => $quote,
            'stated_on' => $statedOn,
            'deadline' => $deadline,
            'deadline_nature' => DeadlineNature::Explicit,
            'status' => $status,
            'author_id' => isset($overrides['author_email']) ? null : $this->users['m.durand']->id,
            'email_confirmed_at' => $status === CardStatus::AwaitingEmail ? null : Carbon::parse($statedOn)->addDay(),
            'published_at' => $status->isPublic() ? Carbon::parse($statedOn)->addDays(3) : null,
            ...$overrides,
        ]);

        $card->themes()->attach(Theme::whereIn('name', $themes)->pluck('id'));

        $card->sources()->create([
            'url' => 'https://example.org/'.Str::slug(Str::limit($quote, 40, '')),
            'kind' => $sourceKind,
            'outlet' => $outlet,
            'published_on' => $statedOn,
            'timecode' => $timecode,
        ])->forceFill([
            'archive_url' => 'https://web.archive.org/web/2025/https://example.org/',
            'archived_at' => Carbon::parse($statedOn)->addDays(2),
        ])->save();

        return $card;
    }

    private function verdict(
        Card $card,
        VerdictValue $value,
        User $proposer,
        VerdictStatus $status,
        ?User $decider,
        ?string $decidedAt,
        string $justification,
        int $sources = 1,
    ): Verdict {
        $verdict = Verdict::factory()->create([
            'card_id' => $card->id,
            'value' => $value,
            'justification' => $justification,
            'proposed_by' => $proposer->id,
            'status' => $status,
            'decided_by' => $decider?->id,
            'decided_at' => $decidedAt,
        ]);

        for ($i = 1; $i <= $sources; $i++) {
            $verdict->sources()->create([
                'url' => 'https://example.org/donnees-publiques-'.$i,
                'kind' => SourceKind::PublicData,
                'outlet' => 'Organisme statistique',
            ]);
        }

        // Comme ProposeVerdict et ApproveVerdict : une ligne sur le verdict et une sur la carte.
        $proposedAt = Carbon::parse($decidedAt ?? now())->subDays(10)->toDateString();
        $this->log($card, $proposer, ActivityAction::Proposed, $proposedAt, ['status' => 'published'], ['status' => 'verdict_proposed', 'verdict' => $value->value]);

        if ($decider !== null) {
            $this->log($verdict, $decider, ActivityAction::Approved, $decidedAt, ['status' => 'proposed'], ['status' => 'approved']);
            $this->log($card, $decider, ActivityAction::Approved, $decidedAt, ['status' => 'verdict_proposed'], ['status' => 'closed', 'verdict' => $value->value]);
        }

        return $verdict;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function log(Card|Verdict $subject, ?User $actor, ActivityAction $action, string $at, ?array $before = null, ?array $after = null): void
    {
        $log = new ActivityLog(['action' => $action, 'before' => $before, 'after' => $after]);
        $log->subject()->associate($subject);
        $log->actor()->associate($actor);
        $log->created_at = Carbon::parse($at);
        $log->save();
    }
}
