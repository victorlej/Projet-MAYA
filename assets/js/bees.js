/* ============================================================
 * MAYA — Abeilles volantes + particules de pollen
 * Décor d'ambiance, désactivé via prefers-reduced-motion.
 * ============================================================ */

(function () {

    const BEE_SVG = `
        <svg class="bee-svg" viewBox="0 0 64 44" xmlns="http://www.w3.org/2000/svg">
            <ellipse class="wing" cx="22" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
            <ellipse class="wing" cx="34" cy="14" rx="11" ry="8" fill="rgba(255,255,255,.85)" stroke="rgba(31,35,48,.25)" stroke-width="1"/>
            <ellipse cx="32" cy="26" rx="18" ry="12" fill="#f59e0b"/>
            <rect x="22" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
            <rect x="32" y="14" width="5" height="24" fill="#1f2330" opacity=".85"/>
            <circle cx="48" cy="22" r="6" fill="#1f2330"/>
            <circle cx="50" cy="20" r="1.4" fill="#fff"/>
            <path d="M52 18 L58 12 M52 24 L60 22" stroke="#1f2330" stroke-width="1.5" stroke-linecap="round" fill="none"/>
        </svg>
    `;

    function makeBee(index, total) {
        const bee = document.createElement('div');
        bee.className = 'bee bee-flyer';
        bee.innerHTML = BEE_SVG;
        const topPct = 8 + Math.random() * 70;
        const duration = 18 + Math.random() * 16;
        const delay = -(duration * (index / total)) - Math.random() * 4;
        const scale = 0.6 + Math.random() * 0.7;
        bee.style.top = topPct + '%';
        bee.style.left = '-80px';
        bee.style.transform = `scale(${scale})`;
        bee.style.animationDuration = duration + 's';
        bee.style.animationDelay = delay + 's';
        return bee;
    }

    function spawnBees() {
        const layer = document.createElement('div');
        layer.className = 'bee-layer';
        const isMobile = window.innerWidth < 768;
        const count = isMobile ? 3 : 6;
        for (let i = 0; i < count; i++) layer.appendChild(makeBee(i, count));
        document.body.appendChild(layer);
    }

    function spawnPollen() {
        const layer = document.createElement('div');
        layer.className = 'pollen-layer';
        const isMobile = window.innerWidth < 768;
        const count = isMobile ? 14 : 28;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'pollen';
            const dur = 10 + Math.random() * 14;
            const size = 4 + Math.random() * 7;
            p.style.left = Math.random() * 100 + 'vw';
            p.style.width = p.style.height = size + 'px';
            p.style.setProperty('--dur', dur + 's');
            p.style.setProperty('--dx', (Math.random() * 200 - 100) + 'px');
            p.style.animationDelay = -(Math.random() * dur) + 's';
            p.style.opacity = (0.4 + Math.random() * 0.5).toString();
            layer.appendChild(p);
        }
        document.body.appendChild(layer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { spawnBees(); spawnPollen(); });
    } else {
        spawnBees(); spawnPollen();
    }
})();
