# Prophet — « Parole tenue »

Registre des promesses et prédictions de personnalités publiques : citées mot pour mot, datées, sourcées, archivées, puis tranchées par un verdict sourcé à leur échéance.

Voir `docs/specification.md` et les maquettes dans `docs/maquettes/`.

## Installation locale

Prérequis : PHP 8.4, Composer, Node, PostgreSQL (le PostgreSQL Homebrew de Charles écoute sur le port 5433 ; adapter `DB_PORT` sinon).

```bash
composer install
cp .env.example .env
php artisan key:generate
psql -h 127.0.0.1 -p 5433 -d postgres -c "create role prophet login"
createdb -h 127.0.0.1 -p 5433 -O prophet prophet
createdb -h 127.0.0.1 -p 5433 -O prophet prophet_test
php artisan migrate --seed
./vendor/bin/pest
```

Le seeder crée un jeu de démonstration entièrement fictif (repris des maquettes) et des comptes `admin`, `s.leroy` (modératrice), `m.durand` et `j.martin`, tous en `@example.test`, mot de passe `password`.

## Mise en ligne

Le site tourne sur le VPS dans Docker, derrière Traefik (voir `~/dev/vps-infra`), à l’adresse `https://paroletenue.charlescoiffier.fr`.
Chaque poussée sur `main` lance les tests, construit l’image (`Dockerfile`) et la déploie (`.github/workflows/deploy.yml`).

Les secrets sont dans `~/srv/paroletenue/.env` sur le VPS (modèle : `deploy/env.example`).

Commandes utiles sur le VPS, depuis `~/srv/paroletenue` :

```bash
docker compose exec app php artisan db:seed --force                              # thèmes (une fois)
docker compose exec -it app php artisan users:create-admin vous@exemple.fr pseudo   # compte admin
docker compose logs -f app horizon                                              # journaux
```
