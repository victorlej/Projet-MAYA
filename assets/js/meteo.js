/* ============================================================
 * MAYA — Météo (Open-Meteo, prévisions 7 jours)
 * Lit window.MAYA.{lat, lon, hasRuche}
 * ============================================================ */

(function () {
    if (!window.MAYA || !MAYA.hasRuche) return;

    const CODES = {
        0:'☀️',1:'☀️',2:'☁️',3:'☁️',
        45:'🌫️',48:'🌫️',
        51:'🌦️',53:'🌦️',55:'🌦️',
        61:'🌧️',63:'🌧️',65:'🌧️',
        71:'❄️',73:'❄️',75:'❄️',
        80:'🌦️',81:'🌦️',82:'🌦️',
        95:'⛈️'
    };

    fetch(`https://api.open-meteo.com/v1/forecast?latitude=${MAYA.lat}&longitude=${MAYA.lon}&daily=weathercode,temperature_2m_max,precipitation_sum&timezone=auto`)
        .then(r => r.json())
        .then(d => {
            let html = '';
            for (let i = 0; i < 7; i++) {
                const day  = new Date(d.daily.time[i]).toLocaleDateString('fr-FR', { weekday: 'short' });
                const tmax = d.daily.temperature_2m_max[i];
                const rain = d.daily.precipitation_sum[i];
                const code = d.daily.weathercode[i];
                const flightOK = !(tmax < 12 || rain > 1.0 || [71, 73, 75, 95].includes(code));

                html += `
                    <div class="weather-day" style="animation-delay:${i * 0.06}s">
                        <div class="day">${i === 0 ? "Auj." : day}</div>
                        <span class="emoji">${CODES[code] || '⛅'}</span>
                        <div class="temp">${tmax}°C</div>
                        <div class="rain">💧 ${rain} mm</div>
                        <span class="badge ${flightOK ? 'badge-ok' : 'badge-warn'}">
                            ${flightOK ? '✓ Vol actif' : '✕ Vol limité'}
                        </span>
                    </div>`;
            }
            document.getElementById('meteo-content').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('meteo-content').innerHTML =
                '<div style="grid-column:1/-1; text-align:center; color:var(--text-soft); padding:30px;">Météo indisponible.</div>';
        });
})();
