<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=ruche_connectee;charset=utf8', 'root', 'Maya2026!');
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $mdp = $_POST['mdp'];

    if (isset($_POST['inscription'])) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, mot_de_passe) VALUES (?, ?)");
            $stmt->execute([$nom, $hash]);
            $msg = "Compte créé ! Connecte-toi maintenant.";
            $msg_type = 'success';
        } catch (Exception $e) {
            $msg = "Ce nom est déjà pris.";
            $msg_type = 'error';
        }
    } elseif (isset($_POST['connexion'])) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom = ?");
        $stmt->execute([$nom]);
        $user = $stmt->fetch();
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            header("Location: index.php"); exit;
        } else {
            $msg = "Identifiants incorrects.";
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · MAYA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --honey-50:  #fffbeb;
            --honey-100: #fef3c7;
            --honey-300: #fcd34d;
            --honey-500: #f59e0b;
            --honey-600: #d97706;
            --honey-700: #b45309;
            --honey-grad: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            --text: #1f2330;
            --text-soft: #6b7280;
            --easing: cubic-bezier(.4,0,.2,1);
            --bounce: cubic-bezier(.34,1.56,.64,1);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background:
                radial-gradient(circle at 15% 10%, rgba(251,191,36,.35), transparent 45%),
                radial-gradient(circle at 85% 90%, rgba(217,119,6,.25), transparent 50%),
                linear-gradient(135deg, #fffbeb 0%, #fef3c7 50%, #fde68a 100%);
            color: var(--text);
            min-height: 100vh;
            display: grid; place-items: center;
            position: relative;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Hexagones d'arrière-plan ===== */
        body::before {
            content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background-image:
                url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='115' viewBox='0 0 100 115'><polygon points='50,5 93,28 93,87 50,110 7,87 7,28' fill='none' stroke='%23f59e0b' stroke-width='1.2' opacity='.35'/></svg>");
            background-size: 100px 115px;
            mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
            animation: hex-drift 60s linear infinite;
            opacity: .6;
        }
        @keyframes hex-drift { from { background-position: 0 0; } to { background-position: 100px 115px; } }

        /* ===== Animations ===== */
        @keyframes fadeUp { from { opacity:0; transform: translateY(30px); } to { opacity:1; transform: none; } }
        @keyframes pop    { 0%{transform:scale(.85);opacity:0;} 60%{transform:scale(1.06);} 100%{transform:scale(1);opacity:1;} }
        @keyframes float-y { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-12px);} }
        @keyframes spin   { to { transform: rotate(360deg); } }
        @keyframes shimmer { 0%{background-position:-200% 0;} 100%{background-position:200% 0;} }
        @keyframes glow {
            0%,100% { box-shadow: 0 20px 60px rgba(245,158,11,.25), 0 0 0 0 rgba(245,158,11,.4); }
            50%     { box-shadow: 0 25px 80px rgba(245,158,11,.35), 0 0 0 16px rgba(245,158,11,0); }
        }
        @keyframes wing-flap { 0%,100%{transform:scaleY(1);} 50%{transform:scaleY(.35);} }
        @keyframes bee-path {
            0%   { transform: translate(0, 0) rotate(8deg); }
            25%  { transform: translate(30vw, -12vh) rotate(-8deg); }
            50%  { transform: translate(60vw, 8vh) rotate(12deg); }
            75%  { transform: translate(85vw, -6vh) rotate(-6deg); }
            100% { transform: translate(110vw, 6vh) rotate(8deg); }
        }
        @keyframes pollen-fall {
            0%   { transform: translate(0, -20px) rotate(0); opacity: 0; }
            10%  { opacity: 1; }
            100% { transform: translate(var(--dx,40px), 110vh) rotate(720deg); opacity: 0; }
        }
        @keyframes shake {
            0%,100%{transform:translateX(0);}
            20%,60%{transform:translateX(-8px);}
            40%,80%{transform:translateX(8px);}
        }

        /* ===== Décor : abeilles + pollen ===== */
        .bee-layer, .pollen-layer {
            position: fixed; inset: 0; pointer-events: none; z-index: 1; overflow: hidden;
        }
        .bee {
            position: absolute; width: 50px; height: 34px;
            filter: drop-shadow(0 4px 6px rgba(181, 83, 9, .25));
            animation: bee-path 22s linear infinite;
        }
        .bee .wing { transform-origin: center bottom; animation: wing-flap .14s linear infinite; }
        .pollen {
            position: absolute; top: -20px; width: 8px; height: 8px; border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #fde68a, #f59e0b 80%);
            box-shadow: 0 0 8px rgba(252,211,77,.7);
            animation: pollen-fall var(--dur, 14s) linear infinite;
            opacity: .8;
        }

        /* ===== Carte de login ===== */
        .login-wrap {
            position: relative; z-index: 5;
            width: 100%; max-width: 420px; padding: 20px;
            animation: fadeUp .8s var(--easing) both;
        }
        .login-card {
            background: rgba(255, 255, 255, .82);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(245, 158, 11, .3);
            border-radius: 28px;
            padding: 44px 36px 36px;
            text-align: center;
            position: relative;
            animation: glow 4s ease-in-out infinite;
        }
        .login-card::before {
            content: ''; position: absolute; top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: 30px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706, #fbbf24);
            background-size: 300% 300%;
            z-index: -1;
            animation: shimmer 4s linear infinite;
            opacity: .35;
        }

        .logo-wrap {
            width: 88px; height: 88px; margin: 0 auto 20px;
            background: var(--honey-grad);
            border-radius: 24px;
            display: grid; place-items: center;
            font-size: 2.6rem;
            box-shadow: 0 15px 40px rgba(245,158,11,.45);
            animation: float-y 4s ease-in-out infinite;
            position: relative;
        }
        .logo-wrap::after {
            content: ''; position: absolute; inset: -10px; border-radius: 32px;
            border: 2px dashed rgba(245,158,11,.45);
            animation: spin 18s linear infinite;
        }

        h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.2rem; font-weight: 800; margin: 0 0 6px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
            background-size: 200% 200%;
            -webkit-background-clip: text; background-clip: text; color: transparent;
            animation: shimmer 5s ease-in-out infinite;
            letter-spacing: -.02em;
        }
        .subtitle {
            color: var(--text-soft); font-size: .95rem; margin-bottom: 28px;
            font-weight: 500;
        }

        .alert {
            padding: 12px 16px; border-radius: 12px; margin-bottom: 18px;
            font-size: .88rem; font-weight: 600;
            animation: pop .5s var(--bounce) both;
            display: flex; align-items: center; gap: 8px; justify-content: center;
        }
        .alert.success { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.3); }
        .alert.error   { background: rgba(239,68,68,.10); color: #b91c1c; border: 1px solid rgba(239,68,68,.3); animation: pop .5s var(--bounce), shake .5s var(--easing) .3s; }

        .field {
            position: relative; margin-bottom: 16px;
            animation: fadeUp .6s var(--easing) both;
        }
        .field:nth-child(1) { animation-delay: .1s; }
        .field:nth-child(2) { animation-delay: .2s; }
        .field input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 1.5px solid rgba(245,158,11,.2);
            border-radius: 14px;
            background: rgba(255,255,255,.7);
            font-size: .95rem; color: var(--text);
            font-family: inherit;
            transition: all .3s var(--easing);
            outline: none;
        }
        .field input::placeholder { color: var(--text-soft); }
        .field input:focus {
            border-color: var(--honey-500);
            background: white;
            box-shadow: 0 0 0 4px rgba(245,158,11,.15), 0 8px 20px rgba(245,158,11,.1);
            transform: translateY(-1px);
        }
        .field .ico {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--honey-600); pointer-events: none; font-size: 1.1rem;
            transition: transform .3s var(--bounce);
        }
        .field input:focus + .ico { transform: translateY(-50%) scale(1.15) rotate(-8deg); }

        .btn {
            width: 100%; padding: 14px; margin-top: 8px;
            border: none; border-radius: 14px;
            font-weight: 700; font-size: .95rem; cursor: pointer;
            font-family: inherit;
            transition: all .3s var(--easing);
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            position: relative; overflow: hidden;
            animation: fadeUp .6s var(--easing) .3s both;
        }
        .btn::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(115deg, transparent 30%, rgba(255,255,255,.4) 50%, transparent 70%);
            background-size: 200% 100%; background-position: -100% 0;
            transition: background-position .6s var(--easing);
        }
        .btn:hover::after { background-position: 200% 0; }
        .btn-primary {
            background: var(--honey-grad); color: white;
            box-shadow: 0 10px 25px rgba(245,158,11,.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(245,158,11,.5); }
        .btn-primary:active { transform: translateY(0); }
        .btn-ghost {
            background: transparent; color: var(--honey-700);
            border: 1.5px solid rgba(245,158,11,.4);
            margin-top: 10px;
            animation-delay: .4s;
        }
        .btn-ghost:hover {
            background: rgba(245,158,11,.08); border-color: var(--honey-500);
            transform: translateY(-1px);
        }

        .footer-note {
            margin-top: 22px; font-size: .78rem; color: var(--text-soft);
            animation: fadeUp .6s var(--easing) .5s both;
        }
        .footer-note span { color: var(--honey-600); font-weight: 600; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
            .bee-layer, .pollen-layer { display: none; }
        }
    </style>
</head>
<body>

    <!-- Décor abeilles -->
    <div class="bee-layer">
        <div class="bee" style="top: 12%; left: -80px; animation-duration: 24s; animation-delay: -2s; transform: scale(.85);">
            <svg viewBox="0 0 64 44" xmlns="http://www.w3.org/2000/svg">
                <ellipse class="wing" cx="22" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse class="wing" cx="34" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse cx="32" cy="26" rx="18" ry="12" fill="#f59e0b"/>
                <rect x="22" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <rect x="32" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <circle cx="48" cy="22" r="6" fill="#1f2330"/>
                <circle cx="50" cy="20" r="1.4" fill="#fff"/>
                <path d="M52 18 L58 12 M52 24 L60 22" stroke="#1f2330" stroke-width="1.5" stroke-linecap="round" fill="none"/>
            </svg>
        </div>
        <div class="bee" style="top: 38%; left: -80px; animation-duration: 30s; animation-delay: -10s; transform: scale(.65);">
            <svg viewBox="0 0 64 44" xmlns="http://www.w3.org/2000/svg">
                <ellipse class="wing" cx="22" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse class="wing" cx="34" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse cx="32" cy="26" rx="18" ry="12" fill="#f59e0b"/>
                <rect x="22" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <rect x="32" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <circle cx="48" cy="22" r="6" fill="#1f2330"/>
                <circle cx="50" cy="20" r="1.4" fill="#fff"/>
            </svg>
        </div>
        <div class="bee" style="top: 72%; left: -80px; animation-duration: 26s; animation-delay: -18s; transform: scale(1);">
            <svg viewBox="0 0 64 44" xmlns="http://www.w3.org/2000/svg">
                <ellipse class="wing" cx="22" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse class="wing" cx="34" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
                <ellipse cx="32" cy="26" rx="18" ry="12" fill="#f59e0b"/>
                <rect x="22" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <rect x="32" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
                <circle cx="48" cy="22" r="6" fill="#1f2330"/>
                <circle cx="50" cy="20" r="1.4" fill="#fff"/>
            </svg>
        </div>
    </div>

    <!-- Pollen -->
    <div class="pollen-layer" id="pollen-layer"></div>

    <!-- Carte de connexion -->
    <div class="login-wrap">
        <div class="login-card">
            <div class="logo-wrap">🐝</div>
            <h1>MAYA</h1>
            <p class="subtitle">Le rucher connecté de demain</p>

            <?php if ($msg): ?>
                <div class="alert <?= htmlspecialchars($msg_type) ?>">
                    <?= $msg_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <div class="field">
                    <input type="text" name="nom" placeholder="Pseudo apiculteur" required autofocus>
                    <span class="ico">👤</span>
                </div>
                <div class="field">
                    <input type="password" name="mdp" placeholder="Mot de passe" required>
                    <span class="ico">🔒</span>
                </div>
                <button type="submit" name="connexion" class="btn btn-primary">
                    Se connecter →
                </button>
                <button type="submit" name="inscription" class="btn btn-ghost">
                    Créer un compte
                </button>
            </form>

            <div class="footer-note">
                <span>🍯 MAYA</span> · Suivi de ruches IoT
            </div>
        </div>
    </div>

    <script>
        // Particules de pollen générées en JS
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const layer = document.getElementById('pollen-layer');
            const count = window.innerWidth < 768 ? 16 : 32;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.className = 'pollen';
                const dur = 10 + Math.random() * 14;
                const size = 4 + Math.random() * 7;
                p.style.left = Math.random() * 100 + 'vw';
                p.style.width = p.style.height = size + 'px';
                p.style.setProperty('--dur', dur + 's');
                p.style.setProperty('--dx', (Math.random() * 200 - 100) + 'px');
                p.style.animationDelay = -(Math.random() * dur) + 's';
                p.style.opacity = (0.4 + Math.random() * 0.5).toString();
                layer.appendChild(p);
            }
        })();
    </script>
</body>
</html>
