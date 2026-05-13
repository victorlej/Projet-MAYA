<aside class="sidebar" id="sidebar">

    <div class="side-block">
        <div class="side-label">Mon rucher</div>
        <form method="POST">
            <select name="ruche_id" class="select" onchange="this.form.submit()">
                <?php if (!$mes_ruches): ?>
                    <option>Aucune ruche</option>
                <?php endif; ?>
                <?php foreach ($mes_ruches as $r): ?>
                    <option value="<?= e($r['device_id']) ?>" <?= $ruche_active_id == $r['device_id'] ? 'selected' : '' ?>>
                        <?= e($r['nom_affichage']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="periode" value="<?= e($periode) ?>">
        </form>
    </div>

    <details class="collapsible">
        <summary>Associer une ruche</summary>
        <div class="details-body">
            <form method="POST">
                <input type="text"     name="new_device_id"    class="input" placeholder="Device ID TTN"      required style="margin-bottom:8px;">
                <input type="text"     name="new_nom_ruche"    class="input" placeholder="Nom d'affichage"    required style="margin-bottom:8px;">
                <input type="text"     name="new_ttn_app_id"   class="input" placeholder="App ID TTN (opt.)"           style="margin-bottom:8px;">
                <input type="password" name="new_ttn_api_key"  class="input" placeholder="API Key TTN (opt.)"          style="margin-bottom:12px;">
                <button type="submit" name="ajout_ruche" class="btn">+ Ajouter</button>
            </form>
        </div>
    </details>

    <?php if ($ruche_active): ?>
        <div class="divider"></div>

        <div class="side-block">
            <div class="side-label">Accès TTN</div>
            <form method="POST">
                <input type="hidden" name="ruche_id" value="<?= e($ruche_active_id) ?>">
                <input type="text"     name="ttn_app_id"  class="input" placeholder="App ID TTN"  value="<?= e($ruche_active['ttn_app_id']  ?? '') ?>" style="margin-bottom:8px;">
                <input type="password" name="ttn_api_key" class="input" placeholder="API Key TTN" value="<?= e($ruche_active['ttn_api_key'] ?? '') ?>" style="margin-bottom:12px;">
                <button type="submit" name="connecter_reseau" class="btn">Enregistrer</button>
            </form>
        </div>

        <div class="side-block">
            <form method="POST">
                <input type="hidden" name="ruche_id" value="<?= e($ruche_active_id) ?>">
                <button type="submit" name="supprimer_ruche" class="btn btn-danger"
                        onclick="return confirm('Supprimer définitivement cette ruche ?');">
                    Supprimer la ruche
                </button>
            </form>
        </div>
    <?php endif; ?>

</aside>
