# Prophet — registre des promesses et prédictions

Site qui recense les promesses et prédictions de personnalités publiques (politiques, journalistes, éditorialistes, économistes), citées mot pour mot, datées, sourcées et archivées, puis tranchées par un verdict sourcé à leur échéance. Première échéance forte : la présidentielle française (premier tour le 18 avril 2027).

« Parole tenue » est le nom de travail affiché ; « prophet » est le nom du dépôt.

## Documents de référence

- `docs/specification.md` : circuit, modèle de données, architecture, endpoints. À lire avant toute décision de structure.
- `docs/maquettes/` : maquettes HTML statiques des quatre écrans (ouvrir `index.html`). Elles servent de référence visuelle pour les vues Blade/Livewire et le thème Filament.
- `docs/architecture.png` : schéma d'architecture.

## Décisions prises

- **Une seule application Laravel** porte l'API, le site public et la modération.
- **API d'abord, par le code** : la logique métier est dans des classes d'action (`app/Actions/...`) et des Policies. Contrôleurs API, composants Livewire et ressources Filament les appellent toutes ; aucune règle métier n'est écrite dans un contrôleur ou une vue.
- **API** : REST JSON sous `/api/v1`, Sanctum, Form Requests, API Resources, documentation OpenAPI avec Scramble.
- **Site public** : Blade + Livewire, rendu serveur (pages indexables, aperçus de partage).
- **Modération** : Filament.
- **Tâches de fond** : files Laravel sur Redis (Horizon), planificateur pour les rappels d'échéance.
- **E-mails** : notifications Laravel envoyées par le SMTP de l'offre e-mail OVH de Charles (volume faible, adresse dédiée, SPF/DKIM/DMARC).
- **Hébergement** : VPS Hostinger, selon l'infrastructure existante de Charles (`~/dev/vps-infra` : Docker derrière Traefik, HTTPS Let's Encrypt automatique). Adresse : `paroletenue.charlescoiffier.fr`, protégée par mot de passe pendant les tests. Image construite et déployée par GitHub Actions (`.github/workflows/deploy.yml`, tests d'abord) ; `deploy/docker-compose.yml` : app (serversideup/php, Nginx + FPM, migrations au démarrage), horizon, scheduler, postgres, redis, avec limites mémoire et CPU (VPS : 1 cœur, 3,8 Go partagés). Secrets dans `~/srv/paroletenue/.env` sur le VPS (modèle : `deploy/env.example`). Sauvegardes hors du VPS (spatie/laravel-backup) : à faire, destination à choisir.
- **Anti-spam** : ALTCHA + limitation de débit Laravel. Pas de captcha à cookies.
- **Base** : PostgreSQL (choisi le 20 octobre 2026). Plusieurs règles sont doublées en base (contraintes CHECK, index uniques partiels) et la recherche plein texte utilise la configuration `french`. Les tests tournent sur PostgreSQL (base `prophet_test`), pas sur SQLite.
- **Clés primaires** : UUID (`HasUuids`). Statut, auteur, version, décideur et rôle ne sont jamais « fillable » : ils ne changent que par les classes d'action.

## Règles métier à faire respecter partout (Policies + tests)

1. Une carte proposée sans compte reste en `awaiting_email` tant que l'e-mail n'est pas confirmé par lien.
2. Seuls les modérateurs et l'admin publient, refusent (avec motif) ou modifient la rédaction d'une carte ; l'auteur est notifié par e-mail, un non-inscrit est invité à créer un compte.
3. Seul un membre inscrit propose un verdict, avec au moins une source.
4. Personne ne décide sur sa propre proposition (carte ou verdict), modérateurs compris. Exception décidée par Charles le 20 octobre 2026 : l'admin peut publier et modifier ses propres cartes (pas les refuser, et jamais pour les verdicts) ; l'historique public le signale (`moderation.self_published`).
5. Une seule proposition de verdict en attente par carte.
6. Chaque action de modération écrit une ligne dans le journal (état avant/après) ; l'historique d'une carte est public.
7. Chaque source est archivée automatiquement (Wayback Machine « Save Page Now ») par un job, à l'enregistrement.
8. À l'inscription, les cartes proposées avec la même adresse e-mail sont rattachées au nouveau compte.

Déjà garanti en base : règle 4 pour les verdicts (`verdicts_not_self_decided`), règle 5 (`verdicts_one_pending_per_card`), au plus un verdict validé par carte, une source rattachée à une carte ou à un verdict mais pas aux deux, une carte a toujours un auteur (compte ou e-mail).

## Vocabulaire

On « tranche » une carte en lui donnant un **verdict** ; on n'emploie pas « résoudre ». Une carte tranchée a le statut `closed` (libellé « Tranchée »).

## Nommage

Code, tables et routes en anglais ; interface et textes en français (fichiers `lang/fr`).

| Spécification (fr) | Code (en) |
| --- | --- |
| carte | Card / cards |
| personnalite | PublicFigure / public_figures |
| affiliation | Affiliation / affiliations |
| source | Source / sources |
| verdict | Verdict / verdicts |
| theme, carte_theme | Theme / themes, card_theme |
| journal | ActivityLog / activity_logs |
| signalement | Report / reports |
| utilisateur | User / users |

Statuts d'une carte : `awaiting_email` → `in_moderation` → `published` → `verdict_proposed` → `closed`, ou `rejected`.
Valeurs de verdict — promesse : `kept`, `partially_kept`, `compromised`, `abandoned`, `moot` ; prédiction : `correct`, `partially_correct`, `wrong`, `unverifiable`.
Rôles : `member`, `moderator`, `admin`.

Les enums sont dans `app/Enums` ; leurs libellés français dans `lang/fr/enums.php` (`$enum->label()`, `Enum::options()`).

Catégorie d'une personnalité : table `categories` (enrichissable par les modérateurs via `CreateCategory`), plus l'ancien enum. Organisations : `App\Support\Organizations` fusionne les variantes d'écriture et signale les quasi-doublons (`NotNearDuplicateOrganization`). Comptes : suppression douce par l'admin (`DeleteUser`, `RestoreUser`).

Champs renommés par rapport à la spécification : `reformulation` → `testable_statement`, `critere_verdict` → `verdict_criterion`, `pseudo` → `username`, `media` → `outlet`, `horodatage` → `timecode`, `valide_par`/`valide_le` → `decided_by`/`decided_at`.

## Style visuel

Charte éditoriale (21 octobre 2026), référence `docs/maquettes/editoriale/index.html` : papier crème `#F7F4EA`, encre `#20212B`, traits d'encre de 1 px et angles droits, DM Sans pour le texte et Newsreader (serif grasse) pour les titres et les citations. La couleur d'une carte signifie toujours un verdict (décision du 21 octobre 2026) : une carte tranchée prend la couleur du sien (bleu tenue/juste, jaune partielle, rouge abandonnée/fausse, gris caduque/invérifiable) ; une carte ouverte ou en relecture garde le fond papier. Portrait d'une personnalité en photocopie (photo libre de droits en noir et blanc contrasté), monogramme en serif à défaut. Une photo téléversée passe par l'atelier portrait de la modération (champ `PortraitStudio`, calcul dans le navigateur : `resources/js/portrait-studio`, tests `npm run test:js`) : recadrage, découpe aux ciseaux, deux tons et liseré en option ; taille finale et couleurs dans `resources/portrait/studio.json` (valent pour les photos traitées ensuite) ; original et réglages conservés pour « Retoucher » ; une photo traitée s'affiche sans filtre CSS. Barre latérale : États, Verdicts (par tonalité), Personnalités, Thèmes ; ajout rapide au clavier avec la touche Q. Les maquettes d'origine (`docs/maquettes/*.html`, style Todoist) restent la référence de structure des autres écrans. Le panneau de modération Filament garde son thème, rendu compact (`resources/css/filament/moderation/theme.css` : taille de base 14 px et pas d'espacement réduit).

## Ordre de construction

1. Migrations, modèles, enums, factories et seeders (données fictives, jamais de vraies citations inventées). **Fait.**
2. Filament : saisie et modération (panneau `/moderation`). **Fait**, avec les actions et Policies de modération qu'il appelle (`app/Actions`, `app/Policies`, boutons réutilisables dans `app/Filament/Actions`).
3. Actions + Policies + tests (Pest) des parcours restants. **Fait** : `ProposeCard` + lien signé `/confirmations/{card}` (règle 1), `ProposeVerdict` (règles 3 et 5), `ReportCard`, job `ArchiveSource` (règle 7), `AttachGuestCards` (règle 8). Choix faits :
   - toute source passe par `AttachSources`, qui lance l'archivage ; ne jamais créer une source autrement ;
   - archivage : demande « Save Page Now » puis vérification par l'API de disponibilité ; une capture antérieure à l'enregistrement de la source est refusée ; en cas de 429, le job patiente ; `sources:archive-missing` relance chaque nuit ;
   - un verdict peut être proposé dès que la carte est publiée, sans attendre l'échéance (une promesse peut être tenue ou abandonnée avant) ; la modération filtre ;
   - les cartes sont rattachées à la confirmation de l'adresse du compte (événement `Verified`), pas à l'inscription, et l'adresse est alors effacée de la carte ;
   - les cartes jamais confirmées sont effacées après 8 jours (`cards:purge-unconfirmed`) ;
   - le texte d'un signalement reste privé ; l'historique public n'en montre que l'existence et la note du modérateur ;
   - les tests bloquent tout appel HTTP réel (`Http::preventStrayRequests()` dans `tests/TestCase.php`).
4. API `/api/v1` + documentation Scramble. **Fait** : `routes/api.php`, contrôleurs minces dans `app/Http/Controllers/Api/V1`, Form Requests (forme des données) dans `app/Http/Requests/Api/V1`, ressources dans `app/Http/Resources/V1` (chaque enum exposé en `{value, label}`). Documentation publique sur `/docs/api`, groupes Scramble via `#[Group]`. Choix faits :
   - jetons Sanctum (`Authorization: Bearer`) ; proposition de carte et signalement ouverts sans jeton, avec adresse e-mail ;
   - carte ou proposition de verdict non publique : 404 (pas 403) pour ne pas révéler son existence ;
   - l'adresse e-mail d'un auteur sans compte et le texte d'un signalement ne sortent jamais publiquement ;
   - bilan d'une personnalité en décomptes de verdicts, jamais en pourcentages ;
   - limites de débit : `api` (120/min), `proposals` (10/h par IP), `auth` ; confirmation de compte par la route web `verification.verify`, réinitialisation du mot de passe vers `/mot-de-passe/nouveau` (page à faire à l'étape 5).
5. Site public Blade/Livewire d'après les maquettes. **Fait** : composants Livewire à classe (`app/Livewire`, vues dans `resources/views/livewire`), mises en page `layouts/app` (barre latérale, dont la fiche d'une carte) et `layouts/dialog` (fenêtre sur fond gris, pour la connexion et l'inscription). `Board` sert l'accueil, les vues, les pages personnalité, thème et recherche. Repères :
   - palette et polices dans `resources/css/app.css` (`@theme`) et `vite.config.js` (Bunny Fonts) ; aplats des cartes, ronds d'état, statuts et tonalités de verdict (`Appearance::VERDICT_TONES`, vues `/verdicts/{tonalité}`) dans `App\View\Appearance` (classes Tailwind écrites en entier) ; portraits : composant `<x-portrait>` (photo téléversée `photo_path` ou lien `photo_url`, sinon monogramme) ;
   - icônes : UIcons de Flaticon, style Regular Rounded uniquement, extraites en SVG dans `resources/icons/` par `scripts/extract-uicons.py` (ajouter une icône : l'inscrire dans la liste du script) et affichées par `<x-uicon name>` (`<x-icon>` est pris par blade-icons, livré avec Filament) ; mention « UIcons par Flaticon » obligatoire (pied de page) ; logo `public/logo.svg`, favicons régénérés par `npm run build:favicons` ;
   - en haut du site : version (`config/prophet.php`) et commit de construction (`APP_COMMIT`, posé par le workflow) ;
   - historique public en phrases : `ActivityLog::publicSentence()` + `lang/fr/history.php` ;
   - touche Q : `resources/js/app.js` ; mêmes limites de débit que l'API pour les formulaires publics ;
   - textes de la charte et de la page données personnelles : brouillons à relire par Charles ;
   - pas encore faits : raccourcis @ et # dans le contexte, bouton « Afficher » de la maquette, création d'une personnalité par un visiteur (il choisit dans la liste existante).
6. Ouverture des propositions publiques (confirmation e-mail, anti-spam, limitation de débit). **Fait** : ALTCHA (`altcha-org/altcha` côté serveur, widget `altcha` côté navigateur), défi servi par `GET /altcha` et `GET /api/v1/altcha`, vérifié par la règle `App\Rules\Altcha` via `App\Support\AltchaGuard`. Repères :
   - exigé des visiteurs sans compte pour proposer une carte, signaler une erreur et créer un compte (site et API) ; les membres connectés en sont dispensés ;
   - une solution ne sert qu'une fois (mémorisée en cache jusqu'à l'expiration du défi) ; dans Livewire, `VerifiesAltcha` la vérifie après les autres champs pour ne pas la gâcher ;
   - difficulté réglable dans `.env` (`ALTCHA_COST`, `ALTCHA_COUNTER_MIN/MAX`, environ une seconde pour un visiteur) ; réduite au minimum dans `phpunit.xml` ; les tests résolvent de vrais défis avec `altchaSolution()` (`tests/Pest.php`).

## Hors périmètre V1

Prédictions des membres (questions fermées avec probabilités et score de calibration) : prévu en V2 ou dans une zone séparée. Ne pas l'anticiper dans le schéma au-delà du raisonnable.

## Contraintes éditoriales et juridiques

- Citer mot pour mot, avec contexte et horodatage pour l'audio et la vidéo ; on juge des faits, jamais des intentions.
- Droit de citation pour les extraits ; RGPD pour les e-mails des visiteurs (finalité : notification et invitation, durée de conservation limitée).
- Pas d'argent en jeu, pas d'agrégats ressemblant à des sondages en période électorale.
- Langue de travail avec Charles : le français.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
