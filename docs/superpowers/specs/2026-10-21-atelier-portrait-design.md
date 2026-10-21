# Atelier portrait : recadrer, découper, passer en photocopie

Conception du 21 octobre 2026. Sous-projet de Parole tenue, dans le panneau de modération Filament.

## Pourquoi

La charte éditoriale veut des portraits « en photocopie » : un sujet à deux tons, posé sans fond sur le papier crème ou sur l'aplat de couleur d'une carte tranchée. Aujourd'hui, la photo téléversée est seulement recadrée au carré, et l'effet est imité en CSS (`grayscale contrast-[1.7]` dans `<x-portrait>`), fond compris. Charles n'a encore jamais ajouté de portrait : l'outil doit lui permettre d'obtenir le bon rendu directement dans la fiche d'une personnalité, sans logiciel de retouche.

L'image de référence fournie par Charles est un chat en **deux tons plats** : gris très foncé pour les ombres, gris très clair pour les lumières, aucun dégradé. Le détail tient dans la frontière entre les deux. Un liseré clair entoure le sujet, comme un autocollant découpé, et le fond est transparent.

## Objectifs

- Dans la fiche d'une personnalité (`/moderation`), transformer la photo choisie avant de l'enregistrer : **recadrer → découper le fond → régler les tons → valider**.
- Découpe **à la main, façon ciseaux** (décision de Charles) : une ligne brisée qui part d'un point du **pourtour** de l'image et finit sur un autre point du pourtour. Chaque clic ajoute un segment.
- Quelques réglages avec aperçu en direct : **luminosité**, **contraste**, **seuil** entre les deux tons (décision de Charles).
- **Conserver la photo d'origine** sur le serveur pour pouvoir refaire la découpe ou les réglages plus tard (décision de Charles).
- Liseré clair autour du sujet : **à tester** (Charles), donc proposé en option qu'on peut activer ou couper.

## Non-objectifs

- Détourage automatique par apprentissage automatique (lourd, et le VPS n'a qu'un cœur).
- Traitement des photos données par lien externe (`photo_url`) : elles gardent le filtre CSS actuel.
- Téléversement ou traitement de photo par l'API `/api/v1`, ou par les visiteurs.
- Courbes de Bézier, gomme, pinceau : la découpe reste une ligne brisée.

## Anti-objectifs (ce qui serait un échec même si ça marche)

- Envoyer la photo à un service extérieur.
- Faire calculer l'image par le serveur (CPU du VPS) ou ajouter une dépendance lourde.
- Des portraits disparates : les deux tons sont fixes, seul le seuil et la lumière varient d'une photo à l'autre.
- Un outil qui plante à l'enregistrement, comme l'éditeur d'image de Filament 5 (écarté pour cette raison, voir `PublicFigureForm`).

## Contraintes

- Filament 5, Livewire, Alpine ; pas de nouvelle dépendance npm ou Composer sans accord de Charles. Le canevas HTML suffit.
- Tout le calcul d'image se fait **dans le navigateur du modérateur**. Le serveur reçoit l'image finale, l'original et les réglages.
- Interface en français ; code en anglais.
- La logique métier reste dans `SavePublicFigure` (règle du projet : aucune règle métier dans une vue ou un composant).
- Les tests tournent sur PostgreSQL avec Pest ; aucun appel HTTP réel.

## Ce qui est décidé

### Le parcours

Dans la section « Photo » de `PublicFigureForm`, le `FileUpload` actuel est remplacé par l'atelier. Choisir un fichier ouvre l'atelier ; une photo déjà traitée affiche un bouton « Retoucher » qui le rouvre dans l'état enregistré.

