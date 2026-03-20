<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>온도 센서 실시간 모니터링</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        h1 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #f97316, #ef4444, #eab308);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }
        .status-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            50% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stat-value .unit { font-size: 0.9rem; color: #64748b; font-weight: 400; }
        .stat-current .stat-value {
            background: linear-gradient(135deg, #f97316, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Chart */
        .chart-box {
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #e2e8f0;
        }
        .chart-container { position: relative; width: 100%; height: 260px; }
        canvas { width: 100% !important; height: 100% !important; }

        /* Table */
        .table-box {
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 14px;
            padding: 20px;
        }
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 400px; }
        thead th {
            text-align: left;
            padding: 12px 14px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            border-bottom: 1px solid rgba(148,163,184,0.1);
        }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover { background: rgba(249,115,22,0.05); }
        tbody td {
            padding: 12px 14px;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(148,163,184,0.05);
            color: #cbd5e1;
        }
        .temp-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .temp-cold { background: rgba(56,189,248,0.15); color: #38bdf8; }
        .temp-normal { background: rgba(34,197,94,0.15); color: #4ade80; }
        .temp-warm { background: rgba(250,204,21,0.15); color: #fbbf24; }
        .temp-hot { background: rgba(239,68,68,0.15); color: #f87171; }
        .id-cell { color: #64748b; }
        .time-cell { color: #94a3b8; font-size: 0.82rem; }

        @keyframes fadeIn {
            from { background: rgba(249,115,22,0.12); }
            to { background: transparent; }
        }
        .new-row { animation: fadeIn 1.5s ease-out; }
        .loading { text-align: center; padding: 40px; color: #64748b; }

        @media (max-width: 600px) {
            .container { padding: 20px 14px; }
            h1 { font-size: 1.4rem; }
            .stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-value { font-size: 1.4rem; }
            thead th, tbody td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🌡️ 온도 센서 모니터링</h1>
    <p class="subtitle">tempdb · temp 테이블 실시간 데이터</p>
    <div class="status-bar">
        <span class="status-dot"></span>
        <span id="statusText">연결 중...</span>
    </div>

    <div class="stats">
        <div class="stat-card stat-current">
            <div class="stat-label">현재 온도</div>
            <div class="stat-value" id="curTemp">--<span class="unit">°C</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">평균 온도</div>
            <div class="stat-value" id="avgTemp">--<span class="unit">°C</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">최고 온도</div>
            <div class="stat-value" id="maxTemp">--<span class="unit">°C</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">최저 온도</div>
            <div class="stat-value" id="minTemp">--<span class="unit">°C</span></div>
        </div>
    </div>

    <div class="chart-box">
        <h2 class="section-title">📈 온도 변화 추이</h2>
        <div class="chart-container">
            <canvas id="tempChart"></canvas>
        </div>
    </div>

    <div class="table-box">
        <h2 class="section-title">📋 최근 데이터 (최대 30건)</h2>
        <div class="table-wrapper" id="tableContent">
            <div class="loading">데이터를 불러오는 중...</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
let chart = null;
let prevIds = new Set();

function tempClass(v) {
    if (v < 20) return 'temp-cold';
    if (v < 40) return 'temp-normal';
    if (v < 70) return 'temp-warm';
    return 'temp-hot';
}

function fmtTime(dt) {
    const d = new Date(dt);
    return [d.getHours(), d.getMinutes(), d.getSeconds()].map(n => String(n).padStart(2,'0')).join(':');
}

function initChart(labels, values) {
    const ctx = document.getElementById('tempChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(249,115,22,0.25)');
    grad.addColorStop(1, 'rgba(249,115,22,0)');

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: '온도 (°C)',
                data: values,
                borderColor: '#f97316',
                backgroundColor: grad,
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#0f172a',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500 },
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.9)',
                    titleColor: '#94a3b8',
                    bodyColor: '#f1f5f9',
                    borderColor: 'rgba(148,163,184,0.2)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: false,
                    callbacks: { label: c => `온도: ${c.parsed.y}°C` }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(148,163,184,0.06)' },
                    ticks: { color: '#475569', font: { size: 11 }, maxTicksLimit: 10 },
                },
                y: {
                    min: 0, max: 100,
                    grid: { color: 'rgba(148,163,184,0.06)' },
                    ticks: { color: '#475569', font: { size: 11 }, callback: v => v + '°C' },
                }
            }
        }
    });
}

function updateChart(labels, values) {
    if (!chart) { initChart(labels, values); return; }
    chart.data.labels = labels;
    chart.data.datasets[0].data = values;
    chart.update('none');
}

function renderTable(data) {
    const newIds = new Set(data.map(r => r.id));
    let html = `<table><thead><tr><th>ID</th><th>온도</th><th>기록 시간</th></tr></thead><tbody>`;
    data.forEach(row => {
        const isNew = !prevIds.has(row.id) && prevIds.size > 0;
        const t = parseFloat(row.temperature);
        html += `<tr class="${isNew ? 'new-row' : ''}">
            <td class="id-cell">#${row.id}</td>
            <td><span class="temp-badge ${tempClass(t)}">${t.toFixed(2)}°C</span></td>
            <td class="time-cell">${row.created_at}</td></tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('tableContent').innerHTML = html;
    prevIds = newIds;
}

function updateStats(data) {
    if (!data.length) return;
    const vals = data.map(r => parseFloat(r.temperature));
    const cur = vals[0], avg = (vals.reduce((a,b) => a+b, 0) / vals.length).toFixed(1);
    const max = Math.max(...vals).toFixed(1), min = Math.min(...vals).toFixed(1);
    document.getElementById('curTemp').innerHTML = `${cur.toFixed(1)}<span class="unit">°C</span>`;
    document.getElementById('avgTemp').innerHTML = `${avg}<span class="unit">°C</span>`;
    document.getElementById('maxTemp').innerHTML = `${max}<span class="unit">°C</span>`;
    document.getElementById('minTemp').innerHTML = `${min}<span class="unit">°C</span>`;
}

async function fetchData() {
    try {
        const resp = await fetch('api.php');
        const data = await resp.json();
        if (data.error) { document.getElementById('statusText').textContent = '⚠️ ' + data.error; return; }

        const chron = [...data].reverse();
        updateChart(chron.map(r => fmtTime(r.created_at)), chron.map(r => parseFloat(r.temperature)));
        renderTable(data);
        updateStats(data);

        const now = new Date();
        const ts = [now.getHours(), now.getMinutes(), now.getSeconds()].map(n => String(n).padStart(2,'0')).join(':');
        document.getElementById('statusText').textContent = `실시간 업데이트 중 · 마지막 갱신: ${ts}`;
    } catch (e) {
        document.getElementById('statusText').textContent = '⚠️ 데이터 로드 실패';
        console.error(e);
    }
}

fetchData();
setInterval(fetchData, 5000);
</script>
</body>
</html>
