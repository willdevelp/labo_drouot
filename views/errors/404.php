<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable — Laboratoire Drouot</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F4F6F9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wrap {
            text-align: center;
            padding: 2rem;
        }
        .code {
            font-size: 6rem;
            font-weight: 600;
            color: #0D47A1;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .icon {
            font-size: 3rem;
            color: #1E88E5;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.4rem;
            font-weight: 500;
            color: #1a2340;
            margin-bottom: 0.5rem;
        }
        p {
            color: #6b7a99;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #1E88E5;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: background 0.2s;
        }
        .btn:hover { background: #0D47A1; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="icon"><i class="fas fa-flask"></i></div>
        <div class="code">404</div>
        <h1>Page introuvable</h1>
        <p>La page que vous cherchez n'existe pas ou a été déplacée.</p>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>" class="btn">
            <i class="fas fa-home"></i> Retour à l'accueil
        </a>
    </div>
</body>
</html>

