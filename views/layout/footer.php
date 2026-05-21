<!-- ===== Chatbot MAYA ===== -->
<button class="chat-btn" id="chat-btn" title="Parler à MAYA IA">🐝</button>

<div class="chat-panel" id="chat-panel">
    <div class="chat-header">
        <div class="chat-header-avatar">🐝</div>
        <div class="chat-header-text">
            <h4>MAYA · Assistante apicole</h4>
            <p>Posez-moi une question sur votre ruche</p>
        </div>
        <button class="chat-close" id="chat-close" title="Fermer">×</button>
    </div>

    <div class="chat-messages" id="chat-messages"></div>

    <div class="chat-suggestions" id="chat-suggestions"></div>

    <div class="chat-footer">
        <textarea class="chat-input" id="chat-input" rows="1"
                  placeholder="Ex : Ma ruche est-elle en bonne santé ?"></textarea>
        <button class="chat-send" id="chat-send" title="Envoyer">➤</button>
    </div>
</div>

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
<script src="assets/js/ui.js?v=<?= filemtime(__DIR__ . '/../../assets/js/ui.js') ?>"></script>
<script src="assets/js/charts.js?v=<?= filemtime(__DIR__ . '/../../assets/js/charts.js') ?>"></script>
<script src="assets/js/meteo.js?v=<?= filemtime(__DIR__ . '/../../assets/js/meteo.js') ?>"></script>
<script src="assets/js/bees.js?v=<?= filemtime(__DIR__ . '/../../assets/js/bees.js') ?>"></script>

