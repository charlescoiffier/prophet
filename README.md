# Prophet — « Parole tenue »

Registre des promesses et prédictions de personnalités publiques : citées mot pour mot, datées, sourcées, archivées, puis tranchées par un verdict sourcé à leur échéance.

Voir `docs/specification.md` et les maquettes dans `docs/maquettes/`.

## Installation locale

Prérequis : PHP 8.4, Composer, Node, PostgreSQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
createdb prophet && createdb prophet_test
php artisan migrate --seed
./vendor/bin/pest
```

Le seeder crée un jeu de démonstration entièrement fictif (repris des maquettes) et des comptes `admin`, `s.leroy` (modératrice), `m.durand` et `j.martin`, tous en `@example.test`, mot de passe `password`.
