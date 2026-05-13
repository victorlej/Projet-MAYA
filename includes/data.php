<?php
/**
 * Récupère les données nécessaires aux vues :
 *   $mes_ruches      : ruches de l'utilisateur
 *   $ruche_active    : ruche sélectionnée (ou null)
 *   $ruche_active_id : son device_id
 *   $periode         : 1h | 24h | 7j | 30j
 *   $data            : dernière mesure (clés temp/hum/poids/lum/pres/tact/lat/lon/date)
 *   $vrai_donnee     : true si une mesure existe
 *   $labels_graph    : dates pour les graphiques
 *   $poids_graph, $temp_graph, $hum_graph, $lum_graph
 *   $alert_temp, $alert_hum
 */

// Liste des ruches de l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM ruches WHERE proprietaire_id = ?');
$stmt->execute([$user_id]);
$mes_ruches = $stmt->fetchAll();

// Ruche active
$ruche_active_id = $_POST['ruche_id'] ?? $_GET['ruche'] ?? ($mes_ruches[0]['device_id'] ?? null);
$ruche_active = null;
foreach ($mes_ruches as $r) {
    if ($r['device_id'] === $ruche_active_id) { $ruche_active = $r; break; }
}

// Période + format SQL associé
$periode = $_POST['periode'] ?? '24h';
$periode_map = [
    '1h'  => ['INTERVAL 1 HOUR',  '%H:%i'],
    '24h' => ['INTERVAL 24 HOUR', '%H:00'],
    '7j'  => ['INTERVAL 7 DAY',   '%d/%m %H:00'],
    '30j' => ['INTERVAL 30 DAY',  '%d/%m'],
];
[$sql_interval, $sql_fmt] = $periode_map[$periode] ?? $periode_map['24h'];

// Valeurs par défaut
$data = [
    'temp' => 0, 'hum' => 0, 'poids' => 0, 'lum' => 0,
    'pres' => false, 'tact' => false,
    'lat' => 49.894, 'lon' => 2.295, 'date' => '—',
];
$vrai_donnee = false;
$labels_graph = $poids_graph = $temp_graph = $hum_graph = $lum_graph = [];

if ($ruche_active) {
    // Dernière mesure
    $req = $pdo->prepare('SELECT * FROM mesures WHERE device_id = ? ORDER BY date_mesure DESC LIMIT 1');
    $req->execute([$ruche_active_id]);
    if ($db = $req->fetch()) {
        $data = [
            'temp'  => $db['temperature'],
            'hum'   => $db['humidite'],
            'poids' => $db['poids'],
            'lum'   => $db['luminosite'],
            'pres'  => $db['alerte_presence'],
            'tact'  => $db['alerte_choc'],
            'lat'   => $db['lat'],
            'lon'   => $db['lon'],
            'date'  => date('d/m/Y H:i:s', strtotime($db['date_mesure'])),
        ];
        $vrai_donnee = true;
    }

    // Historique agrégé pour les graphiques
    $hist = $pdo->prepare(
        "SELECT DATE_FORMAT(date_mesure, '$sql_fmt') AS lbl,
                MIN(date_mesure) AS rd,
                ROUND(AVG(poids), 2)       AS ap,
                ROUND(AVG(temperature), 1) AS at_,
                ROUND(AVG(humidite), 1)    AS ah,
                ROUND(AVG(luminosite), 1)  AS al
         FROM mesures
         WHERE device_id = ? AND date_mesure >= DATE_SUB(NOW(), $sql_interval)
         GROUP BY lbl ORDER BY rd ASC"
    );
    $hist->execute([$ruche_active_id]);
    foreach ($hist->fetchAll() as $h) {
        $labels_graph[] = $h['lbl'];
        $poids_graph[]  = $h['ap'];
        $temp_graph[]   = $h['at_'];
        $hum_graph[]    = $h['ah'];
        $lum_graph[]    = $h['al'];
    }
}

// Alertes
$alert_temp = $vrai_donnee && ($data['temp'] < 30 || $data['temp'] > 37);
$alert_hum  = $vrai_donnee && $data['hum'] > 80;
