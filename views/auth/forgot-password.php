<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — Laboratoire Drouot</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --blue-primary:  #1E88E5;
            --blue-dark:     #0D47A1;
            --green-valid:   #2E7D32;
            --red-critical:  #C62828;
            --bg-app:        #F4F6F9;
            --white:         #ffffff;
            --text-dark:     #1a2340;
            --text-muted:    #6b7a99;
            --border:        #dde3f0;
            --shadow-card:   0 8px 32px rgba(13,71,161,0.10);
            --shadow-hover:  0 16px 48px rgba(13,71,161,0.18);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg-app);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(30,136,229,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 90% 80%, rgba(13,71,161,0.10) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(244,246,249,1) 0%, transparent 100%);
            z-index: 0;
        }
        .deco-cross {
            position: fixed; opacity: 0.04; font-size: 20rem;
            color: var(--blue-dark); font-weight: 900; line-height: 1;
            z-index: 0; user-select: none;
        }
        .deco-cross.tl { top: -4rem; left: -4rem; }
        .deco-cross.br { bottom: -6rem; right: -4rem; font-size: 28rem; }

        .login-wrapper {
            position: relative; z-index: 1; width: 100%; max-width: 460px;
            padding: 1.5rem; animation: fadeInUp 0.6s ease both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .logo-circle {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--blue-primary), var(--blue-dark));
            border-radius: 20px; display: inline-flex; align-items: center;
            justify-content: center; margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(13,71,161,0.25);
        }
        .logo-circle i { font-size: 1.8rem; color: white; }
        .login-title {
            font-family: 'Playfair Display', serif; font-size: 1.6rem;
            color: var(--blue-dark); letter-spacing: -0.02em;
        }
        .login-subtitle {
            font-size: 0.85rem; color: var(--text-muted); margin-top: 0.3rem;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .login-card {
            background: var(--white); border-radius: 24px; padding: 2.5rem;
            box-shadow: var(--shadow-card); border: 1px solid rgba(221,227,240,0.8);
        }
        .card-intro {
            font-size: 0.85rem; color: var(--text-muted);
            margin-bottom: 1.5rem; line-height: 1.5;
        }
        .card-label {
            font-size: 0.78rem; font-weight: 600; color: var(--text-muted);
            letter-spacing: 0.08em; text-transform: uppercase;
            margin-bottom: 0.5rem; display: block;
        }
        .input-group { position: relative; margin-bottom: 1.25rem; }
        .input-group i.input-icon {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.95rem; transition: color 0.2s;
        }
        .input-group input:focus ~ i.input-icon { color: var(--blue-primary); }
        .form-input {
            width: 100%; padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 1.5px solid var(--border); border-radius: 12px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
            color: var(--text-dark); background: #f8fafd;
            transition: all 0.2s ease; outline: none;
        }
        .form-input:focus {
            border-color: var(--blue-primary); background: white;
            box-shadow: 0 0 0 4px rgba(30,136,229,0.08);
        }
        .form-input::placeholder { color: #b0b8cc; }
        .alert-error {
            background: #fff5f5; border: 1px solid #fccfcf;
            border-left: 4px solid var(--red-critical); border-radius: 10px;
            padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem;
            color: var(--red-critical); display: flex; align-items: center; gap: 0.5rem;
        }
        .alert-success {
            background: #f0faf1; border: 1px solid #c8e6c9;
            border-left: 4px solid var(--green-valid); border-radius: 10px;
            padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem;
            color: var(--green-valid); display: flex; align-items: center; gap: 0.5rem;
        }
        .btn-login {
            width: 100%; padding: 0.95rem;
            background: linear-gradient(135deg, var(--blue-primary) 0%, var(--blue-dark) 100%);
            color: white; border: none; border-radius: 12px;
            font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(13,71,161,0.25);
            letter-spacing: 0.02em; margin-top: 0.5rem;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .btn-login:active { transform: translateY(0); }
        .login-footer {
            text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted);
        }
        .login-footer a { color: var(--blue-primary); text-decoration: none; font-weight: 500; }
        .login-footer a:hover { text-decoration: underline; }
        .version-badge {
            text-align: center; margin-top: 1.5rem; font-size: 0.72rem;
            color: #b0b8cc; letter-spacing: 0.05em;
        }
        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.5rem; }
            .login-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

    <div class="deco-cross tl">✚</div>
    <div class="deco-cross br">✚</div>

    <div class="login-wrapper">

        <div class="login-header">
            <div class="logo-circle">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="login-title">Mot de passe oublié</h1>
            <p class="login-subtitle">Laboratoire Drouot · Douala</p>
        </div>

        <div class="login-card">

            <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert-success">
                <i class="fas fa-circle-check"></i>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <p class="card-intro">
                Saisissez l'adresse email associée à votre compte.
                Un lien de réinitialisation vous sera envoyé.
            </p>

            <form method="POST" action="<?= BASE_URL ?>forgot-password">
                <div>
                    <label class="card-label">Adresse email</label>
                    <div class="input-group">
                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="votre@email.com"
                            autocomplete="email"
                            required
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-paper-plane"></i>&nbsp;&nbsp;Envoyer le lien
                </button>
            </form>
            <?php endif; ?>

            <div class="login-footer">
                <a href="<?= BASE_URL ?>login"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
            </div>
        </div>

        <div class="version-badge">
            © <?= date('Y') ?> Laboratoire Drouot · Douala
        </div>
    </div>

</body>
</html>