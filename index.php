<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$pdo = new PDO('mysql:host=localhost;dbname=ruche_connectee;charset=utf8', 'root', 'Maya2026!', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$user_id = $_SESSION['user_id'];
$toast_msg = ""; $toast_type = "";

// --- GESTION DES ACTIONS ---
if (isset($_POST['logout'])) { session_destroy(); header("Location: login.php"); exit; }

if (isset($_POST['ajout_ruche'])) {
    $stmt = $pdo->prepare("INSERT INTO ruches (device_id, nom_affichage, proprietaire_id, ttn_app_id, ttn_api_key) VALUES (?, ?, ?, ?, ?)");
    try { 
        $stmt->execute([$_POST['new_device_id'], $_POST['new_nom_ruche'], $user_id, $_POST['new_ttn_app_id'], $_POST['new_ttn_api_key']]); 
        $toast_msg = "Ruche ajoutée ! 🐝"; $toast_type = "success";
    } catch (Exception $e) { $toast_msg = "Erreur : Ce Device ID est déjà pris."; $toast_type = "error"; }
}

if (isset($_POST['supprimer_ruche'])) {
    $stmt = $pdo->prepare("DELETE FROM ruches WHERE device_id = ? AND proprietaire_id = ?");
    $stmt->execute([$_POST['ruche_id'], $user_id]);
    $toast_msg = "Ruche supprimée."; $toast_type = "info";
}

if (isset($_POST['connecter_reseau'])) {
    $stmt = $pdo->prepare("UPDATE ruches SET ttn_app_id=?, ttn_api_key=? WHERE device_id=? AND proprietaire_id=?");
    $stmt->execute([$_POST['ttn_app_id'], $_POST['ttn_api_key'], $_POST['ruche_id'], $user_id]);
    $toast_msg = "Clés TTN mises à jour ! 🚀"; $toast_type = "success";
}

// Actionneurs
if (isset($_POST['action_buzzer'])) {
    $ttn_app = trim($_POST['saved_app_id'] ?? ''); $ttn_key = trim($_POST['saved_api_key'] ?? ''); $dev_id = trim($_POST['ruche_id'] ?? '');
    if (empty($ttn_app) || empty($ttn_key)) { $toast_msg = "❌ Clés TTN manquantes."; $toast_type = "error"; } 
    else {
        $url = "https://eu1.cloud.thethings.network/api/v3/as/applications/" . urlencode($ttn_app) . "/devices/" . urlencode($dev_id) . "/down/push";
        $data = json_encode(["downlinks" => [["f_port" => 1, "frm_payload" => base64_encode(hex2bin("01")), "priority" => "NORMAL"]]]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ttn_key", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, $data); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if($httpcode >= 200 && $httpcode < 300) { $toast_msg = "✅ Alarme en file d'attente !"; $toast_type = "success"; } 
        else { $toast_msg = "❌ Échec TTN (Erreur $httpcode)."; $toast_type = "error"; }
    }
}

if (isset($_POST['action_moteur'])) {
    $ttn_app = trim($_POST['saved_app_id'] ?? ''); $ttn_key = trim($_POST['saved_api_key'] ?? ''); $dev_id = trim($_POST['ruche_id'] ?? '');
    if (empty($ttn_app) || empty($ttn_key)) { $toast_msg = "❌ Clés TTN manquantes."; $toast_type = "error"; } 
    else {
        $url = "https://eu1.cloud.thethings.network/api/v3/as/applications/" . urlencode($ttn_app) . "/devices/" . urlencode($dev_id) . "/down/push";
        $data = json_encode(["downlinks" => [["f_port" => 1, "frm_payload" => base64_encode(hex2bin("02")), "priority" => "NORMAL"]]]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ttn_key", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, $data); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if($httpcode >= 200 && $httpcode < 300) { $toast_msg = "✅ Ordre d'ouverture envoyé !"; $toast_type = "success"; } 
        else { $toast_msg = "❌ Échec TTN (Erreur $httpcode)."; $toast_type = "error"; }
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$stmt = $pdo->prepare("SELECT * FROM ruches WHERE proprietaire_id = ?");
$stmt->execute([$user_id]);
$mes_ruches = $stmt->fetchAll();

$ruche_active_id = $_POST['ruche_id'] ?? ($_GET['ruche'] ?? ($mes_ruches[0]['device_id'] ?? null));
$ruche_active = null;
$data = ['temp' => 0, 'hum' => 0, 'poids' => 0, 'lum' => 0, 'pres' => false, 'tact' => false, 'lat' => 49.894, 'lon' => 2.295, 'date' => '-'];
$vrai_donnee = false;

$periode = $_POST['periode'] ?? '24h';
$sql_time_filter = ">= DATE_SUB(NOW(), INTERVAL 24 HOUR)"; $sql_date_format = '%H:00'; 
switch ($periode) {
    case '1h':  $sql_time_filter = ">= DATE_SUB(NOW(), INTERVAL 1 HOUR)"; $sql_date_format = '%H:%i'; break;
    case '24h': $sql_time_filter = ">= DATE_SUB(NOW(), INTERVAL 24 HOUR)"; $sql_date_format = '%H:00'; break;
    case '7j':  $sql_time_filter = ">= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $sql_date_format = '%d/%m %H:00'; break;
    case '30j': $sql_time_filter = ">= DATE_SUB(NOW(), INTERVAL 30 DAY)"; $sql_date_format = '%d/%m'; break;
}

$labels_graph = []; $poids_graph = []; $temp_graph = []; $hum_graph = []; $lum_graph = [];

if ($ruche_active_id) {
    foreach($mes_ruches as $r) { if($r['device_id'] === $ruche_active_id) { $ruche_active = $r; break; } }
    if ($ruche_active) {
        $req = $pdo->prepare("SELECT * FROM mesures WHERE device_id = ? ORDER BY date_mesure DESC LIMIT 1");
        $req->execute([$ruche_active_id]);
        $db_data = $req->fetch();
        if ($db_data) {
            $data = ['temp' => $db_data['temperature'], 'hum' => $db_data['humidite'], 'poids' => $db_data['poids'], 'lum' => $db_data['luminosite'], 'pres' => $db_data['alerte_presence'], 'tact' => $db_data['alerte_choc'], 'lat' => $db_data['lat'], 'lon' => $db_data['lon'], 'date' => date('d/m/Y H:i:s', strtotime($db_data['date_mesure']))];
            $vrai_donnee = true;
        }

        $req_hist = $pdo->prepare("SELECT DATE_FORMAT(date_mesure, '$sql_date_format') as label_date, MIN(date_mesure) as real_date, ROUND(AVG(poids), 2) as avg_poids, ROUND(AVG(temperature), 1) as avg_temp, ROUND(AVG(humidite), 1) as avg_hum, ROUND(AVG(luminosite), 1) as avg_lum FROM mesures WHERE device_id = ? AND date_mesure $sql_time_filter GROUP BY label_date ORDER BY real_date ASC");
        $req_hist->execute([$ruche_active_id]);
        $historique = $req_hist->fetchAll();
        foreach($historique as $h) {
            $labels_graph[] = $h['label_date']; $poids_graph[] = $h['avg_poids']; $temp_graph[] = $h['avg_temp']; $hum_graph[] = $h['avg_hum']; $lum_graph[] = $h['avg_lum'];
        }
    }
}

// Seuils d'alerte pour bordures rouges
$alert_temp = ($data['temp'] < 30 || $data['temp'] > 37) && $vrai_donnee;
$alert_hum = ($data['hum'] > 80) && $vrai_donnee;

// --- GÉNÉRATION DE L'ANALYSE INTELLIGENTE DÉTAILLÉE (HOVER) ---
$txt_temp = "✅ <b>Température Idéale (34-36°C)</b><br>Le couvain se développe parfaitement. Les nourrices maintiennent cette chaleur pour garantir l'éclosion dans les temps.";
if($data['temp'] == 0) $txt_temp = "⚠️ Pas de donnée valide reçue.";
elseif($data['temp'] < 30) $txt_temp = "🚨 <b>Alerte Hypothermie (<30°C)</b><br>Risque mortel pour le couvain (maladie du couvain plâtré). La grappe peine à se chauffer. Vérifiez l'isolation.";
elseif($data['temp'] > 37) $txt_temp = "🔥 <b>Alerte Surchauffe (>37°C)</b><br>Les abeilles ventilent à l'extrême. Risque de fonte des cires et d'étouffement. Fournissez ombre et eau.";

$txt_hum = "✅ <b>Humidité Optimale (40-60%)</b><br>Hygrométrie parfaite. Les ventileuses réussissent à évaporer l'eau du nectar pour le transformer en miel.";
if($data['hum'] > 80) $txt_hum = "🚨 <b>Humidité excessive (>80%)</b><br>Air saturé. Fort risque de moisissures sur les cadres de rive et maladies fongiques. Ouvrez l'aération basse.";
elseif($data['hum'] < 30) $txt_hum = "⚠️ <b>Air trop sec (<30%)</b><br>L'éclosion nécessite de l'humidité. Les butineuses vont s'épuiser à ramener de l'eau. Installez un abreuvoir.";

$txt_poids = "⚖️ <b>Analyse du Poids</b><br>• <i>Hausse :</i> Miellée en cours.<br>• <i>Baisse lente :</i> Consommation (hiver/disette).<br>• <i>Chute brutale :</i> Essaimage probable (la reine est partie avec l'essaim).";

$txt_lum = $data['lum'] > 50 ? "☀️ <b>Forte luminosité</b><br>Le soleil déclenche l'activité de butinage. Les vols d'orientation des jeunes abeilles ont lieu." : "🌙 <b>Faible luminosité</b><br>Colonie au repos dans la grappe. Activité extérieure réduite au minimum.";
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MAYA Dashboard</title>
    <meta http-equiv="refresh" content="180">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #f4f6f8; --card-bg: #ffffff; --text-color: #31333F; --sidebar-bg: #ffffff;
            --amber: #F59E0B; --border: 1px solid rgba(245, 158, 11, 0.2); --shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        [data-theme="dark"] {
            --bg-color: #0e1117; --card-bg: #1e1e24; --text-color: #FAFAFA; --sidebar-bg: #16161a;
            --border: 1px solid rgba(245, 158, 11, 0.4); --shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        
        html, body { margin: 0; padding: 0; width: 100vw; overflow-x: hidden; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: var(--bg-color); color: var(--text-color); transition: background 0.3s; }
        h1, h2, h3, h4 { color: var(--amber); margin-top: 0; }
        
        /* 🌟 TOPBAR FIXÉE (Z-INDEX SUPERIEUR) 🌟 */
        .topbar { position: fixed; top: 0; left: 0; width: 100%; height: 60px; background: var(--card-bg); border-bottom: var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 15px; box-sizing: border-box; z-index: 2000; box-shadow: var(--shadow); }
        .menu-btn { font-size: 1.8rem; cursor: pointer; background: none; border: none; color: var(--text-color); padding: 5px; }
        
        .layout { display: flex; margin-top: 60px; min-height: calc(100vh - 60px); position: relative; width: 100%; }
        
        /* 🌟 SIDEBAR (PASSÉE EN DESSOUS DE LA TOPBAR) 🌟 */
        .sidebar { position: fixed; top: 60px; left: 0; width: 300px; height: calc(100vh - 60px); background: var(--sidebar-bg); border-right: var(--border); padding: 20px; box-sizing: border-box; overflow-y: auto; z-index: 1001; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); flex-shrink: 0; transform: translateX(0); }
        .main { flex-grow: 1; padding: 20px; box-sizing: border-box; width: 100%; max-width: 100vw; overflow-x: hidden; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); margin-left: 300px; }
        .sidebar-overlay { display: none; position: fixed; top: 60px; left: 0; width: 100vw; height: calc(100vh - 60px); background: rgba(0,0,0,0.5); z-index: 1000; opacity: 0; transition: opacity 0.3s; }
        
        @media (min-width: 769px) { 
            .sidebar.collapsed { transform: translateX(-300px); } 
            .main.expanded { margin-left: 0; } 
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); box-shadow: 2px 0 15px rgba(0,0,0,0.5); }
            .main { margin-left: 0; }
            .sidebar-overlay.active { display: block; opacity: 1; }
            .user-name { display: none; }
            .st-tabs { flex-wrap: wrap; gap: 5px; }
            .st-tab { flex-grow: 1; text-align: center; padding: 8px 5px; font-size: 0.9rem; }
        }

        .st-input { width: 100%; padding: 10px; margin: 5px 0 15px 0; border-radius: 6px; border: 1px solid #ccc; background: var(--bg-color); color: var(--text-color); box-sizing: border-box;}
        .st-button { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--amber); background: transparent; color: var(--text-color); font-weight: bold; cursor: pointer; transition: 0.2s; box-sizing: border-box; }
        .st-button:hover { background: var(--amber); color: white; transform: scale(1.02); }
        
        /* 🌟 ANIMATIONS CSS 🌟 */
        @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        
        /* 🌟 CARTES ET HOVER OVERLAY EXPERT 🌟 */
        .metric-card { 
            background: var(--card-bg); border: var(--border); border-radius: 12px; padding: 20px; 
            box-shadow: var(--shadow); position: relative; overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 0; animation: fadeSlideUp 0.6s forwards; 
        }
        .metric-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 12px 24px rgba(245, 158, 11, 0.25); border-color: var(--amber); z-index: 10; }
        
        .metric-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--card-bg); color: var(--text-color);
            padding: 15px; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; font-size: 0.95rem; line-height: 1.4;
            opacity: 0; visibility: hidden; transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: translateY(100%); z-index: 10;
        }
        .metric-card:hover .metric-overlay { opacity: 0.98; visibility: visible; transform: translateY(0); }

        .metric-card:nth-child(1) { animation-delay: 0.1s; }
        .metric-card:nth-child(2) { animation-delay: 0.2s; }
        .metric-card:nth-child(3) { animation-delay: 0.3s; }
        .metric-card:nth-child(4) { animation-delay: 0.4s; }

        .metric-value { font-size: 2.2rem; font-weight: 900; color: var(--amber); margin: 5px 0; transition: 0.3s; }
        .metric-card:hover .metric-value { opacity: 0; transform: scale(0.8); } 
        
        .alert-danger { border: 2px solid #ef4444; background: rgba(239, 68, 68, 0.05); }
        .alert-warning { border: 2px solid #f59e0b; background: rgba(245, 158, 11, 0.05); }

        .st-tabs { display: flex; border-bottom: 2px solid rgba(245, 158, 11, 0.2); margin-bottom: 20px; gap: 10px; }
        .st-tab { padding: 10px; cursor: pointer; color: var(--text-color); font-weight: bold; opacity: 0.6; border-bottom: 3px solid transparent; transition: 0.2s; }
        .st-tab:hover { opacity: 1; }
        .st-tab.active { color: var(--amber); border-bottom-color: var(--amber); opacity: 1; }
        .tab-content { display: none; animation: fadeIn 0.4s ease-in-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .chart-container { background: var(--card-bg); border: var(--border); border-radius: 12px; padding: 20px; box-shadow: var(--shadow); margin-bottom: 20px; width: 100%; box-sizing: border-box; overflow: hidden; position: relative; opacity: 0; animation: fadeSlideUp 0.6s forwards; animation-delay: 0.5s;}

        #toast { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 5000; right: 30px; bottom: 30px; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: opacity 0.3s, bottom 0.3s; opacity: 0; }
        #toast.show { visibility: visible; opacity: 1; bottom: 50px; }
        #toast.success { background-color: #10b981; }
        #toast.error { background-color: #ef4444; }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-btn" onclick="toggleSidebar()">☰</button>
            <h2 style="margin: 0; display: flex; align-items: center; gap: 5px;">🍯 MAYA</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span class="user-name" style="font-weight: bold; opacity: 0.8;">👤 <?= htmlspecialchars($_SESSION['nom'] ?? 'Apiculteur') ?></span>
            <button onclick="toggleTheme()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;" title="Mode Sombre/Clair">🌓</button>
            <form method="POST" style="margin:0;"><button type="submit" name="logout" style="background:#ef4444; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; font-weight:bold;">Déco.</button></form>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="layout">
        <div class="sidebar" id="sidebar">
            <form method="POST">
                <h3 style="margin-top:0;">🐝 Rucher</h3>
                <select name="ruche_id" class="st-input" onchange="this.form.submit()">
                    <?php if(!$mes_ruches) echo "<option>Aucune ruche</option>"; ?>
                    <?php foreach($mes_ruches as $r): ?>
                        <option value="<?= htmlspecialchars($r['device_id']) ?>" <?= ($ruche_active_id == $r['device_id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['nom_affichage']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="periode" value="<?= htmlspecialchars($periode) ?>">
            </form>

            <details style="background: var(--card-bg); border: var(--border); border-radius: 8px; margin-bottom: 15px;">
                <summary style="padding: 15px; cursor: pointer; font-weight: bold; color: var(--amber); outline: none; list-style: none;">➕ Associer une ruche</summary>
                <div style="padding: 15px; border-top: 1px solid rgba(245,158,11,0.1);">
                    <form method="POST">
                        <label style="font-size:0.9rem;">Device ID TTN</label>
                        <input type="text" name="new_device_id" class="st-input" placeholder="ex: projet-iot" required>
                        <label style="font-size:0.9rem;">Nom d'affichage</label>
                        <input type="text" name="new_nom_ruche" class="st-input" placeholder="ex: Ruche 1" required>
                        <button type="submit" name="ajout_ruche" class="st-button">Ajouter</button>
                    </form>
                </div>
            </details>

            <?php if($ruche_active): ?>
                <hr style="border-color: rgba(245, 158, 11, 0.2); margin: 20px 0;">
                <h3>📡 Accès TTN</h3>
                <form method="POST">
                    <input type="hidden" name="ruche_id" value="<?= htmlspecialchars($ruche_active_id) ?>">
                    <input type="text" name="ttn_app_id" class="st-input" placeholder="App ID TTN" value="<?= htmlspecialchars($ruche_active['ttn_app_id'] ?? '') ?>">
                    <input type="password" name="ttn_api_key" class="st-input" placeholder="API Key TTN" value="<?= htmlspecialchars($ruche_active['ttn_api_key'] ?? '') ?>">
                    <button type="submit" name="connecter_reseau" class="st-button">🔄 Enregistrer</button>
                </form>
                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="ruche_id" value="<?= htmlspecialchars($ruche_active_id) ?>">
                    <button type="submit" name="supprimer_ruche" class="st-button" style="border-color: #ef4444; color: #ef4444; background: transparent;" onclick="return confirm('Supprimer définitivement cette ruche ?');">🗑️ Supprimer la ruche</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="main" id="main-content">
            <?php if(!$ruche_active): ?>
                <h1 style="animation: slideInRight 0.5s;">Bienvenue ! 👋</h1>
                <p style="animation: slideInRight 0.6s;">Ouvrez le menu (☰) et ajoutez votre première ruche.</p>
            <?php else: ?>
                <h1 style="margin-bottom: 5px; font-size: 2rem; animation: slideInRight 0.4s;"><?= htmlspecialchars($ruche_active['nom_affichage']) ?></h1>
                <p style="opacity: 0.7; margin-top: 0; font-size: 0.95rem; animation: slideInRight 0.5s;">ID: <?= htmlspecialchars($ruche_active['device_id']) ?> | MàJ : <b><?= $data['date'] ?></b></p>

                <?php if(!$vrai_donnee): ?>
                    <div style="background: rgba(245, 158, 11, 0.1); color: #d97706; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; animation: fadeSlideUp 0.6s;">⚠️ En attente du premier message TTN...</div>
                <?php endif; ?>

                <div class="st-tabs" style="animation: slideInRight 0.6s;">
                    <div class="st-tab active" onclick="switchTab('dashboard', this)">📊 Données</div>
                    <div class="st-tab" onclick="switchTab('meteo', this)">⛅ Météo</div>
                    <div class="st-tab" onclick="switchTab('actions', this)">⚙️ Actions</div>
                </div>

                <div id="dashboard" class="tab-content active">
                    <div class="metrics-grid">
                        <div class="metric-card <?= $alert_temp ? 'alert-danger' : '' ?>">
                            <div style="opacity:0.8; font-size:0.95rem;">🌡️ Temp. Interne</div>
                            <div class="metric-value" <?= $alert_temp ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($data['temp']) ?> °C</div>
                            <div class="metric-overlay"><?= $txt_temp ?></div>
                        </div>
                        <div class="metric-card <?= $alert_hum ? 'alert-warning' : '' ?>">
                            <div style="opacity:0.8; font-size:0.95rem;">💧 Humidité</div>
                            <div class="metric-value" <?= $alert_hum ? 'style="color:#f59e0b;"' : '' ?>><?= htmlspecialchars($data['hum']) ?> %</div>
                            <div class="metric-overlay"><?= $txt_hum ?></div>
                        </div>
                        <div class="metric-card">
                            <div style="opacity:0.8; font-size:0.95rem;">⚖️ Poids Brut</div>
                            <div class="metric-value"><?= htmlspecialchars($data['poids']) ?> kg</div>
                            <div class="metric-overlay"><?= $txt_poids ?></div>
                        </div>
                        <div class="metric-card">
                            <div style="opacity:0.8; font-size:0.95rem;">☀️ Luminosité</div>
                            <div class="metric-value"><?= htmlspecialchars($data['lum']) ?> %</div>
                            <div class="metric-overlay"><?= $txt_lum ?></div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <form method="POST" id="periodForm" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <input type="hidden" name="ruche_id" value="<?= htmlspecialchars($ruche_active_id) ?>">
                                <label style="font-weight: bold;">📅 Période :</label>
                                <select name="periode" class="st-input" style="width: auto; margin: 0; padding: 6px 12px;" onchange="this.form.submit()">
                                    <option value="1h" <?= $periode == '1h' ? 'selected' : '' ?>>1 Heure</option>
                                    <option value="24h" <?= $periode == '24h' ? 'selected' : '' ?>>24 Heures</option>
                                    <option value="7j" <?= $periode == '7j' ? 'selected' : '' ?>>7 Jours</option>
                                    <option value="30j" <?= $periode == '30j' ? 'selected' : '' ?>>30 Jours</option>
                                </select>
                            </form>
                            <button type="button" class="st-button" style="border-color: #10b981; color: #10b981; width: auto; padding: 6px 15px; margin:0;" onclick="exportCSV()">📥 Exporter CSV</button>
                        </div>
                        <h3 style="font-size: 1.2rem; margin-top:10px;">📈 Graphiques d'évolution</h3>
                        <div style="position: relative; height: 250px; width: 100%; margin-bottom: 20px;">
                            <canvas id="weightChart"></canvas>
                        </div>
                        <div style="position: relative; height: 200px; width: 100%;">
                            <canvas id="climateChart"></canvas>
                        </div>
                    </div>
                </div>

                <div id="meteo" class="tab-content">
                    <div class="chart-container" style="animation-delay: 0.1s;">
                        <h3 style="font-size: 1.2rem;">⛅ Prévisions Météo (Lieu de la ruche)</h3>
                        <div id="meteo-api-content" class="metrics-grid">Chargement...</div>
                    </div>
                </div>

                <div id="actions" class="tab-content">
                    <div class="chart-container" style="animation-delay: 0.1s;">
                        <h3 style="font-size: 1.2rem;">🎛️ Centre de Contrôle Actionneurs</h3>
                        
                        <div class="metrics-grid" style="margin-top:20px;">
                            <div class="metric-card" style="box-shadow: none; border: 2px solid rgba(59,130,246,0.3); pointer-events: none; animation: none; transform: none;">
                                <h4 style="font-size: 1.1rem; color: #3b82f6;">🚪 Trappe (Moteur)</h4>
                                
                                <div style="background: rgba(59,130,246,0.1); border-radius: 8px; padding: 10px; margin-bottom: 15px; text-align: center;">
                                    <span style="font-size: 0.9rem; opacity: 0.8;">État actuel estimé</span><br>
                                    <span id="door-status" style="font-weight: bold; font-size: 1.2rem; color: #3b82f6;">Fermée 🔴</span>
                                </div>

                                <form method="POST" onsubmit="simulateDoorOpening()" style="pointer-events: auto;">
                                    <input type="hidden" name="ruche_id" value="<?= htmlspecialchars($ruche_active_id) ?>">
                                    <input type="hidden" name="saved_app_id" value="<?= htmlspecialchars($ruche_active['ttn_app_id'] ?? '') ?>">
                                    <input type="hidden" name="saved_api_key" value="<?= htmlspecialchars($ruche_active['ttn_api_key'] ?? '') ?>">
                                    <label style="font-size:0.95rem;"><input type="checkbox" required> Confirmer l'action</label><br><br>
                                    <button type="submit" name="action_moteur" class="st-button" style="border-color: #3b82f6; color: #3b82f6; background: transparent;">Envoyer l'ordre d'ouverture</button>
                                </form>
                            </div>

                            <div class="metric-card" style="box-shadow: none; border: 2px solid rgba(239,68,68,0.3); pointer-events: none; animation: none; transform: none;">
                                <h4 style="font-size: 1.1rem; color: #ef4444;">🚨 Alarme (Buzzer)</h4>
                                <form method="POST" style="pointer-events: auto; margin-top: 15px;">
                                    <input type="hidden" name="ruche_id" value="<?= htmlspecialchars($ruche_active_id) ?>">
                                    <input type="hidden" name="saved_app_id" value="<?= htmlspecialchars($ruche_active['ttn_app_id'] ?? '') ?>">
                                    <input type="hidden" name="saved_api_key" value="<?= htmlspecialchars($ruche_active['ttn_api_key'] ?? '') ?>">
                                    <label style="font-size:0.95rem;"><input type="checkbox" required> Déverrouiller</label><br><br>
                                    <button type="submit" name="action_buzzer" class="st-button" style="background: #ef4444; color: white; border: none;">📢 Déclencher</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="toast">Message</div>

    <script>
        // Logique de Sidebar Responsive
        function toggleSidebar() { 
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main-content');
            const overlay = document.getElementById('sidebar-overlay');
            
            if(window.innerWidth > 768) {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            if(typeof updateChartColor === "function") updateChartColor();
        }
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');

        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.st-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            el.classList.add('active');
        }

        function showToast(msg, type = 'info') {
            const toast = document.getElementById("toast");
            toast.innerText = msg; toast.className = "show " + type;
            setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
        }
        <?php if($toast_msg != ""): ?> showToast("<?= htmlspecialchars($toast_msg, ENT_QUOTES) ?>", "<?= $toast_type ?>"); <?php endif; ?>

        // SIMULATION VISUELLE DE L'ÉTAT DE LA PORTE
        function simulateDoorOpening() {
            const status = document.getElementById('door-status');
            status.innerHTML = "En cours... ⏳";
            status.style.color = "var(--text-color)";
            setTimeout(() => {
                status.innerHTML = "Ouverte 🟢";
                status.style.color = "#10b981";
                localStorage.setItem('door_state', 'open');
            }, 3000);
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const status = document.getElementById('door-status');
            if(status && localStorage.getItem('door_state') === 'open') {
                status.innerHTML = "Ouverte 🟢";
                status.style.color = "#10b981";
            }
        });

        function exportCSV() {
            const labels = <?= json_encode($labels_graph) ?>;
            const poids = <?= json_encode($poids_graph) ?>;
            const temp = <?= json_encode($temp_graph) ?>;
            const hum = <?= json_encode($hum_graph) ?>;
            
            if (labels.length === 0) { showToast("Aucune donnée à exporter.", "error"); return; }
            let csvContent = "data:text/csv;charset=utf-8,Date,Poids(kg),Temperature(C),Humidite(%)\n";
            for(let i=0; i<labels.length; i++) csvContent += `${labels[i]},${poids[i]},${temp[i]},${hum[i]}\n`;
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "historique_ruche.csv");
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
            showToast("Export CSV réussi !", "success");
        }

        <?php if($ruche_active && count($labels_graph) > 0): ?>
            let charts = {};
            function initCharts() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const textColor = isDark ? '#FAFAFA' : '#31333F';
                const gridColor = isDark ? '#333' : '#e0e0e0';
                const opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } }, scales: { x: { ticks: { color: textColor }, grid: { display: false } } } };

                charts.weight = new Chart(document.getElementById('weightChart').getContext('2d'), {
                    type: 'line', data: { labels: <?= json_encode($labels_graph) ?>, datasets: [{ label: 'Poids (kg)', data: <?= json_encode($poids_graph) ?>, borderColor: '#F59E0B', backgroundColor: 'rgba(245, 158, 11, 0.2)', borderWidth: 3, tension: 0.4, fill: true, pointRadius: 2 }] },
                    options: { ...opts, scales: { ...opts.scales, y: { ticks: { color: textColor }, grid: { color: gridColor } } } }
                });

                charts.climate = new Chart(document.getElementById('climateChart').getContext('2d'), {
                    type: 'line', data: { labels: <?= json_encode($labels_graph) ?>, datasets: [ { label: 'Temp. (°C)', data: <?= json_encode($temp_graph) ?>, borderColor: '#ef4444', backgroundColor: '#ef4444', borderWidth: 2, tension: 0.4, yAxisID: 'y' }, { label: 'Hum. (%)', data: <?= json_encode($hum_graph) ?>, borderColor: '#3b82f6', backgroundColor: '#3b82f6', borderWidth: 2, tension: 0.4, yAxisID: 'y1' } ] },
                    options: { ...opts, scales: { x: opts.scales.x, y: { type: 'linear', display: true, position: 'left', ticks: { color: textColor }, grid: { color: gridColor } }, y1: { type: 'linear', display: true, position: 'right', ticks: { color: textColor }, grid: { drawOnChartArea: false } } } }
                });
            }
            
            function updateChartColor() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const textColor = isDark ? '#FAFAFA' : '#31333F';
                const gridColor = isDark ? '#333' : '#e0e0e0';
                if(Object.keys(charts).length > 0) {
                    Object.values(charts).forEach(c => {
                        c.options.scales.x.ticks.color = textColor;
                        if(c.options.scales.y) { c.options.scales.y.ticks.color = textColor; c.options.scales.y.grid.color = gridColor; }
                        if(c.options.scales.y1) c.options.scales.y1.ticks.color = textColor;
                        c.options.plugins.legend.labels.color = textColor; c.update();
                    });
                }
            }
            initCharts();
        <?php endif; ?>

        <?php if($ruche_active): ?>
            fetch(`https://api.open-meteo.com/v1/forecast?latitude=<?= $data['lat'] ?>&longitude=<?= $data['lon'] ?>&daily=weathercode,temperature_2m_max,precipitation_sum&timezone=auto`)
            .then(res => res.json())
            .then(data => {
                const codes = {0:'☀️',1:'☀️',2:'☁️',3:'☁️',45:'🌫️',48:'🌫️',51:'🌦️',53:'🌦️',55:'🌦️',61:'🌧️',63:'🌧️',65:'🌧️',71:'❄️',73:'❄️',75:'❄️',80:'🌦️',81:'🌦️',82:'🌦️',95:'⛈️'};
                let html = '';
                for(let i=0; i<7; i++) {
                    let date = new Date(data.daily.time[i]).toLocaleDateString('fr-FR', {weekday: 'short'});
                    let t_max = data.daily.temperature_2m_max[i]; let rain = data.daily.precipitation_sum[i]; let code = data.daily.weathercode[i];
                    let emoji = codes[code] || '⛅';
                    let pastille = (t_max < 12 || rain > 1.0 || [71, 73, 75, 95].includes(code)) ? '<span style="background:#FECACA; color:#7F1D1D; padding:4px 10px; border-radius:15px; font-size:0.75rem; font-weight:bold;">🛑 Vol Limité</span>' : '<span style="background:#BBF7D0; color:#14532D; padding:4px 10px; border-radius:15px; font-size:0.75rem; font-weight:bold;">✅ Vol Actif</span>';
                    html += `<div class="metric-card" style="text-align:center;"><h4 style="margin:0; text-transform:capitalize;">${i===0 ? "Auj." : date}</h4><div style="font-size:2.5rem; margin:10px 0;">${emoji}</div><div style="font-weight:bold; font-size:1.2rem;">${t_max}°C</div><div style="color:#3B82F6; font-size:0.9rem; margin-bottom: 10px;">💧 ${rain} mm</div>${pastille}</div>`;
                }
                document.getElementById('meteo-api-content').innerHTML = html;
            });
        <?php endif; ?>
    </script>
</body>
</html>
