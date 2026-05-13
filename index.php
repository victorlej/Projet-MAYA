<?php
/**
 * MAYA — Point d'entrée du dashboard apiculteur.
 * Toute la logique est éclatée dans config/, includes/, views/ et assets/.
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';     // session + PDO ($pdo, $user_id)
require_once __DIR__ . '/includes/helpers.php';  // send_downlink(), e()
require_once __DIR__ . '/includes/actions.php';  // POST handlers ($toast_msg, $toast_type)
require_once __DIR__ . '/includes/data.php';     // $mes_ruches, $ruche_active, $data, $alert_*

include __DIR__ . '/views/layout/head.php';
include __DIR__ . '/views/layout/topbar.php';
?>

<div class="layout">
    <?php include __DIR__ . '/views/layout/sidebar.php'; ?>

    <main class="main" id="main-content">
        <?php if (!$ruche_active): ?>
            <?php include __DIR__ . '/views/partials/welcome.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/views/partials/hero.php'; ?>
            <?php include __DIR__ . '/views/partials/tabs.php'; ?>
            <?php include __DIR__ . '/views/tabs/dashboard.php'; ?>
            <?php include __DIR__ . '/views/tabs/meteo.php'; ?>
            <?php include __DIR__ . '/views/tabs/actions.php'; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/views/layout/footer.php'; ?>
