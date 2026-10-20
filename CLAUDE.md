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
- **Hébergement** : VPS Hostinger (Nginx, PHP-FPM, Supervisor, Let's Encrypt). Sauvegardes hors du VPS (spatie/laravel-backup).
- **Anti-spam** : ALTCHA + limitation de débit Laravel. Pas de captcha à cookies.
- **Base** : PostgreSQL (choisi le 20 octobre 2026). Plusieurs règles sont doublées en base (contraintes CHECK, index uniques partiels) et la recherche plein texte utilise la configuration `french`. Les tests tournent sur PostgreSQL (base `prophet_test`), pas sur SQLite.
- **Clés primaires** : UUID (`HasUuids`). Statut, auteur, version, décideur et rôle ne sont jamais « fillable » : ils ne changent que par les classes d'action.

## Règles métier à faire respecter partout (Policies + tests)

1. Une carte proposée sans compte reste en `awaiting_email` tant que l'e-mail n'est pas confirmé par lien.
2. Seuls les modérateurs et l'admin publient, refusent (avec motif) ou modifient la rédaction d'une carte ; l'auteur est notifié par e-mail, un non-inscrit est invité à créer un compte.
3. Seul un membre inscrit propose un verdict, avec au moins une source.
4. Personne ne décide sur sa propre proposition (carte ou verdict), modérateurs compris.
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

Champs renommés par rapport à la spécification : `reformulation` → `testable_statement`, `critere_verdict` → `verdict_criterion`, `pseudo` → `username`, `media` → `outlet`, `horodatage` → `timecode`, `valide_par`/`valide_le` → `decided_by`/`decided_at`.

## Style visuel

Principes de Todoist (sobriété, barre latérale de vues, ajout rapide au clavier avec la touche Q) mais écran principal organisé en grille de cartes. Accent bleu `#2552C6`, typographie Inter, rond d'état coloré selon le verdict. Ne pas reprendre le logo ni le rouge de Todoist. Voir `docs/maquettes/`.

## Ordre de construction

1. Migrations, modèles, enums, factories et seeders (données fictives, jamais de vraies citations inventées). **Fait.**
2. Filament : saisie et modération.
3. Actions + Policies + tests (Pest) des règles ci-dessus.
4. API `/api/v1` + documentation Scramble. (À l'installation de Sanctum, passer `personal_access_tokens` en `uuidMorphs`.)
5. Site public Blade/Livewire d'après les maquettes.
6. Ouverture des propositions publiques (confirmation e-mail, anti-spam, limitation de débit).

## Hors périmètre V1

Prédictions des membres (questions fermées avec probabilités et score de calibration) : prévu en V2 ou dans une zone séparée. Ne pas l'anticiper dans le schéma au-delà du raisonnable.

## Contraintes éditoriales et juridiques

- Citer mot pour mot, avec contexte et horodatage pour l'audio et la vidéo ; on juge des faits, jamais des intentions.
- Droit de citation pour les extraits ; RGPD pour les e-mails des visiteurs (finalité : notification et invitation, durée de conservation limitée).
- Pas d'argent en jeu, pas d'agrégats ressemblant à des sondages en période électorale.
- Langue de travail avec Charles : le français.
