<?php
/**
 * Traite tous les POST entrants (logout, ajout/suppression ruche, clés TTN, downlinks).
 * Alimente $toast_msg et $toast_type pour affichage dans le footer.
 */

$toast_msg = '';
$toast_type = '';

// Déconnexion
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Ajout d'une ruche
if (isset($_POST['ajout_ruche'])) {
    try {
        $pdo->prepare(
            'INSERT INTO ruches (device_id, nom_affichage, proprietaire_id, ttn_app_id, ttn_api_key)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $_POST['new_device_id'],
            $_POST['new_nom_ruche'],
            $user_id,
            $_POST['new_ttn_app_id']  ?? '',
            $_POST['new_ttn_api_key'] ?? '',
        ]);
        $toast_msg = 'Ruche ajoutée !';
        $toast_type = 'success';
    } catch (Exception $e) {
        $toast_msg = 'Ce Device ID est déjà pris.';
        $toast_type = 'error';
    }
}

// Suppression d'une ruche
if (isset($_POST['supprimer_ruche'])) {
    $pdo->prepare('DELETE FROM ruches WHERE device_id = ? AND proprietaire_id = ?')
        ->execute([$_POST['ruche_id'], $user_id]);
    $toast_msg = 'Ruche supprimée.';
    $toast_type = 'info';
}

// Mise à jour clés TTN
if (isset($_POST['connecter_reseau'])) {
    $pdo->prepare(
        'UPDATE ruches SET ttn_app_id = ?, ttn_api_key = ?
         WHERE device_id = ? AND proprietaire_id = ?'
    )->execute([
        $_POST['ttn_app_id'],
        $_POST['ttn_api_key'],
        $_POST['ruche_id'],
        $user_id,
    ]);
    $toast_msg = 'Clés TTN mises à jour !';
    $toast_type = 'success';
}

// Actionneurs (downlinks LoRaWAN)
$action_map = [
    'action_buzzer'        => [PAYLOAD_BUZZ, null],
    'action_moteur_ouvrir' => [PAYLOAD_OPEN, 'open'],
    'action_moteur_fermer' => [PAYLOAD_SHUT, 'closed'],
];
foreach ($action_map as $btn => [$hex, $door_state]) {
    if (!isset($_POST[$btn])) continue;
    [$ok, $msg] = send_downlink(
        trim($_POST['saved_app_id']  ?? ''),
        trim($_POST['saved_api_key'] ?? ''),
        trim($_POST['ruche_id']      ?? ''),
        $hex
    );
    $toast_msg  = $msg;
    $toast_type = $ok ? 'success' : 'error';
    if ($ok && $door_state) {
        $_SESSION['door_state_' . $_POST['ruche_id']] = $door_state;
    }
}
