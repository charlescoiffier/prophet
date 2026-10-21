# Atelier portrait — plan de construction

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** dans la fiche d'une personnalité (`/moderation`), un atelier qui recadre la photo, la découpe aux ciseaux, la passe en deux tons et enregistre l'image finale, l'original et les réglages.

**Architecture :** tout le calcul d'image se fait dans le navigateur (canevas, JavaScript pur, sans dépendance). Un champ Filament personnalisé `PortraitStudio` envoie à Livewire, par `$wire.upload`, le PNG final et l'original, plus les réglages en JSON ; il range les fichiers sur le disque `public`. `SavePublicFigure` garde la cohérence des trois et supprime les fichiers qui ne servent plus. `<x-portrait>` affiche une photo traitée sans filtre CSS.

**Tech Stack :** Laravel 13 / PHP 8.4, Filament 5, Livewire, Alpine (livré par Livewire), Vite, Pest sur PostgreSQL, `node:test` (intégré à Node, aucun paquet ajouté) pour les fonctions de calcul.

**Spec :** `docs/superpowers/specs/2026-10-21-atelier-portrait-design.md`

## Global Constraints

- Aucune dépendance npm ou Composer ajoutée.
- Aucun envoi de la photo à un service extérieur ; aucun calcul d'image côté serveur.
- Image finale : PNG 600 × 600 avec transparence, deux tons plats.
- Couleurs uniquement dans `resources/portrait/palette.json` ; aucune couleur de portrait en dur dans le code.
- Les couleurs utilisées sont recopiées dans les réglages ; « Retoucher » reprend celles des réglages, pas celles du fichier.
- Interface en français ; code, colonnes et clés JSON en anglais.
- Règles métier dans `SavePublicFigure`, pas dans le champ ni dans les pages.
- Photos par lien (`photo_url`) et anciennes photos sans réglages : filtre CSS actuel inchangé.
- Téléverser une photo vide `photo_url` (comportement actuel conservé).
- Lancer `vendor/bin/pint --dirty --format agent` après toute modification PHP.

### Forme des réglages (`photo_settings`), partagée par PHP et JavaScript

```json
{
  "version": 1,
  "crop": { "x": 120, "y": 40, "size": 900 },
  "cuts": [
    { "points": [[0, 540], [180, 120], [420, 110], [600, 520]], "discard": [300, 20] }
  ],
  "brightness": 0,
  "contrast": 0,
  "threshold": 128,
  "halo": { "enabled": false, "width": 8 },
  "palette": { "dark": "#20212B", "light": "#D9D8D2", "halo": "#D9D8D2" }
}
```

