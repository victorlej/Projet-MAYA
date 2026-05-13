/* ============================================================
 * MAYA — UI : theme, sidebar, tabs, toast, modal, csv export
 * ============================================================ */

const ROOT = document.documentElement;

/* ---------- Theme ---------- */
function toggleTheme() {
    const next = ROOT.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    ROOT.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    if (typeof updateChartColors === 'function') updateChartColors();
}

/* ---------- Sidebar ---------- */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('main-content');
    const overlay = document.getElementById('sidebar-overlay');
    if (window.innerWidth > 991) {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    } else {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}

/* ---------- Tabs ---------- */
function switchTab(id, el) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    el.classList.add('active');
}

/* ---------- Toast ---------- */
function showToast(msg, type = 'info') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'show ' + type;
    setTimeout(() => t.className = '', 3000);
}
if (window.MAYA && window.MAYA.toast) {
    showToast(window.MAYA.toast.msg, window.MAYA.toast.type);
}

/* ---------- City reverse geocoding ---------- */
if (window.MAYA && window.MAYA.hasRuche) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${MAYA.lat}&lon=${MAYA.lon}`)
        .then(r => r.json())
        .then(d => {
            const a = d.address || {};
            document.getElementById('city-name').textContent =
                a.city || a.town || a.village || a.county || 'Lieu inconnu';
        })
        .catch(() => document.getElementById('city-name').textContent = 'Lieu non résolu');
}

/* ---------- Chart visibility toggles ---------- */
function toggleChart(type) {
    const wrap = document.getElementById('wrapper-' + type);
    const chip = document.getElementById('chip-' + type);
    const isActive = chip.classList.toggle('active');
    wrap.classList.toggle('chart-hidden', !isActive);
    localStorage.setItem('show_chart_' + type, isActive ? 'true' : 'false');
}
['poids','climat','lum'].forEach(type => {
    if (localStorage.getItem('show_chart_' + type) === 'false') {
        const chip = document.getElementById('chip-' + type);
        const wrap = document.getElementById('wrapper-' + type);
        if (chip && wrap) { chip.classList.remove('active'); wrap.classList.add('chart-hidden'); }
    }
});

/* ---------- Modal diagnostic ---------- */
const DIAGNOSTICS = {
    temp: (v) => {
        if (v == 0)  return ['🌡️ Température', "Aucune donnée valide reçue par la sonde."];
        if (v < 30)  return ['🌡️ Température', `<b style="color:var(--red);">Alerte hypothermie (&lt;30°C)</b><br><br>Risque mortel pour le couvain (couvain plâtré). La grappe peine à se chauffer et dépense énormément d'énergie. Vérifie l'isolation ou le volume de la ruche.`];
        if (v > 37)  return ['🌡️ Température', `<b style="color:var(--red);">Alerte surchauffe (&gt;37°C)</b><br><br>Les abeilles ventilent à l'extrême. Risque de fonte des cires et d'étouffement. Apporte rapidement de l'ombre et un point d'eau.`];
        return ['🌡️ Température', `<b style="color:var(--green);">Température idéale</b><br><br>Le couvain se développe parfaitement. Les nourrices maintiennent la chaleur requise pour garantir l'éclosion dans les temps.`];
    },
    hum: (v) => {
        if (v > 80)  return ['💧 Humidité', `<b style="color:var(--red);">Alerte fongique (&gt;80%)</b><br><br>L'air est saturé. Fort risque de moisissures sur les cadres de rive et de maladies fongiques. Ouvre l'aération du plancher.`];
        if (v < 30)  return ['💧 Humidité', `<b style="color:var(--honey-600);">Air trop sec (&lt;30%)</b><br><br>L'éclosion des larves nécessite une certaine humidité. Les butineuses s'épuisent à ramener de l'eau. Pense à un abreuvoir.`];
        return ['💧 Humidité', `<b style="color:var(--green);">Humidité optimale</b><br><br>Hygrométrie parfaite. Les ventileuses évaporent correctement l'eau du nectar pour le transformer en miel.`];
    },
    poids: (v) => ['⚖️ Suivi pondéral',
        `Poids actuel : <b>${v} kg</b>.<br><br>
         • <b>Hausse continue</b> — c'est une miellée, prépare tes hausses.<br>
         • <b>Baisse lente</b> — période de consommation (hiver, disette estivale).<br>
         • <b>Chute brutale (2 à 4 kg)</b> — essaimage très probable.`],
    lum: (v) => v > 50
        ? ['☀️ Luminosité', `<b>Journée lumineuse</b><br><br>Signal de réveil pour les butineuses. Les jeunes abeilles font leurs vols d'orientation devant le trou de vol.`]
        : ['☀️ Luminosité', `<b>Faible luminosité</b><br><br>La colonie est au repos, regroupée en grappe. Activité extérieure réduite (nuit / mauvais temps).`],
};
function openAnalysis(type, value) {
    const [head, body] = DIAGNOSTICS[type](value);
    document.getElementById('modalTitle').innerHTML = head;
    document.getElementById('modalBody').innerHTML  = body;
    document.getElementById('analysisModal').classList.add('show');
}
function closeModal() { document.getElementById('analysisModal').classList.remove('show'); }

/* ---------- Export CSV ---------- */
function exportCSV() {
    const { labels, poids, temp, hum } = window.MAYA;
    if (!labels.length) return showToast('Aucune donnée à exporter.', 'error');
    let csv = 'Date,Poids(kg),Temperature(C),Humidite(%)\n';
    for (let i = 0; i < labels.length; i++) {
        csv += `${labels[i]},${poids[i]},${temp[i]},${hum[i]}\n`;
    }
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
    a.download = 'historique_ruche.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    showToast('Export CSV réussi', 'success');
}

/* ---------- Auto-refresh ---------- */
if (window.MAYA && window.MAYA.refreshMs) {
    setTimeout(() => location.reload(), window.MAYA.refreshMs);
}
