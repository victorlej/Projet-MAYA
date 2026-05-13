<section class="hero anim-fade">
    <div>
        <h1 class="hero-title"><?= e($ruche_active['nom_affichage']) ?></h1>
        <p class="hero-meta">
            ID : <b><?= e($ruche_active['device_id']) ?></b> ·
            MàJ : <b><?= e($data['date']) ?></b><br>
            <span class="pin">📍 <span id="city-name">Localisation en cours…</span></span>
            <span style="opacity:.7;">(<?= e((string)$data['lat']) ?>, <?= e((string)$data['lon']) ?>)</span>
        </p>
    </div>
    <form method="POST" style="margin:0;">
        <input type="hidden" name="ruche_id" value="<?= e($ruche_active_id) ?>">
        <button type="submit" name="rafraichir" class="icon-btn spin-on-hover" title="Rafraîchir">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/>
                <polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
        </button>
    </form>
</section>

<?php if (!$vrai_donnee): ?>
    <div class="alert-banner anim-fade">⏳ En attente du premier message TTN pour cette ruche…</div>
<?php endif; ?>
