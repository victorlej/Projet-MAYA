<section id="dashboard" class="tab-panel active">

    <div class="grid-metrics">
        <?php
        $metrics = [
            ['id'=>'temp',  'label'=>'Température', 'icon'=>'🌡️', 'val'=>$data['temp'],  'unit'=>'°C',  'class'=>$alert_temp?'alert-temp':'', 'trend'=>$alert_temp?'⚠️ Hors plage idéale':'✓ Plage normale',   'pct'=>min(100,max(0,round($data['temp']/50*100))),  'delay'=>1],
            ['id'=>'hum',   'label'=>'Humidité',    'icon'=>'💧', 'val'=>$data['hum'],   'unit'=>'%',   'class'=>$alert_hum?'alert-hum':'',   'trend'=>$alert_hum?'⚠️ Saturation':'✓ Hygrométrie OK',           'pct'=>(int)$data['hum'],                           'delay'=>2],
            ['id'=>'poids', 'label'=>'Poids',        'icon'=>'⚖️', 'val'=>$data['poids'], 'unit'=>'kg',  'class'=>'',                          'trend'=>'Suivi pondéral',                                         'pct'=>min(100,max(0,round($data['poids']/80*100))), 'delay'=>3],
            ['id'=>'lum',   'label'=>'Luminosité',   'icon'=>'☀️', 'val'=>$data['lum'],   'unit'=>'%',   'class'=>'',                          'trend'=>$data['lum']>50?'Activité diurne':'Repos / nuit',         'pct'=>(int)$data['lum'],                           'delay'=>4],
        ];
        foreach ($metrics as $m): ?>
        <div class="card metric clickable <?= $m['class'] ?> delay-<?= $m['delay'] ?>"
             onclick="openAnalysis('<?= $m['id'] ?>', <?= (float)$m['val'] ?>)">
            <div class="metric-head">
                <span><?= $m['label'] ?></span>
                <span class="metric-icon"><?= $m['icon'] ?></span>
            </div>
            <div class="metric-value" data-val="<?= (float)$m['val'] ?>" data-dec="<?= strlen(strstr((string)$m['val'], '.')) > 1 ? strlen(strstr((string)$m['val'], '.')) - 1 : 0 ?>">
                <span class="metric-num">0</span><span class="metric-unit"><?= $m['unit'] ?></span>
            </div>
            <div class="metric-trend"><?= $m['trend'] ?></div>
            <svg class="gauge-svg" viewBox="0 0 120 65" aria-hidden="true">
                <path class="gauge-track" d="M 10 60 A 50 50 0 0 1 110 60" stroke-width="8" fill="none" stroke-linecap="round"/>
                <path class="gauge-arc"   d="M 10 60 A 50 50 0 0 1 110 60" stroke-width="8" fill="none" stroke-linecap="round"
                      stroke-dasharray="157 157" stroke-dashoffset="157" data-pct="<?= $m['pct'] ?>"/>
                <circle class="gauge-dot" cx="60" cy="60" r="3.5" fill="currentColor"/>
            </svg>
        </div>
        <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/../partials/conseil.php'; ?>

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