<!-- ===== Chatbot JS (règles locales) ===== -->
<script>
(function () {
    const btn      = document.getElementById('chat-btn');
    const panel    = document.getElementById('chat-panel');
    const closeBtn = document.getElementById('chat-close');
    const input    = document.getElementById('chat-input');
    const sendBtn  = document.getElementById('chat-send');
    const msgBox   = document.getElementById('chat-messages');
    let open = false;

    // --- Données capteurs ---
    function getData() {
        const d = window.MAYA || {};
        return {
            hasRuche: !!d.hasRuche,
            temp:  Array.isArray(d.temp)  && d.temp.length  ? d.temp[d.temp.length - 1]   : null,
            hum:   Array.isArray(d.hum)   && d.hum.length   ? d.hum[d.hum.length - 1]     : null,
            poids: Array.isArray(d.poids) && d.poids.length ? d.poids[d.poids.length - 1] : null,
            lum:   Array.isArray(d.lum)   && d.lum.length   ? d.lum[d.lum.length - 1]     : null,
        };
    }

    // --- Moteur de règles ---
    function reply(msg) {
        const s = msg.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        const d = getData();

        // Température
        if (/temp|chaud|froid|degre|°/.test(s)) {
            if (d.temp === null) return '🌡️ Aucune donnée de température disponible pour l\'instant.';
            if (d.temp < 30) return `🥶 Température basse : ${d.temp} °C. La ruche est peut-être en grappes hivernales ou manque de chaleur.`;
            if (d.temp > 37) return `🔥 Température élevée : ${d.temp} °C. Risque de surchauffe — vérifiez la ventilation et l'ombrage.`;
            return `🌡️ Température actuelle : ${d.temp} °C. C'est dans la plage normale (30–37 °C). 👍`;
        }

        // Humidité
        if (/humid|eau|mouill|hygro/.test(s)) {
            if (d.hum === null) return '💧 Aucune donnée d\'humidité disponible.';
            if (d.hum > 80) return `💦 Humidité élevée : ${d.hum} %. Risque de moisissures — améliorez la ventilation.`;
            if (d.hum < 40) return `🏜️ Humidité faible : ${d.hum} %. Surveillez si les abeilles apportent suffisamment d'eau.`;
            return `💧 Humidité : ${d.hum} %. C'est correct (40–70 %). ✅`;
        }

        // Poids / miel
        if (/poids|miel|kg|kilog|recolte|essaim/.test(s)) {
            if (d.poids === null) return '⚖️ Aucune donnée de poids disponible.';
            if (d.poids < 10) return `⚖️ Poids faible : ${d.poids} kg. Réserves limitées, envisagez une nourriture si nécessaire.`;
            if (d.poids > 40) return `🍯 Poids important : ${d.poids} kg. La ruche est bien garnie, une récolte pourrait être envisagée !`;
            return `⚖️ Poids actuel : ${d.poids} kg. La colonie est bien approvisionnée.`;
        }

        // Luminosité / activité
        if (/lumin|lumiere|soleil|activit|jour|nuit/.test(s)) {
            if (d.lum === null) return '☀️ Aucune donnée de luminosité disponible.';
            if (d.lum > 70) return `☀️ Luminosité forte : ${d.lum} %. Journée active — les butineuses sont probablement en vol.`;
            if (d.lum < 20) return `🌙 Luminosité faible : ${d.lum} %. La ruche est en repos nocturne ou par temps couvert.`;
            return `☀️ Luminosité : ${d.lum} %. Activité modérée dans la ruche.`;
        }

        // État général / santé
        if (/etat|sante|bien|ok|comment|bonne|ruche|normal|probleme|alerte/.test(s)) {
            if (!d.hasRuche) return '🐝 Aucune ruche connectée. Ajoutez-en une via le panneau latéral.';
            const issues = [];
            if (d.temp !== null && (d.temp < 30 || d.temp > 37)) issues.push(`température hors plage (${d.temp} °C)`);
            if (d.hum  !== null && d.hum > 80) issues.push(`humidité élevée (${d.hum} %)`);
            if (d.poids !== null && d.poids < 10) issues.push(`poids faible (${d.poids} kg)`);
            if (issues.length) return `⚠️ Points d'attention : ${issues.join(', ')}. Vérifiez la ruche rapidement.`;
            return '✅ Tout semble normal ! Température, humidité et poids sont dans les normes.';
        }

        // Trappe / porte
        if (/trappe|porte|ouvri|fermer|motor/.test(s)) {
            return '🚪 Vous pouvez contrôler la trappe motorisée depuis l\'onglet **Actions** du tableau de bord.';
        }

        // Buzzer / alarme
        if (/buzz|alarm|son|bruit|signal/.test(s)) {
            return '🚨 L\'alarme sonore (buzzer) se déclenche depuis l\'onglet **Actions**.';
        }

        // Météo
        if (/meteo|pluie|vent|prevision|temps/.test(s)) {
            return '⛅ Consultez l\'onglet **Météo** pour les prévisions sur 7 jours à la position de votre ruche.';
        }

        // Graphiques
        if (/graphique|graph|histor|courbe|periode|donnee/.test(s)) {
            return '📈 Les graphiques sont disponibles dans l\'onglet **Tableau de bord**. Vous pouvez filtrer par 1h, 24h, 7j ou 30j.';
        }

        // Bonjour
        if (/bonjour|salut|hello|coucou|bonsoir/.test(s)) {
            return '🐝 Bonjour ! Je suis MAYA, votre assistante ruche. Demandez-moi la température, l\'humidité, le poids ou l\'état général de la colonie.';
        }

        // Merci
        if (/merci|super|parfait|top|bravo/.test(s)) {
            return '🍯 Avec plaisir ! N\'hésitez pas si vous avez d\'autres questions sur votre ruche.';
        }

        // Fallback
        return '🐝 Je peux vous renseigner sur la **température**, l\'**humidité**, le **poids**, la **luminosité** ou l\'**état général** de votre ruche. Que souhaitez-vous savoir ?';
    }

    // --- Suggestions ---
    const SUGGESTIONS = {
        default: ['🐝 État de la ruche ?', '🌡️ Température ?', '💧 Humidité ?', '⚖️ Poids du miel ?', '☀️ Activité ?'],
        temp:    ['💧 Humidité ?', '⚖️ Poids du miel ?', '🐝 État général ?'],
        hum:     ['🌡️ Température ?', '⚖️ Poids du miel ?', '🐝 État général ?'],
        poids:   ['🌡️ Température ?', '💧 Humidité ?', '🍯 Conseils récolte ?'],
        lum:     ['🐝 État de la ruche ?', '🌡️ Température ?', '⛅ Voir la météo ?'],
        etat:    ['🌡️ Température ?', '⚖️ Poids du miel ?', '⛅ Voir la météo ?'],
        meteo:   ['🐝 État de la ruche ?', '🌡️ Température ?', '💧 Humidité ?'],
        action:  ['🐝 État de la ruche ?', '🌡️ Température ?', '⚖️ Poids du miel ?'],
    };

    const suggBox = document.getElementById('chat-suggestions');

    function showSuggestions(topic = 'default') {
        suggBox.innerHTML = '';
        (SUGGESTIONS[topic] || SUGGESTIONS.default).forEach((label, i) => {
            const b = document.createElement('button');
            b.className = 'chat-sugg';
            b.textContent = label;
            b.style.animationDelay = (i * 0.06) + 's';
            b.addEventListener('click', () => sendText(label.replace(/^[\u{1F300}-\u{1FFFF}\s]+/u, '').trim()));
            suggBox.appendChild(b);
        });
    }

    function clearSuggestions() { suggBox.innerHTML = ''; }

    function topicOf(text) {
        const s = text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        if (/temp|chaud|froid|°/.test(s))               return 'temp';
        if (/humid|eau|hygro/.test(s))                   return 'hum';
        if (/poids|miel|kg|recolte|essaim|conseil/.test(s)) return 'poids';
        if (/lumin|soleil|activit|jour|nuit/.test(s))    return 'lum';
        if (/etat|sante|general|bien|ok|ruche|alerte/.test(s)) return 'etat';
        if (/meteo|pluie|vent|prevision/.test(s))        return 'meteo';
        if (/trappe|buzz|alarm|action/.test(s))          return 'action';
        return 'default';
    }

    // --- UI ---
    function toggleChat() {
        open = !open;
        panel.classList.toggle('open', open);
        btn.classList.toggle('open', open);
        if (open) {
            if (msgBox.children.length === 0) {
                addMsg('ai', '🐝 Bonjour ! Je suis MAYA, votre assistante apicole. Que voulez-vous savoir sur votre ruche ?');
                showSuggestions('default');
            }
            setTimeout(() => input.focus(), 350);
        }
    }

    function addMsg(role, text) {
        const d = document.createElement('div');
        d.className = 'chat-msg ' + role;
        d.textContent = text;
        msgBox.appendChild(d);
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    function showTyping() {
        const d = document.createElement('div');
        d.id = 'chat-typing';
        d.className = 'chat-typing-row';
        d.innerHTML = '<span></span><span></span><span></span>';
        msgBox.appendChild(d);
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    function hideTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    function sendText(text) {
        if (!text) return;
        clearSuggestions();
        input.value = '';
        input.style.height = 'auto';
        addMsg('user', text);
        showTyping();
        const topic = topicOf(text);
        setTimeout(() => {
            hideTyping();
            addMsg('ai', reply(text));
            showSuggestions(topic);
        }, 500 + Math.random() * 300);
    }

    function send() {
        sendText(input.value.trim());
    }

    btn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);
    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && open) toggleChat(); });
})();
</script>

</body>
</html>
