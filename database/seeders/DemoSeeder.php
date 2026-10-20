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
 * Mot de passe de tous les comptes : « password » (sauf en mode verrouillé, voir demo:load).
 */
class DemoSeeder extends Seeder
{
    /**
     * Personnalités créées par la démonstration : demo:purge les retrouve par leur slug.
     *
     * @var list<string>
     */
    public const FIGURES = [
        'Jeanne Exemple', 'Paul Modèle', 'Lucie Témoin', 'Marc Hypothèse', 'Hugo Fiction', 'Anne Scénario', 'Claire Récit',
        'Bruno Brouillon', 'Sophie Maquette', 'Karim Parabole', 'Inès Esquisse', 'Thomas Ébauche', 'Nadia Synopsis',
        'Louis Prototype', 'Émilie Variante', 'Victor Postulat', 'Rose Hypallage', 'Samuel Prologue', 'Agathe Corollaire',
        'Yves Épilogue', 'Margaux Tangente',
    ];

    /**
     * Thèmes propres à la démonstration (le vocabulaire de production reste dans ThemeSeeder).
     *
     * @var list<string>
     */
    public const THEMES = ['Fiscalité', 'Vie publique', 'Services publics', 'Logement', 'Transports', 'Retraites', 'Énergie', 'International'];

    /** Domaine des comptes et adresses de démonstration : réservé, il ne reçoit aucun e-mail. */
    public const EMAIL_DOMAIN = 'example.test';

    /**
     * Comptes verrouillés (site de test) : pas d'administrateur de démonstration, et des mots de passe
     * aléatoires jamais affichés, donc aucun compte de démonstration ne peut être ouvert.
     */
    private bool $locked = false;

    /** @var array<string, User> */
    private array $users = [];

    public function locked(): static
    {
        $this->locked = true;

        return $this;
    }

    /** @var array<string, PublicFigure> */
    private array $figures = [];

    public function run(): void
    {
        $this->seedUsers();
        $this->seedPublicFigures();
        $this->seedCards();
        $this->seedCatalog();
    }

