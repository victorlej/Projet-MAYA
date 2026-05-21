<section id="meteo" class="tab-panel">
    <div class="chart-card anim-fade">
        <h3>⛅ Prévisions sur 7 jours</h3>
        <div id="meteo-content" class="weather-grid">
            <div style="grid-column:1/-1; text-align:center; color:var(--text-soft); padding:30px;">
                Chargement…
            </div>
        </div>
    </div>

    <?php if ($ruche_active): ?>
    <div class="chart-card anim-fade delay-2">
        <h3>📍 Position de la ruche</h3>
        <div id="hive-map"></div>
    </div>
    <?php endif; ?>
</section>
