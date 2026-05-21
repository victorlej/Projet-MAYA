<header class="topbar anim-fade">
    <div class="topbar-left">
        <button class="menu-btn" onclick="toggleSidebar()" aria-label="Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                <line x1="4" y1="7"  x2="20" y2="7"/>
                <line x1="4" y1="12" x2="20" y2="12"/>
                <line x1="4" y1="17" x2="20" y2="17"/>
            </svg>
        </button>
        <div class="brand">
            <div class="brand-mark">M</div>
            <span><?= APP_NAME ?></span>
        </div>
    </div>

    <div class="topbar-right">
        <div class="user-pill">
            <span class="dot"></span>
            <?= e($_SESSION['nom'] ?? 'Apiculteur') ?>
        </div>
        <button class="icon-btn rotate-on-hover" onclick="toggleTheme()" title="Mode clair / sombre">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        </button>
        <form method="POST" style="margin:0;">
            <button type="submit" name="logout" class="logout-btn">Déconnexion</button>
        </form>
    </div>
</header>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