1. **Recadrer.** Un cadre carré sur la photo d'origine, qu'on déplace et qu'on agrandit ou réduit. Résultat final : 600 × 600 (comme aujourd'hui).
2. **Découper.** Sur l'image recadrée :
   - le premier clic doit tomber sur le pourtour (un clic proche du bord s'y aimante) ;
   - chaque clic suivant à l'intérieur pose un angle de la ligne brisée ;
   - un clic sur le pourtour termine la coupe ;
   - l'image est alors partagée en deux morceaux : on clique sur celui qu'on jette, il devient transparent (damier en aperçu) ;
   - plusieurs coupes successives sont possibles (ôter un coin de fond entre le bras et le corps) ;
   - « Annuler la dernière coupe » et « Tout recommencer » ; pendant une coupe, Retour arrière retire le dernier point et Échap abandonne la coupe en cours.
3. **Régler les tons.** Curseurs luminosité, contraste et seuil, aperçu en direct de l'image finale. Case « Liseré clair » (désactivée par défaut) avec un curseur d'épaisseur.
4. **Valider.** L'atelier produit le PNG final et le remet au formulaire ; l'enregistrement de la fiche fait le reste.

### Le rendu

- Chaque pixel conservé est converti en niveau de gris, ajusté par la luminosité et le contraste, puis comparé au seuil : en dessous, le **ton foncé** ; au-dessus, le **ton clair**.
- **Les couleurs sont dans un fichier JSON** du dépôt (décision de Charles), pour pouvoir les changer plus tard : ton foncé, ton clair et couleur du liseré. Valeurs de départ : encre `#20212B` pour le ton foncé ; pour le ton clair et le liseré, un gris clair proche de celui de la référence, lisible sur le papier crème comme sur les quatre aplats de verdict (valeur choisie par le constructeur). L'atelier lit ce fichier ; aucune couleur n'est écrite en dur dans le code.
- Changer le fichier ne touche que les **photos traitées ensuite** (décision de Charles) : les PNG existants ne sont pas recalculés. Les couleurs utilisées sont recopiées dans les réglages de chaque photo, et « Retoucher » une photo existante reprend ses propres couleurs, pas celles du fichier. *(Ce dernier point est un choix de Claude.)*
- Pixels jetés : transparents.
- Liseré, si activé : une bande du ton clair, de l'épaisseur choisie, le long des coupes, sous le sujet. Pas de liseré là où le sujet touche le bord de l'image (bas des épaules) : il y est coupé net. *(Choix de Claude.)*
- Une photo téléversée avant l'atelier (sans original ni réglages) ne peut pas être retouchée : on en téléverse une nouvelle. *(Choix de Claude.)*
- Sortie : PNG 600 × 600 avec transparence.

### Ce qui est enregistré

- `photo_path` : le PNG final (disque `public`, dossier `portraits`), comme aujourd'hui.
- Nouvelle colonne : chemin de la photo d'origine. Elle n'est pas affichée sur le site.
- Nouvelle colonne JSON : les réglages, suffisants pour rouvrir l'atelier à l'identique (cadrage dans les coordonnées de l'original, chaque coupe avec ses points et le morceau jeté, luminosité, contraste, seuil, liseré et son épaisseur).
- `SavePublicFigure` supprime l'original devenu inutile, comme il le fait déjà pour `photo_path`, et refuse l'enregistrement si un PNG traité arrive sans original ou sans réglages (les trois vont ensemble).
- Le journal (`snapshot`) continue de tracer `photo_path` ; l'original et les réglages n'y figurent pas.
- Le comportement actuel est conservé : téléverser une photo vide le lien `photo_url`.

### Le site public

`<x-portrait>` affiche une photo traitée (réglages présents) **telle quelle** : ni `grayscale`, ni `contrast`, ni `mix-blend-multiply`, puisque ses tons sont déjà ceux de la charte et que le fond est transparent. Les photos par lien externe et les anciennes photos téléversées sans réglages gardent le filtre CSS actuel.

### Tests

- Pest : `SavePublicFigure` enregistre l'image, l'original et les réglages, supprime l'ancien original quand la photo change ou disparaît, refuse un ensemble incomplet ; le formulaire Filament enregistre ce que l'atelier lui remet ; `<x-portrait>` n'applique pas le filtre à une photo traitée et le garde sinon. Adapter `tests/Feature/Filament/PublicFigurePhotoTest.php`.
- Le JavaScript de l'atelier n'a pas de banc de test dans le projet : vérification à la main dans le navigateur (recadrage, coupes, annulation, réglages, retouche après rechargement).

## Laissé au constructeur

- La forme technique du composant Filament (champ personnalisé, vue Blade + composant Alpine) et la façon de transmettre les fichiers produits par le canevas à Livewire.
- Le chargement du script de l'atelier (entrée Vite supplémentaire, `FilamentAsset`, ou autre), tant qu'il ne pèse pas sur le site public.
- Disque et dossier de l'original (`public` ou privé), noms des colonnes, structure exacte du JSON de réglages.
- L'emplacement et la forme du fichier JSON des couleurs (lisible côté PHP comme côté JavaScript), la valeur de départ du gris clair, les bornes et valeurs par défaut des curseurs, l'épaisseur par défaut du liseré, la distance d'aimantation au bord.
- Le calcul des deux morceaux d'une coupe (polygones ou masque) et le cas d'une ligne qui se croise elle-même.
- La taille minimale de la photo d'origine et le message si le cadrage est plus petit que 600 px.
- La mise en page de l'atelier (fenêtre modale ou zone dans la section « Photo »), dans le thème compact du panneau.
