/* ============================================================
 * MAYA — Charts (Chart.js)
 * Lit window.MAYA.{labels, poids, temp, hum, lum}
 * Expose updateChartColors() consommé par toggleTheme()
 * ============================================================ */

(function () {
    if (!window.MAYA || !MAYA.hasRuche || !MAYA.labels || MAYA.labels.length === 0) return;

    const charts = {};

    const txt  = () => document.documentElement.getAttribute('data-theme') === 'dark' ? '#f5f6fa' : '#1f2330';
    const grid = () => document.documentElement.getAttribute('data-theme') === 'dark' ? 'rgba(255,255,255,.06)' : 'rgba(31,35,48,.06)';

    function gradient(ctx, c1, c2) {
        const g = ctx.createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0, c1); g.addColorStop(1, c2);
        return g;
    }

    function baseOpts() {
        return {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    labels: {
                        color: txt(), font: { family: 'Inter', weight: '600' },
                        usePointStyle: true, padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(31,35,48,.95)',
                    titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                    bodyFont:  { family: 'Inter' },
                    padding: 12, borderRadius: 10, cornerRadius: 10
                }
            },
            scales: {
                x: { ticks: { color: txt(), font: { family: 'Inter' } }, grid: { display: false } },
                y: { ticks: { color: txt(), font: { family: 'Inter' } }, grid: { color: grid(), drawBorder: false } }
            }
        };
    }

    /* Poids */
    const wctx = document.getElementById('weightChart').getContext('2d');
    charts.weight = new Chart(wctx, {
        type: 'line',
        data: {
            labels: MAYA.labels,
            datasets: [{
                label: 'Poids (kg)', data: MAYA.poids,
                borderColor: '#f59e0b',
                backgroundColor: gradient(wctx, 'rgba(245,158,11,.4)', 'rgba(245,158,11,0)'),
                borderWidth: 3, tension: .4, fill: true,
                pointRadius: 0, pointHoverRadius: 6,
                pointHoverBackgroundColor: '#f59e0b',
                pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
            }]
        },
        options: baseOpts()
    });

    /* Climat (temp + humidité, double axe) */
    charts.climate = new Chart(document.getElementById('climateChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: MAYA.labels,
            datasets: [
                { label: 'Temp. (°C)', data: MAYA.temp, borderColor: '#ef4444', backgroundColor: '#ef4444',
                  borderWidth: 2.5, tension: .4, yAxisID: 'y',  pointRadius: 0, pointHoverRadius: 5 },
                { label: 'Hum. (%)',   data: MAYA.hum,  borderColor: '#3b82f6', backgroundColor: '#3b82f6',
                  borderWidth: 2.5, tension: .4, yAxisID: 'y1', pointRadius: 0, pointHoverRadius: 5 }
            ]
        },
        options: { ...baseOpts(), scales: {
            x: baseOpts().scales.x,
            y:  { type: 'linear', position: 'left',  ticks: { color: txt() }, grid: { color: grid() } },
            y1: { type: 'linear', position: 'right', ticks: { color: txt() }, grid: { drawOnChartArea: false } }
        }}
    });

    /* Luminosité */
    const lctx = document.getElementById('lumChart').getContext('2d');
    charts.lum = new Chart(lctx, {
        type: 'line',
        data: {
            labels: MAYA.labels,
            datasets: [{
                label: 'Luminosité (%)', data: MAYA.lum,
                borderColor: '#eab308',
                backgroundColor: gradient(lctx, 'rgba(234,179,8,.35)', 'rgba(234,179,8,0)'),
                borderWidth: 2.5, tension: .35, fill: true,
                pointRadius: 0, pointHoverRadius: 5,
            }]
        },
        options: { ...baseOpts(), scales: {
            x: baseOpts().scales.x,
            y: { suggestedMin: 0, suggestedMax: 100, ticks: { color: txt() }, grid: { color: grid() } }
        }}
    });

    /* Repeint les couleurs lors du switch dark/light */
    window.updateChartColors = function () {
        const tc = txt(), gc = grid();
        Object.values(charts).forEach(c => {
            c.options.plugins.legend.labels.color = tc;
            c.options.scales.x.ticks.color = tc;
            if (c.options.scales.y)  { c.options.scales.y.ticks.color  = tc; c.options.scales.y.grid.color = gc; }
            if (c.options.scales.y1) { c.options.scales.y1.ticks.color = tc; }
            c.update();
        });
    };
})();