    private function seedUsers(): void
    {
        foreach ([
            ...($this->locked ? [] : ['admin' => Role::Admin]),
            's.leroy' => Role::Moderator,
            'm.durand' => Role::Member,
            'j.martin' => Role::Member,
            'a.moreau' => Role::Moderator,
            'c.bernard' => Role::Member,
            'l.petit' => Role::Member,
        ] as $username => $role) {
            $this->users[$username] = User::factory()->create([
                'username' => $username,
                'email' => $username.'@'.self::EMAIL_DOMAIN,
                'role' => $role,
                ...($this->locked ? ['password' => Str::random(64)] : []),
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
     * Catalogue étoffé : personnalités, thèmes et cartes fictifs couvrant tous les états et tous les verdicts.
     * Les échéances sont relatives à la date du chargement, pour que la démonstration ne vieillisse pas.
     */
    private function seedCatalog(): void
    {
        // Thèmes propres à la démonstration (le vocabulaire de production reste dans ThemeSeeder).
        foreach (self::THEMES as $name) {
            Theme::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ([
            ['Bruno Brouillon', PublicFigureCategory::Politician, 'Maire d’une grande ville', [['Parti F', 'Maire', '-6 years', null]]],
            ['Sophie Maquette', PublicFigureCategory::Politician, 'Sénatrice', [['Parti A', 'Sénatrice', '-5 years', null]]],
            ['Karim Parabole', PublicFigureCategory::Columnist, 'Éditorialiste à la télévision', [['Télé G', 'Éditorialiste', '-8 years', null]]],
            ['Inès Esquisse', PublicFigureCategory::Economist, 'Économiste, professeure d’université', [['Institut D', 'Chercheuse associée', '-10 years', null]]],
            ['Thomas Ébauche', PublicFigureCategory::Politician, 'Ministre de l’Économie', [['Parti B', 'Député', '-9 years', '-2 years'], ['Gouvernement C', 'Ministre de l’Économie', '-2 years', null]]],
            ['Nadia Synopsis', PublicFigureCategory::Journalist, 'Journaliste politique', [['Hebdo H', 'Rédactrice en chef', '-4 years', null]]],
            ['Louis Prototype', PublicFigureCategory::Politician, 'Candidat à l’élection présidentielle', [['Parti G', 'Candidat', '-1 year', null]]],
            ['Émilie Variante', PublicFigureCategory::Politician, 'Présidente de région', [['Parti B', 'Présidente de région', '-5 years', null]]],
            ['Victor Postulat', PublicFigureCategory::Economist, 'Économiste de banque', [['Banque K', 'Chef économiste', '-7 years', null]]],
            ['Rose Hypallage', PublicFigureCategory::Columnist, 'Chroniqueuse radio', [['Radio E', 'Chroniqueuse', '-3 years', null]]],
            ['Samuel Prologue', PublicFigureCategory::Politician, 'Député', [['Parti A', 'Député', '-4 years', null]]],
            ['Agathe Corollaire', PublicFigureCategory::Journalist, 'Présentatrice de la matinale', [['Télé G', 'Présentatrice', '-6 years', null]]],
            ['Yves Épilogue', PublicFigureCategory::Politician, 'Ancien Premier ministre', [['Gouvernement C', 'Premier ministre', '-7 years', '-4 years'], ['Parti B', 'Conseiller politique', '-4 years', null]]],
            ['Margaux Tangente', PublicFigureCategory::Other, 'Secrétaire générale d’un syndicat', [['Syndicat L', 'Secrétaire générale', '-3 years', null]]],
        ] as [$name, $category, $description, $affiliations]) {
            $figure = PublicFigure::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'category' => $category,
                'description' => $description,
            ]);

            foreach ($affiliations as [$organization, $role, $start, $end]) {
                $figure->affiliations()->create([
                    'organization' => $organization,
                    'role' => $role,
                    'started_on' => now()->modify($start)->startOfYear()->toDateString(),
                    'ended_on' => $end ? now()->modify($end)->endOfYear()->toDateString() : null,
                ]);
            }

            $this->figures[$name] = $figure;
        }

        $members = [$this->users['m.durand'], $this->users['j.martin'], $this->users['c.bernard'], $this->users['l.petit']];
        $moderators = [$this->users['s.leroy'], $this->users['a.moreau']];
        $at = fn (string $relative) => now()->modify($relative)->toDateString();

        // [personnalité, type, citation, déclarée (relatif), échéance (relatif), thèmes, statut, verdict ?, contexte, source]
        $cards = [
            // Prochaines échéances
            ['Louis Prototype', CardType::Promise, '« Je baisserai l’impôt sur le revenu des classes moyennes de 10 % sur le quinquennat. »', '-1 month', '+5 years', ['Présidentielle 2027', 'Fiscalité'], CardStatus::Published, null, 'Meeting de lancement de campagne, devant ses soutiens.', [SourceKind::Video, 'Télé G', '41:05']],
            ['Sophie Maquette', CardType::Promise, '« Aucun bureau de poste ne fermera dans les communes rurales d’ici trois ans. »', '-2 months', '+34 months', ['Services publics'], CardStatus::Published, null, 'Questions au gouvernement, en séance publique.', [SourceKind::OfficialJournal, 'Compte rendu des débats', null]],
            ['Karim Parabole', CardType::Prediction, '« La participation au premier tour sera inférieure à 70 %. »', '-3 weeks', '+6 months', ['Présidentielle 2027', 'Vie publique'], CardStatus::Published, null, 'Débat de fin d’émission sur l’abstention.', [SourceKind::Video, 'Télé G', '52:10']],
            ['Inès Esquisse', CardType::Prediction, '« Les prix de l’immobilier ancien baisseront encore de 5 % l’an prochain. »', '-5 weeks', '+14 months', ['Logement', 'Économie'], CardStatus::Published, null, 'Tribune publiée dans un quotidien économique.', [SourceKind::Article, 'Journal B', null]],
            ['Émilie Variante', CardType::Promise, '« Les cars régionaux seront gratuits pour les moins de 26 ans à la rentrée prochaine. »', '-2 months', '+11 months', ['Transports'], CardStatus::Published, null, 'Vote du budget régional.', [SourceKind::Programme, 'Région', null]],
            ['Bruno Brouillon', CardType::Promise, '« Nous planterons 20 000 arbres dans la ville avant la fin du mandat. »', '-7 months', '+5 years', ['Environnement'], CardStatus::Published, null, 'Conseil municipal consacré au plan climat.', [SourceKind::Video, 'Ville', '1:12:40']],
            ['Victor Postulat', CardType::Prediction, '« La croissance dépassera 1,5 % l’an prochain. »', '-6 weeks', '+15 months', ['Économie'], CardStatus::Published, null, 'Présentation des prévisions trimestrielles de la banque.', [SourceKind::PublicData, 'Banque K', null]],
            ['Samuel Prologue', CardType::Promise, '« Je déposerai une proposition de loi sur le non-cumul des indemnités avant la fin de l’année. »', '-1 month', '+2 months', ['Vie publique', 'Institutions'], CardStatus::Published, null, 'Entretien à la matinale.', [SourceKind::Audio, 'Radio E', '08:47']],
            ['Margaux Tangente', CardType::Prediction, '« La dernière réforme des retraites sera abrogée avant deux ans. »', '-3 months', '+21 months', ['Retraites'], CardStatus::Published, null, 'Conférence de presse à l’issue d’une journée de mobilisation.', [SourceKind::Video, 'Hebdo H', '06:30']],
            ['Thomas Ébauche', CardType::Promise, '« Le déficit public repassera sous 3 % du PIB d’ici trois ans. »', '-4 months', '+3 years', ['Économie', 'Fiscalité'], CardStatus::Published, null, 'Présentation du projet de loi de finances.', [SourceKind::OfficialJournal, 'Assemblée', null]],

            // Échéance dépassée, verdict attendu
            ['Thomas Ébauche', CardType::Promise, '« Les prix de l’électricité baisseront de 10 % au 1er février. »', '-14 months', '-8 months', ['Énergie'], CardStatus::Published, null, 'Annonce au journal du soir.', [SourceKind::Video, 'Télé G', '12:02']],
            ['Rose Hypallage', CardType::Prediction, '« Le gouvernement ne passera pas l’été. »', '-9 months', '-3 months', ['Vie publique'], CardStatus::Published, null, 'Chronique politique du vendredi.', [SourceKind::Audio, 'Radio E', '03:15']],
            ['Émilie Variante', CardType::Promise, '« Le nouveau lycée ouvrira ses portes à la rentrée. »', '-18 months', '-1 month', ['Éducation'], CardStatus::Published, null, 'Pose de la première pierre.', [SourceKind::Article, 'Journal B', null]],

            // Verdict en relecture
            ['Bruno Brouillon', CardType::Promise, '« La piscine municipale rouvrira avant l’été. »', '-13 months', '-4 months', ['Services publics'], CardStatus::VerdictProposed,
                [VerdictValue::Kept, null, 'La piscine a rouvert au public trois semaines avant la date annoncée, selon l’arrêté municipal et la presse locale.'], 'Réunion publique de quartier.', [SourceKind::Video, 'Ville', '27:45']],

            // Tranchées : une carte par valeur de verdict
            ['Yves Épilogue', CardType::Promise, '« Nous créerons 2 000 places de prison supplémentaires d’ici la fin du mandat. »', '-6 years', '-4 years', ['Sécurité'], CardStatus::Closed,
                [VerdictValue::Kept, '-40 months', 'Le bilan officiel publié à la fin du mandat recense 2 150 places nettes créées.'], 'Déclaration de politique générale.', [SourceKind::OfficialJournal, 'Assemblée', null]],
            ['Margaux Tangente', CardType::Promise, '« Nous obtiendrons une hausse de 3 % du point d’indice cette année. »', '-20 months', '-9 months', ['Services publics'], CardStatus::Closed,
                [VerdictValue::PartiallyKept, '-7 months', 'Le point d’indice a été revalorisé de 1,5 % : la hausse obtenue est réelle mais deux fois moindre que l’objectif annoncé.'], 'Ouverture des négociations salariales.', [SourceKind::Video, 'Hebdo H', '02:10']],
            ['Sophie Maquette', CardType::Promise, '« Je ferai voter la semaine de quatre jours dans la fonction publique territoriale. »', '-30 months', '-12 months', ['Services publics'], CardStatus::Closed,
                [VerdictValue::Compromised, '-10 months', 'Le texte adopté autorise la semaine de quatre jours à titre expérimental et sur la base du volontariat, loin de la généralisation promise.'], 'Entretien dans un quotidien régional.', [SourceKind::Article, 'Journal B', null]],
            ['Samuel Prologue', CardType::Promise, '« Je présenterai un plan vélo national avant la fin de l’année. »', '-24 months', '-14 months', ['Transports'], CardStatus::Closed,
                [VerdictValue::Abandoned, '-11 months', 'Aucun plan n’a été présenté ; le député a déclaré en commission que le projet était « mis de côté ».'], 'Congrès d’une fédération de cyclistes.', [SourceKind::Video, 'Télé G', '19:30']],
            ['Louis Prototype', CardType::Promise, '« Si je suis élu maire, je gèlerai les loyers des logements municipaux. »', '-26 months', '-18 months', ['Logement'], CardStatus::Closed,
                [VerdictValue::Moot, '-17 months', 'Le candidat n’a pas été élu : la promesse est devenue sans objet.'], 'Débat de second tour des municipales.', [SourceKind::Video, 'Télé G', '33:12']],
            ['Victor Postulat', CardType::Prediction, '« Le chômage dépassera 9 % à la fin de l’année. »', '-22 months', '-10 months', ['Emploi'], CardStatus::Closed,
                [VerdictValue::Wrong, '-8 months', 'Le taux de chômage publié pour le dernier trimestre s’établit à 7,3 %, bien en dessous du seuil annoncé.'], 'Note de conjoncture.', [SourceKind::PublicData, 'Banque K', null]],
            ['Inès Esquisse', CardType::Prediction, '« Les taux baisseront deux fois cette année et l’immobilier repartira. »', '-28 months', '-16 months', ['Économie', 'Logement'], CardStatus::Closed,
                [VerdictValue::PartiallyCorrect, '-14 months', 'Les taux directeurs ont bien baissé deux fois, mais le nombre de ventes de logements anciens est resté stable sur l’année.'], 'Colloque annuel de l’Institut.', [SourceKind::Video, 'Institut D', '1:05:20']],
            ['Karim Parabole', CardType::Prediction, '« Ce débat télévisé changera l’issue de l’élection. »', '-30 months', '-28 months', ['Vie publique'], CardStatus::Closed,
                [VerdictValue::Unverifiable, '-26 months', 'Aucune donnée ne permet d’isoler l’effet d’un débat sur le résultat : la prédiction ne peut être ni confirmée ni infirmée.'], 'Éditorial du soir.', [SourceKind::Video, 'Télé G', '04:50']],
            ['Agathe Corollaire', CardType::Prediction, '« Le texte sera adopté à moins de dix voix d’écart. »', '-15 months', '-13 months', ['Institutions'], CardStatus::Closed,
                [VerdictValue::Correct, '-12 months', 'Le scrutin public a donné 289 voix pour et 282 contre : un écart de sept voix.'], 'Matinale, la veille du vote.', [SourceKind::Video, 'Télé G', '07:58']],

            // En modération et refusée
            ['Nadia Synopsis', CardType::Prediction, '« Les primaires seront annulées faute de candidats sérieux. »', '-1 week', '+5 months', ['Présidentielle 2027'], CardStatus::InModeration, null, 'Lettre politique hebdomadaire.', [SourceKind::Article, 'Hebdo H', null]],
            ['Rose Hypallage', CardType::Prediction, '« Le ticket de métro dépassera 3 euros l’an prochain. »', '-4 days', '+14 months', ['Transports'], CardStatus::InModeration, null, 'Chronique consacrée aux transports.', [SourceKind::Audio, 'Radio E', '02:40']],
            ['Karim Parabole', CardType::Prediction, '« Tout le monde sait que ce ministre finira par mentir. »', '-2 weeks', '+1 year', ['Vie publique'], CardStatus::Rejected, null, 'Éditorial.', [SourceKind::Video, 'Télé G', '01:20']],
        ];

        foreach ($cards as $index => [$figure, $type, $quote, $statedOn, $deadline, $themes, $status, $verdict, $context, [$kind, $outlet, $timecode]]) {
            $author = $members[$index % count($members)];
            $moderator = $moderators[$index % count($moderators)];
            $overrides = ['context' => $context, 'author_id' => $author->id];

            if ($status === CardStatus::Rejected) {
                $overrides['rejection_reason'] = 'Ce n’est ni une promesse ni une prédiction vérifiable : on juge des faits, jamais des intentions.';
            }

            $card = $this->card($figure, $type, $quote, $at($statedOn), $at($deadline), $themes, $status, $overrides, $kind, $outlet, $timecode);

            if ($status->isPublic()) {
                $this->log($card, $author, ActivityAction::Proposed, $at($statedOn));
                $this->log($card, $moderator, ActivityAction::Approved, now()->modify($statedOn)->addDays(3)->toDateString(), ['status' => 'in_moderation'], ['status' => 'published']);
            }

            if ($verdict !== null) {
                [$value, $decidedAt, $justification] = $verdict;
                $proposer = $members[($index + 1) % count($members)];
                $this->verdict(
                    $card, $value, $proposer,
                    $decidedAt === null ? VerdictStatus::Proposed : VerdictStatus::Approved,
                    $decidedAt === null ? null : $moderator,
                    $decidedAt === null ? null : $at($decidedAt),
                    $justification,
                );
            }
        }

        // Un signalement ouvert et un signalement traité
        $electricity = Card::where('quote', 'like', '%prix de l’électricité%')->sole();
        Report::factory()->create([
            'card_id' => $electricity->id,
            'email' => 'lectrice@example.test',
            'reason' => 'La date de l’annonce semble être le 12 et non le 14.',
            'status' => ReportStatus::Open,
        ]);
        Report::factory()->create([
            'card_id' => Card::where('quote', 'like', '%moins de dix voix%')->sole()->id,
            'user_id' => $this->users['l.petit']->id,
            'email' => null,
            'reason' => 'Le lien vers le compte rendu du scrutin a changé.',
            'status' => ReportStatus::Resolved,
            'handled_by' => $this->users['a.moreau']->id,
            'handled_at' => now()->subMonths(2),
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
