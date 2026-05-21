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

<!-- ===== Chatbot JS ===== -->
<script>
(function () {
    const btn      = document.getElementById('chat-btn');
    const panel    = document.getElementById('chat-panel');
    const closeBtn = document.getElementById('chat-close');
    const input    = document.getElementById('chat-input');
    const sendBtn  = document.getElementById('chat-send');
    const msgBox   = document.getElementById('chat-messages');
    let open = false;

    function toggleChat() {
        open = !open;
        panel.classList.toggle('open', open);
        btn.classList.toggle('open', open);
        if (open) {
            if (msgBox.children.length === 0) addMsg('ai', '🐝 Bonjour ! Je suis MAYA, votre assistante apicole. Comment puis-je vous aider avec votre rucher ?');
            setTimeout(() => input.focus(), 350);
        }
    }

    function addMsg(role, text) {
        const d = document.createElement('div');
        d.className = 'chat-msg ' + role;
        d.textContent = text;
        msgBox.appendChild(d);
        msgBox.scrollTop = msgBox.scrollHeight;
        return d;
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

    async function send() {
        const text = input.value.trim();
        if (!text || sendBtn.disabled) return;

        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;
        addMsg('user', text);
        showTyping();

        const d = window.MAYA || {};
        const ctx = {
            hasRuche: d.hasRuche ?? false,
            temp:  Array.isArray(d.temp)  && d.temp.length  ? d.temp[d.temp.length - 1]   : null,
            hum:   Array.isArray(d.hum)   && d.hum.length   ? d.hum[d.hum.length - 1]     : null,
            poids: Array.isArray(d.poids) && d.poids.length ? d.poids[d.poids.length - 1] : null,
            lum:   Array.isArray(d.lum)   && d.lum.length   ? d.lum[d.lum.length - 1]     : null,
        };

        try {
            const res  = await fetch('ajax/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, context: ctx }),
            });
            const data = await res.json();
            hideTyping();
            addMsg('ai', data.reply ?? 'Désolé, je n\'ai pas pu répondre.');
        } catch (e) {
            hideTyping();
            addMsg('ai', '⚠️ Erreur réseau. Réessayez dans un instant.');
        }

        sendBtn.disabled = false;
        input.focus();
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
