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
        blockquote { margin: 16px 0; padding-left: 12px; border-left: 3px solid #2552C6; }
        a { color: #2552C6; }
    </style>
</head>
<body>
<main>
    <h1>Merci, votre adresse est confirmée.</h1>
    <blockquote>{{ $card->quote }}</blockquote>
    <p>Votre proposition est entre les mains des modérateurs. Vous recevrez un e-mail dès qu’elle sera publiée ou si elle n’est pas retenue.</p>
    <p><a href="{{ url('/') }}">Retour au site</a></p>
</main>
</body>
</html>
