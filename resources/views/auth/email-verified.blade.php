{{-- Page provisoire : le site public (étape 5) lui donnera la mise en page des maquettes. --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Adresse confirmée — {{ config('app.name') }}</title>
    <style>
        body { margin: 0; font-family: Inter, system-ui, sans-serif; color: #202020; background: #FBFAF8; }
        main { max-width: 560px; margin: 15vh auto; padding: 0 16px; }
        h1 { font-size: 22px; }
        a { color: #2552C6; }
    </style>
</head>
<body>
<main>
    <h1>Bienvenue, {{ $user->username }} : votre adresse est confirmée.</h1>
    <p>Vous pouvez maintenant proposer des cartes et des verdicts. Les cartes que vous aviez proposées avec cette adresse sont rattachées à votre compte.</p>
    <p><a href="{{ url('/') }}">Retour au site</a></p>
</main>
</body>
</html>
