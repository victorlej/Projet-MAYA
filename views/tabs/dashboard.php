<section id="dashboard" class="tab-panel active">

    <div class="grid-metrics">
        <div class="card metric clickable <?= $alert_temp ? 'alert-temp' : '' ?> delay-1"
             onclick="openAnalysis('temp', <?= (float)$data['temp'] ?>)">
            <div class="metric-head"><span>Température</span><span class="metric-icon">🌡️</span></div>
            <div class="metric-value"><?= e((string)$data['temp']) ?><span class="metric-unit">°C</span></div>
            <div class="metric-trend"><?= $alert_temp ? '⚠️ Hors plage idéale' : '✓ Plage normale' ?></div>
        </div>

        <div class="card metric clickable <?= $alert_hum ? 'alert-hum' : '' ?> delay-2"
             onclick="openAnalysis('hum', <?= (float)$data['hum'] ?>)">
            <div class="metric-head"><span>Humidité</span><span class="metric-icon">💧</span></div>
            <div class="metric-value"><?= e((string)$data['hum']) ?><span class="metric-unit">%</span></div>
            <div class="metric-trend"><?= $alert_hum ? '⚠️ Saturation' : '✓ Hygrométrie OK' ?></div>
        </div>

        <div class="card metric clickable delay-3"
             onclick="openAnalysis('poids', <?= (float)$data['poids'] ?>)">
            <div class="metric-head"><span>Poids</span><span class="metric-icon">⚖️</span></div>
            <div class="metric-value"><?= e((string)$data['poids']) ?><span class="metric-unit">kg</span></div>
            <div class="metric-trend">Suivi pondéral</div>
        </div>

        <div class="card metric clickable delay-4"
             onclick="openAnalysis('lum', <?= (float)$data['lum'] ?>)">
            <div class="metric-head"><span>Luminosité</span><span class="metric-icon">☀️</span></div>
            <div class="metric-value"><?= e((string)$data['lum']) ?><span class="metric-unit">%</span></div>
            <div class="metric-trend"><?= $data['lum'] > 50 ? 'Activité diurne' : 'Repos / nuit' ?></div>
        </div>
    </div>

    <div class="filters-bar">
        <form method="POST" style="margin:0; display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="ruche_id" value="<?= e($ruche_active_id) ?>">
            <span class="lbl">📅 Période</span>
            <select name="periode" class="periode-select" onchange="this.form.submit()">
                <option value="1h"  <?= $periode == '1h'  ? 'selected' : '' ?>>1 heure</option>
                <option value="24h" <?= $periode == '24h' ? 'selected' : '' ?>>24 heures</option>
                <option value="7j"  <?= $periode == '7j'  ? 'selected' : '' ?>>7 jours</option>
                <option value="30j" <?= $periode == '30j' ? 'selected' : '' ?>>30 jours</option>
            </select>
        </form>

        <div class="filters-group">
            <span class="lbl">👁️ Afficher</span>
            <span class="chip active" id="chip-poids"  onclick="toggleChart('poids')">⚖️ Poids</span>
            <span class="chip active" id="chip-climat" onclick="toggleChart('climat')">🌡️ Climat</span>
            <span class="chip active" id="chip-lum"    onclick="toggleChart('lum')">☀️ Lumière</span>
        </div>

        <button type="button" class="btn btn-success" style="width:auto; padding:8px 16px;" onclick="exportCSV()">
            📥 Exporter CSV
        </button>
    </div>

    <div id="wrapper-poids" class="chart-card delay-2">
        <h3>📈 Évolution du poids</h3>
        <div style="position:relative; height:300px;"><canvas id="weightChart"></canvas></div>
    </div>

    <div id="wrapper-climat" class="chart-card delay-3">
        <h3>🌡️ Température & humidité</h3>
        <div style="position:relative; height:260px;"><canvas id="climateChart"></canvas></div>
    </div>

    <div id="wrapper-lum" class="chart-card delay-4">
        <h3>☀️ Cycle de luminosité</h3>
        <div style="position:relative; height:220px;"><canvas id="lumChart"></canvas></div>
    </div>

</section>
