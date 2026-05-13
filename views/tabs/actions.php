<?php $door = $_SESSION['door_state_' . $ruche_active_id] ?? 'closed'; ?>

<section id="actions" class="tab-panel">
    <div class="control-grid">

        <div class="control-card door">
            <h4>🚪 Trappe motorisée</h4>
            <p class="desc">Ouvrir ou fermer l'entrée de la ruche à distance.</p>
            <div class="door-status">
                <div class="label">Dernier ordre envoyé</div>
                <div class="value <?= $door === 'open' ? 'open' : 'closed' ?>">
                    <?= $door === 'open' ? 'Ouverte 🟢' : 'Fermée 🔴' ?>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="ruche_id"      value="<?= e($ruche_active_id) ?>">
                <input type="hidden" name="saved_app_id"  value="<?= e($ruche_active['ttn_app_id']  ?? '') ?>">
                <input type="hidden" name="saved_api_key" value="<?= e($ruche_active['ttn_api_key'] ?? '') ?>">
                <label class="checkbox-row"><input type="checkbox" required> Je confirme l'action</label>
                <div class="btn-pair">
                    <button type="submit" name="action_moteur_ouvrir" class="btn btn-success">🚪 Ouvrir</button>
                    <button type="submit" name="action_moteur_fermer" class="btn btn-danger">🚪 Fermer</button>
                </div>
            </form>
        </div>

        <div class="control-card alarm">
            <h4>🚨 Alarme sonore</h4>
            <p class="desc">Déclencher le buzzer pour signaler ou dissuader.</p>
            <form method="POST">
                <input type="hidden" name="ruche_id"      value="<?= e($ruche_active_id) ?>">
                <input type="hidden" name="saved_app_id"  value="<?= e($ruche_active['ttn_app_id']  ?? '') ?>">
                <input type="hidden" name="saved_api_key" value="<?= e($ruche_active['ttn_api_key'] ?? '') ?>">
                <label class="checkbox-row"><input type="checkbox" required> Je déverrouille l'envoi</label>
                <button type="submit" name="action_buzzer" class="btn" style="background:var(--red);">
                    📢 Déclencher l'alarme
                </button>
            </form>
        </div>

    </div>
</section>
