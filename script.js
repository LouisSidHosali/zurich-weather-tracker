const ENDPUNKT = 'unload.php';
const FALLBACK = 'data/fallback.json';
const WOCHENTAGE = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

let days = 7;
let hourly = [];
let daily = [];
let source = 'endpoint';

// ---------- Daten laden ----------

async function loadJson(url) {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Status ${response.status}`);
    }
    const contentType = response.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
        throw new Error('Antwort ist kein JSON');
    }
    return response.json();
}

function sinceDate(numberOfDays) {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - (numberOfDays - 1));
    return d;
}

// Fallback-Datei hat denselben Aufbau, hier filtert der Browser selbst
async function loadFallback() {
    const data = await loadJson(FALLBACK);
    const since = sinceDate(days);
    return {
        hourly: data.hourly.filter((row) => toDate(row.measured_at) >= since),
        daily: data.daily.filter((row) => new Date(row.date + 'T00:00') >= since),
        status: data.status,
    };
}

async function loadData() {
    setStatus('Daten werden geladen …');
    try {
        try {
            [hourly, daily] = await Promise.all([
                loadJson(`${ENDPUNKT}?view=hourly&days=${days}`),
                loadJson(`${ENDPUNKT}?view=daily&days=${days}`),
            ]);
            source = 'endpoint';
        } catch (error) {
            console.warn('Endpunkt nicht erreichbar, nutze Fallback:', error.message);
            const fallback = await loadFallback();
            hourly = fallback.hourly;
            daily = fallback.daily;
            renderStatus(fallback.status);
            source = 'fallback';
        }

        render();

        if (hourly.length === 0) {
            setStatus('Für diesen Zeitraum sind noch keine Daten gespeichert.');
        } else if (source === 'endpoint') {
            setStatus(`${hourly.length} Stundenwerte aus der Datenbank.`);
        } else {
            setStatus(`${hourly.length} Stundenwerte aus der Fallback-Datei (Datenbank nicht erreichbar).`);
        }
    } catch (error) {
        console.error(error);
        setStatus('Die Daten konnten nicht geladen werden.', true);
    }
}

async function loadStatus() {
    try {
        renderStatus(await loadJson(`${ENDPUNKT}?view=status`));
    } catch (error) {
        console.warn('Status nicht verfügbar:', error.message);
    }
}

// ---------- Formatierung ----------

// "2026-10-05 14:00:00" → Date (lokale Zeit)
function toDate(text) {
    return new Date(text.replace(' ', 'T'));
}

function pad(number) {
    return String(number).padStart(2, '0');
}

function formatHour(text) {
    const d = toDate(text);
    return `${WOCHENTAGE[d.getDay()]} ${pad(d.getDate())}.${pad(d.getMonth() + 1)}. ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function formatDay(text) {
    const d = new Date(text + 'T12:00');
    return `${WOCHENTAGE[d.getDay()]} ${pad(d.getDate())}.${pad(d.getMonth() + 1)}.`;
}

function formatNumber(value, unit) {
    return value === null ? '–' : `${value.toFixed(1).replace('.0', '')} ${unit}`;
}

// ---------- Diagramme ----------

const temperatureChart = new Chart(document.querySelector('#chart-temperature'), {
    type: 'line',
    data: { labels: [], datasets: [] },
    options: {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        elements: { point: { radius: 0 } },
        scales: {
            x: { ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { title: { display: true, text: '°C' } },
        },
    },
});

const rainChart = new Chart(document.querySelector('#chart-rain'), {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { maxTicksLimit: 10, maxRotation: 0 }, grid: { display: false } },
            y: { beginAtZero: true, title: { display: true, text: 'mm' } },
        },
    },
});

function render() {
    temperatureChart.data.labels = hourly.map((row) => formatHour(row.measured_at));
    temperatureChart.data.datasets = [{
        label: 'Temperatur',
        data: hourly.map((row) => row.temperature_c),
        borderColor: '#e4572e',
        backgroundColor: 'rgba(228, 87, 46, 0.12)',
        borderWidth: 2,
        fill: true,
        tension: 0.3,
    }];
    temperatureChart.update();

    rainChart.data.labels = daily.map((row) => formatDay(row.date));
    rainChart.data.datasets = [{
        label: 'Niederschlag',
        data: daily.map((row) => row.precipitation_mm),
        backgroundColor: '#2f80ed',
        borderRadius: 4,
    }];
    rainChart.update();

    renderKpis();
}

function setText(id, value) {
    document.querySelector(`#${id}`).textContent = value ?? '–';
}

function renderKpis() {
    if (hourly.length === 0) {
        ['kpi-now', 'kpi-max', 'kpi-min', 'kpi-rain'].forEach((id) => setText(id, '–'));
        return;
    }

    const latest = hourly[hourly.length - 1];
    const warmest = hourly.reduce((a, b) => (b.temperature_c > a.temperature_c ? b : a));
    const coldest = hourly.reduce((a, b) => (b.temperature_c < a.temperature_c ? b : a));
    const rainTotal = daily.reduce((sum, row) => sum + (row.precipitation_mm ?? 0), 0);
    const rainyDays = daily.filter((row) => (row.precipitation_mm ?? 0) >= 1).length;

    setText('kpi-now', formatNumber(latest.temperature_c, '°C'));
    setText('kpi-now-time', formatHour(latest.measured_at));
    setText('kpi-max', formatNumber(warmest.temperature_c, '°C'));
    setText('kpi-max-time', formatHour(warmest.measured_at));
    setText('kpi-min', formatNumber(coldest.temperature_c, '°C'));
    setText('kpi-min-time', formatHour(coldest.measured_at));
    setText('kpi-rain', formatNumber(rainTotal, 'mm'));
    setText('kpi-rain-days', `${rainyDays} Regentage (≥ 1 mm)`);
}

function renderStatus(status) {
    if (!status) {
        return;
    }
    setText('m-runs', status.runs);
    setText('m-total', status.total_hours);
    if (status.first_measured_at && status.last_measured_at) {
        setText('m-range', `${formatDay(status.first_measured_at.slice(0, 10))} bis ${formatHour(status.last_measured_at)}`);
    }
    const run = status.last_run;
    if (run) {
        setText('m-last', formatHour(run.run_at));
        setText('m-input', run.input_hours);
        setText('m-future', run.future_hours);
        setText('m-missing', run.missing_temperature);
        setText('m-invalid', run.invalid_values);
        setText('m-inserted', run.inserted_rows);
    }
}

function setStatus(text, isError = false) {
    const el = document.querySelector('#status');
    el.textContent = text;
    el.classList.toggle('error', isError);
}

// ---------- Bedienung ----------

document.querySelectorAll('.range button').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.range button').forEach((b) => b.classList.remove('active'));
        button.classList.add('active');
        days = Number(button.dataset.days);
        loadData();
    });
});

loadData();
loadStatus();
