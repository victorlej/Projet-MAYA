<!-- ===== Modal d'analyse ===== -->
<div id="analysisModal" class="modal" onclick="if(event.target===this) closeModal()">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal()">×</button>
        <h2 id="modalTitle">🤖 Diagnostic</h2>
        <div id="modalBody" class="modal-body">…</div>
    </div>
</div>

<!-- ===== Toast ===== -->
<div id="toast">Message</div>

<!-- ===== Données injectées par PHP pour les scripts statiques ===== -->
<script>
window.MAYA = {
    refreshMs:  <?= REFRESH_MS ?>,
    hasRuche:   <?= $ruche_active ? 'true' : 'false' ?>,
    lat:        <?= json_encode($data['lat']) ?>,
    lon:        <?= json_encode($data['lon']) ?>,
    labels:     <?= json_encode($labels_graph) ?>,
    poids:      <?= json_encode($poids_graph) ?>,
    temp:       <?= json_encode($temp_graph) ?>,
    hum:        <?= json_encode($hum_graph) ?>,
    lum:        <?= json_encode($lum_graph) ?>,
    toast:      <?= $toast_msg ? json_encode(['msg' => $toast_msg, 'type' => $toast_type]) : 'null' ?>
};
</script>

<!-- ===== Scripts statiques ===== -->
<script src="assets/js/ui.js"></script>
<script src="assets/js/charts.js"></script>
<script src="assets/js/meteo.js"></script>
<script src="assets/js/bees.js"></script>

</body>
</html>