- `crop` : carré dans les pixels de l'original. `cuts[].points` et `cuts[].discard` : coordonnées dans l'image finale (0 à 600) ; premier et dernier point sur le pourtour ; `discard` est le point cliqué dans le morceau jeté.
- `brightness` et `contrast` de −100 à 100, `threshold` de 0 à 255, `halo.width` de 2 à 20 (pixels de l'image finale).

## Review Focus

1. **Photo de téléphone énorme ou format non lisible (HEIC)** : l'atelier refuse au-delà de 10 Mo ou si le navigateur ne décode pas l'image, avec un message en français, sans rien envoyer. Test : `loadImageFile` dans la tâche 3 rejette un fichier trop gros.
2. **Coupe dégénérée** (deux points sur le même côté sans point intérieur, ou clic « jeter » exactement sur la ligne) : la coupe est ignorée, l'image ne devient pas entièrement transparente. Test : `splitByCut` tâche 3.
3. **Retoucher une photo dont l'original a disparu du disque** : l'atelier dit « Photo d'origine introuvable, choisissez un nouveau fichier » au lieu de rester vide. Vérification manuelle tâche 5.
4. **Ouvrir l'atelier puis le fermer sans valider** : la photo enregistrée et `photo_url` ne changent pas. Test Filament tâche 4.
5. **Retirer la photo** : les trois colonnes sont vidées et les deux fichiers effacés. Test tâche 1.

---

### Task 1 : colonnes, modèle et règles d'enregistrement

**Files :**
- Create : `database/migrations/2026_10_21_000003_add_photo_original_to_public_figures_table.php`
- Modify : `app/Models/PublicFigure.php`, `app/Actions/PublicFigures/SavePublicFigure.php`, `lang/fr/validation.php`
- Test : `tests/Feature/Actions/PublicFigures/SavePublicFigureTest.php`

**Interfaces :**
- Produces : colonnes `photo_original_path` (string, nullable) et `photo_settings` (jsonb, nullable), toutes deux fillable ; cast `photo_settings => 'array'` ; `PublicFigure::hasProcessedPhoto(): bool` (vrai si `photo_path` et `photo_settings` sont remplis) ; `SavePublicFigure::handle()` accepte les clés `photo_path`, `photo_original_path`, `photo_settings`.

- [ ] **Step 1 : écrire les tests qui échouent**, dans `SavePublicFigureTest.php`, avec `Storage::fake('public')` :
  - `enregistre l’image traitée, l’original et les réglages` : après `handle` avec les trois clés, `hasProcessedPhoto()` est vrai et `photo_settings['threshold']` vaut `128`.
  - `efface l’ancien original quand la photo est refaite à partir d’un nouveau fichier` : `portraits/originals/a.jpg` → `b.jpg` : `a.jpg` absent, `b.jpg` présent.
  - `garde l’original quand on retouche seulement les réglages` : même `photo_original_path`, nouveau `photo_path` : l'original existe toujours, l'ancien PNG est effacé.
  - `retire la photo et ses deux fichiers` : les trois clés à `null` : colonnes nulles, les deux fichiers effacés.
  - `refuse un original ou des réglages sans les deux autres` (dataset : original seul ; réglages seuls ; image + réglages sans original) : `ValidationException` sur la clé `photo_path`, rien n'est enregistré.
  - `n’inscrit ni l’original ni les réglages au journal` : `after` du journal ne contient que `photo_path`.
- [ ] **Step 2 :** `php artisan test --compact tests/Feature/Actions/PublicFigures/SavePublicFigureTest.php` → les nouveaux tests échouent.
- [ ] **Step 3 :** migration (style de `2026_10_21_000002`, docblock en français), modèle (fillable, cast, `hasProcessedPhoto`), puis `SavePublicFigure` :
  - avant `fill` : si `photo_original_path` ou `photo_settings` est rempli dans `$data`, `photo_path`, `photo_original_path` et `photo_settings` doivent l'être tous les trois, sinon `ValidationException::withMessages(['photo_path' => __('validation.custom.photo_path.incomplete')])` ; un `photo_path` seul reste accepté (anciennes photos) ;
  - suppression après commit de l'ancien `photo_original_path` quand il change, comme pour `photo_path` ;
  - `snapshot()` inchangé.
  - Message dans `lang/fr/validation.php` : « La photo traitée doit être enregistrée avec sa photo d’origine et ses réglages. »
- [ ] **Step 4 :** relancer le fichier de tests → tout passe ; `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 5 : commit** « Photo de personnalité : original et réglages de l'atelier enregistrés ».

### Task 2 : affichage public d'une photo traitée

**Files :**
- Modify : `resources/views/components/portrait.blade.php`
- Test : `tests/Feature/Site/PortraitTest.php` (`php artisan make:test --pest Site/PortraitTest`)

**Interfaces :**
- Consumes : `PublicFigure::hasProcessedPhoto()` (tâche 1).

- [ ] **Step 1 : tests qui échouent**, avec `$this->blade('<x-portrait :figure="$figure" />', ['figure' => $figure])` :
  - `affiche une photo traitée telle quelle` : `assertDontSee('grayscale')`, `assertDontSee('mix-blend-multiply')`, `assertSee($figure->photoUrl())`.
  - `garde le filtre photocopie pour une photo par lien` (dataset : `photo_url` seul ; `photo_path` sans réglages) : `assertSee('grayscale')`.
- [ ] **Step 2 :** lancer le fichier → le premier échoue.
- [ ] **Step 3 :** dans le composant, les classes `mix-blend-multiply grayscale contrast-[1.7]` ne s'appliquent que si `! $figure->hasProcessedPhoto()` (`@class`, classes écrites en entier).
- [ ] **Step 4 :** relancer → passe.
- [ ] **Step 5 : commit** « Portrait traité affiché sans filtre ».

### Task 3 : palette et calcul d'image (JavaScript pur)

**Files :**
- Create : `resources/portrait/palette.json`, `resources/js/portrait-studio/pipeline.js`, `resources/js/portrait-studio/pipeline.test.js`
- Modify : `package.json` (script `"test:js": "node --test resources/js/"`)

**Interfaces :**
- Produces (exports de `pipeline.js`, coordonnées en pixels de l'image finale, `SIZE = 600`) :
  - `SIZE`, `SNAP_DISTANCE = 12`, `MAX_FILE_BYTES = 10 * 1024 * 1024`
  - `snapToEdge([x, y]): [x, y] | null` : projette sur le côté le plus proche si à moins de `SNAP_DISTANCE`, sinon `null`.
  - `splitByCut(points): [polygonA, polygonB] | null` : les deux morceaux (ligne brisée + pourtour parcouru dans un sens ou dans l'autre, coins compris) ; `null` si un morceau est d'aire nulle.
  - `discardedPolygon(cut): polygon | null` : celui des deux qui contient `cut.discard` (pair-impair) ; `null` si aucun.
  - `toneValue(r, g, b, {brightness, contrast}): number` : gris `0.299r + 0.587g + 0.114b`, puis `(gris − 128) × (1 + contrast/100) + 128 + brightness × 1.28`, borné de 0 à 255.
  - `dilate(mask: Uint8Array, radius: number): Uint8Array` : masque 600 × 600 (1 = gardé) dilaté par un disque.
  - `renderPortrait(source: ImageData /* 600×600 recadré */, settings): ImageData` : masque des coupes, ton foncé si `toneValue < threshold` sinon ton clair, liseré (`dilate(mask, halo.width)` moins `mask`) en couleur `palette.halo`, le reste transparent.
  - `defaultSettings(palette): settings` (forme donnée dans Global Constraints, `cuts: []`, `crop: null`).
  - `checkFile(file): string | null` : message d'erreur français si `file.size > MAX_FILE_BYTES` ou type hors `image/jpeg|png|webp`, sinon `null`.
- `palette.json` : `{ "dark": "#20212B", "light": "#D9D8D2", "halo": "#D9D8D2" }` (le gris clair est à ajuster à l'œil à la tâche 5 sur le papier et les quatre aplats).

- [ ] **Step 1 : tests qui échouent** (`node:test` + `node:assert/strict`, `ImageData` remplacé par `{ width, height, data: Uint8ClampedArray }`) :
  - `snapToEdge([5, 300])` → `[0, 300]` ; `snapToEdge([300, 300])` → `null`.
  - `splitByCut([[0, 300], [600, 300]])` : deux rectangles 600 × 300 ; `splitByCut([[0, 100], [0, 400]])` → `null`.
  - `discardedPolygon` d'une coupe horizontale avec `discard: [300, 50]` : le morceau du haut ; avec `discard: [300, 300]` (sur la ligne) → `null`.
  - `toneValue(255, 255, 255, {brightness: 0, contrast: 0})` → 255 ; `toneValue(128, 128, 128, {brightness: 50, contrast: 0})` → 192.
  - `renderPortrait` sur une image grise uniforme 100, seuil 128, coupe horizontale qui jette le haut : pixel (300, 100) alpha 0, pixel (300, 500) = couleur `dark` opaque ; avec seuil 50 → couleur `light`.
  - halo activé, largeur 8 : pixel (300, 296) = couleur `halo` opaque ; (300, 280) transparent.
  - `palette.json` contient exactement `dark`, `light`, `halo`, chacun `#RRGGBB`.
  - `checkFile({ size: 11 * 1024 * 1024, type: 'image/jpeg' })` renvoie un message ; `{ type: 'image/heic' }` aussi.
- [ ] **Step 2 :** `npm run test:js` → échec (module absent).
- [ ] **Step 3 :** écrire `pipeline.js` (fonctions pures, aucun accès au DOM) et `palette.json`.
- [ ] **Step 4 :** `npm run test:js` → tout passe.
- [ ] **Step 5 : commit** « Atelier portrait : palette et calcul des deux tons ».

### Task 4 : champ Filament `PortraitStudio` et enregistrement

**Files :**
- Create : `app/Filament/Forms/Components/PortraitStudio.php` et sa vue `resources/views/filament/forms/components/portrait-studio.blade.php` (`php artisan make:filament-form-field PortraitStudio --no-interaction`, puis ranger aux chemins ci-dessus)
- Modify : `app/Filament/Resources/PublicFigures/Schemas/PublicFigureForm.php`, `Pages/CreatePublicFigure.php`, `Pages/EditPublicFigure.php`
- Test : `tests/Feature/Filament/PublicFigurePhotoTest.php`

**Interfaces :**
- Consumes : colonnes et règles de la tâche 1.
- Produces :
  - état du champ (statePath `photo`) : `array{path: ?string, original_path: ?string, settings: ?array, image: ?TemporaryUploadedFile, original: ?TemporaryUploadedFile}` ; `image` et `original` sont posés par le navigateur avec `$wire.upload('{statePath}.image', file)` et `'{statePath}.original'` ; `settings` par `$wire.set`.
  - `PortraitStudio::stateFromRecord(?PublicFigure $figure): array` : remplit `path`, `original_path`, `settings` depuis la fiche.
  - `PortraitStudio::toAttributes(array $state): array{photo_path: ?string, photo_original_path: ?string, photo_settings: ?array}`.
  - déshydratation du champ : range `image` dans `portraits/` et `original` dans `portraits/originals/` (disque `public`, nom aléatoire), remplace `path` / `original_path` par ces chemins, retire `image` et `original`.
  - variables passées à la vue : `imageUrl`, `originalUrl` (URL publiques ou `null`) et `palette` (contenu de `palette.json`, utilisé seulement pour une photo neuve).
  - quand `image` arrive : `photo_url` vidé (comme l'actuel `afterStateUpdated`).

- [ ] **Step 1 : adapter et écrire les tests** (`Storage::fake('public')`, modérateur) :
  - `enregistre l’image, l’original et les réglages remis par l’atelier` : `fillForm(['photo' => ['image' => UploadedFile::fake()->image('portrait.png', 600, 600), 'original' => UploadedFile::fake()->image('o.jpg', 1200, 900), 'settings' => [...forme de référence]]])->call('save')` → `photo_path` commence par `portraits/`, `photo_original_path` par `portraits/originals/`, les deux fichiers existent, `photo_settings['version'] === 1`.
  - `retouche sans changer l’original` : fiche déjà traitée, seuls `image` et `settings` envoyés → `photo_original_path` inchangé.
  - `ne touche à rien si l’atelier est fermé sans valider` : `fillForm` d'un autre champ (`description`) → `photo_path`, `photo_original_path`, `photo_url` inchangés.
  - `remplace un ancien lien invalide quand on téléverse une photo` : version existante adaptée à `photo.image` (+ `original`, `settings`).
  - test existant `enregistre une photo téléversée…` remplacé par le premier ci-dessus ; test du lien `photo_url` inchangé.
- [ ] **Step 2 :** `php artisan test --compact tests/Feature/Filament/PublicFigurePhotoTest.php` → échecs.
- [ ] **Step 3 :** champ PHP (méthodes ci-dessus, `->live()` pour vider `photo_url`), vue minimale (aperçu de `imageUrl`, boutons « Choisir une photo », « Retoucher » si `originalUrl`, « Retirer la photo ») ; dans `PublicFigureForm`, `PortraitStudio::make('photo')` remplace le `FileUpload` avec le texte d'aide actuel adapté (« … Recadrée, découpée et passée en deux tons dans l’atelier. ») ; `EditPublicFigure::mutateFormDataBeforeFill` ajoute `'photo' => PortraitStudio::stateFromRecord($figure)` ; les deux pages fusionnent `PortraitStudio::toAttributes($data['photo'])` dans `$data` (sans la clé `photo`) avant `SavePublicFigure`.
- [ ] **Step 4 :** relancer le fichier, puis `tests/Feature/Actions/PublicFigures` → tout passe ; Pint.
- [ ] **Step 5 : commit** « Champ PortraitStudio dans la fiche personnalité ».

### Task 5 : interface de l'atelier dans le navigateur

**Files :**
- Create : `resources/js/portrait-studio/index.js` (composant Alpine `portraitStudio`)
- Modify : vue `portrait-studio.blade.php` (tâche 4), `vite.config.js` (entrée `resources/js/portrait-studio/index.js`), `app/Providers/Filament/ModerationPanelProvider.php` (crochet `PanelsRenderHook::HEAD_END` qui rend `@vite('resources/js/portrait-studio/index.js')`, donc absent du site public)

**Interfaces :**
- Consumes : `pipeline.js` (tâche 3) ; état du champ et variables de vue (tâche 4).
- Produces : `Alpine.data('portraitStudio', ({ statePath, originalUrl, settings, palette }) => …)` enregistré sur `alpine:init`.

Comportement (tranché ici, pas dans la spec) :
- Fenêtre plein écran par `x-teleport="body"`, canevas d'affichage de 600 px, trois onglets « 1. Recadrer », « 2. Découper », « 3. Tons », boutons « Annuler » et « Valider ».
- Recadrer : cadre carré déplaçable à la souris, curseur de taille ; avertissement « La photo sera agrandie, la qualité baissera » si `crop.size < 600`.
- Découper : premier clic aimanté au bord (`snapToEdge`), sinon ignoré avec l'indication « Commencez sur le bord de l’image » ; clic intérieur = nouvel angle ; clic aimanté au bord = fin de coupe, puis « Cliquez sur le morceau à jeter » ; Retour arrière retire le dernier point, Échap abandonne la coupe ; boutons « Annuler la dernière coupe » et « Tout recommencer » ; morceaux jetés en damier.
- Tons : curseurs Luminosité, Contraste, Seuil ; case « Liseré clair » et curseur Épaisseur ; aperçu `renderPortrait` à chaque changement (`requestAnimationFrame`).
- Valider : `canvas.toBlob('image/png')` → `$wire.upload(statePath + '.image', new File([blob], 'portrait.png'))` ; l'original n'est envoyé que s'il vient d'être choisi ; `$wire.set(statePath + '.settings', settings)` avec `palette` recopiée ; fermeture.
- Retoucher : charge `originalUrl` et `settings` (dont sa propre `palette`) ; si l'image ne charge pas : « Photo d’origine introuvable, choisissez un nouveau fichier. »
- `checkFile` avant tout chargement ; message affiché dans la fenêtre.

- [ ] **Step 1 :** écrire `index.js` et compléter la vue.
- [ ] **Step 2 :** `npm run build` sans erreur ; `npm run test:js` passe toujours.
- [ ] **Step 3 : vérification dans le navigateur** (serveur de dev, compte modérateur de seed) : choisir une photo, recadrer, faire deux coupes, annuler une coupe, régler les tons, activer le liseré, valider, enregistrer ; recharger la page et « Retoucher » : même état ; supprimer l'original du disque et « Retoucher » : message attendu ; ouvrir puis « Annuler » : rien ne change ; voir le portrait sur la page publique de la personnalité et sur une carte tranchée de chaque couleur ; ajuster le gris clair de `palette.json` si besoin. Capture d'écran à l'appui.
- [ ] **Step 4 :** `php artisan test --compact tests/Feature/Filament tests/Feature/Actions/PublicFigures tests/Feature/Site/PortraitTest.php` → tout passe.
- [ ] **Step 5 : commit** « Atelier portrait : recadrage, découpe aux ciseaux et réglages des tons ».

### Task 6 : CLAUDE.md

- [ ] **Step 1 :** dans « Style visuel » de `CLAUDE.md`, une phrase : portraits téléversés traités par l'atelier (`PortraitStudio`, `resources/js/portrait-studio`), couleurs dans `resources/portrait/palette.json`, original et réglages conservés, tests JS par `npm run test:js`.
- [ ] **Step 2 : commit** « CLAUDE.md : atelier portrait ».
