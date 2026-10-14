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
    renderChartTitles();
}

// Diagrammtitel als Befund für den gewählten Zeitraum
function renderChartTitles() {
    const fullDays = daily.filter((row) => row.hours >= 20);
    if (fullDays.length >= 2) {
        const first = fullDays[0];
        const last = fullDays[fullDays.length - 1];
        const diff = last.temp_avg_c - first.temp_avg_c;
        if (Math.abs(diff) >= 2) {
            setText('title-temperature',
                `Seit dem ${shortDate(first.date)} ${formatNumber(Math.abs(diff), 'Grad')} ${diff < 0 ? 'kühler' : 'wärmer'}.`);
        } else {
            const swing = Math.max(...fullDays.map((row) => row.temp_max_c - row.temp_min_c));
            setText('title-temperature', `Tag und Nacht liegen bis zu ${formatNumber(swing, 'Grad')} auseinander.`);
        }
        setText('hint-temperature',
            `Tagesmittel ${formatDay(first.date)}: ${formatNumber(first.temp_avg_c, '°C')}, ` +
            `${formatDay(last.date)}: ${formatNumber(last.temp_avg_c, '°C')} · stündliche Werte in °C`);
    } else {
        setText('title-temperature', 'Temperatur');
        setText('hint-temperature', 'Stündliche Werte in °C');
    }

    const total = daily.reduce((sum, row) => sum + (row.precipitation_mm ?? 0), 0);
    const rainyDays = daily.filter((row) => (row.precipitation_mm ?? 0) >= 1).length;
    if (daily.length === 0) {
        setText('title-rain', 'Niederschlag pro Tag');
        setText('hint-rain', 'Summe in Millimetern');
    } else if (rainyDays === 0) {
        setText('title-rain', total > 0 ? 'Nur ein paar Tropfen.' : 'Kein Regen in diesem Zeitraum.');
        setText('hint-rain', 'Summe pro Tag in Millimetern');
    } else {
        const wettest = daily.reduce((a, b) => ((b.precipitation_mm ?? 0) > (a.precipitation_mm ?? 0) ? b : a));
        setText('title-rain', `An ${rainyDays} von ${daily.length} Tagen hat es geregnet.`);
        setText('hint-rain',
            `Am meisten am ${formatDay(wettest.date)} (${formatNumber(wettest.precipitation_mm, 'mm')}) · Summe pro Tag in Millimetern`);
    }
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

// ---------- Befunde (über alle gesammelten Tage) ----------

const MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli',
    'August', 'September', 'Oktober', 'November', 'Dezember'];

function average(values) {
    return values.reduce((sum, value) => sum + value, 0) / values.length;
}

function shortDate(text) {
    const d = new Date(text + 'T12:00');
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.`;
}

// "2026-07-29" → "Ende Juli"
function periodOfMonth(text) {
    const d = new Date(text + 'T12:00');
    const part = d.getDate() <= 10 ? 'Anfang' : d.getDate() <= 20 ? 'Mitte' : 'Ende';
    return `${part} ${MONATE[d.getMonth()]}`;
}

async function loadStory() {
    let allDays;
    try {
        allDays = await loadJson(`${ENDPUNKT}?view=daily&days=365`);
    } catch (error) {
        allDays = (await loadJson(FALLBACK)).daily;
    }
    // nur vollständige Tage, sonst verzerrt der laufende Tag die Werte
    renderStory(allDays.filter((row) => row.hours >= 20));
}

function renderStory(rows) {
    if (rows.length < 14) {
        return;
    }

    // Abkühlung: erste gegen letzte Woche
    const firstWeek = rows.slice(0, 7);
    const lastWeek = rows.slice(-7);
    const before = average(firstWeek.map((row) => row.temp_avg_c));
    const after = average(lastWeek.map((row) => row.temp_avg_c));
    const diff = before - after;
    const start = periodOfMonth(rows[0].date);

    if (diff >= 1) {
        setText('headline', `Zürich ist seit ${start} um ${formatNumber(diff, 'Grad')} kühler geworden`);
    } else if (diff <= -1) {
        setText('headline', `Zürich ist seit ${start} um ${formatNumber(-diff, 'Grad')} wärmer geworden`);
    }
    setText('lead',
        `In der ersten Woche (${shortDate(firstWeek[0].date)}–${shortDate(firstWeek[6].date)}) lag die Tagesmitteltemperatur ` +
        `bei ${formatNumber(before, '°C')}, in der letzten Woche (${shortDate(lastWeek[0].date)}–${shortDate(lastWeek[6].date)}) ` +
        `noch bei ${formatNumber(after, '°C')}. In der Datenbank liegen ${rows.length} vollständige Tage, ` +
        `jede Stunde kommt ein neuer Wert dazu.`);

    setText('f-cooling', `${formatNumber(Math.abs(diff), 'Grad')} ${diff >= 0 ? 'kühler' : 'wärmer'}`);
    setText('f-cooling-text', `Tagesmittel erste Woche ${formatNumber(before, '°C')}, letzte Woche ${formatNumber(after, '°C')}.`);

    // Spannweite: heissester und kältester Wert
    const hottest = rows.reduce((a, b) => (b.temp_max_c > a.temp_max_c ? b : a));
    const coldest = rows.reduce((a, b) => (b.temp_min_c < a.temp_min_c ? b : a));
    setText('f-range', `${formatNumber(hottest.temp_max_c, '')}→ ${formatNumber(coldest.temp_min_c, '°C')}`);
    setText('f-range-text',
        `Höchstwert am ${formatDay(hottest.date)}, Tiefstwert am ${formatDay(coldest.date)}: ` +
        `${formatNumber(hottest.temp_max_c - coldest.temp_min_c, 'Grad')} Unterschied.`);

    // Regen: Anteil der drei nassesten Tage und längste Trockenphase (< 1 mm)
    const rain = rows.map((row) => row.precipitation_mm ?? 0);
    const total = rain.reduce((sum, value) => sum + value, 0);
    if (total > 0) {
        const topThree = [...rain].sort((a, b) => b - a).slice(0, 3).reduce((sum, value) => sum + value, 0);

        let longest = { length: 0, from: 0 };
        let current = 0;
        rain.forEach((value, i) => {
            current = value < 1 ? current + 1 : 0;
            if (current > longest.length) {
                longest = { length: current, from: i - current + 1 };
            }
        });
        let dryText = '';
        if (longest.length > 1) {
            const dryFrom = rows[longest.from].date;
            const dryTo = rows[longest.from + longest.length - 1].date;
            dryText = ` Dazwischen blieb es bis zu ${longest.length} Tage trocken (${shortDate(dryFrom)}–${shortDate(dryTo)}).`;
        }

        setText('f-rain', `${Math.round((topThree / total) * 100)} %`);
        setText('f-rain-text', `des gesamten Regens (${formatNumber(total, 'mm')}) fiel an nur drei Tagen.${dryText}`);
    }
}
// ---------- Hitze ----------

// Farbklasse je nach Tageshöchstwert
function temperatureClass(value) {
    if (value >= 30) return 't-hot';
    if (value >= 25) return 't-warm';
    if (value >= 20) return 't-mild';
    if (value >= 15) return 't-cool';
    return 't-cold';
}

async function loadHeat() {
    try {
        renderHeat(await loadJson(`${ENDPUNKT}?view=heat`));
    } catch (error) {
        console.warn('Hitze-Daten nicht verfügbar:', error.message);
        document.querySelector('#heat').hidden = true;
    }
}

function renderHeat(heat) {
    setText('h-days', heat.heat_days);
    setText('h-nights', heat.tropical_nights);
    setText('h-waves', heat.heatwaves.length);

    if (heat.heatwaves.length > 0) {
        const longest = heat.heatwaves.reduce((a, b) => (b.days > a.days ? b : a));
        setText('h-waves-text',
            `Mindestens drei Tage in Folge mit einem Tagesmittel ab 25 °C. Die längste dauerte ` +
            `${longest.days} Tage, vom ${shortDate(longest.from)} bis ${shortDate(longest.to)}`);
    }
    if (heat.last_heat_day) {
        setText('h-last', `Letzter Hitzetag: ${formatDay(heat.last_heat_day)}`);
    }

    if (heat.days.length > 0) {
        setText('h-start', formatDay(heat.days[0].date));
    }

    // Ein Streifen pro Tag, gefärbt nach dem Höchstwert
    const strip = document.querySelector('#heat-strip');
    strip.innerHTML = '';
    heat.days.forEach((day) => {
        const cell = document.createElement('span');
        cell.className = `day ${temperatureClass(day.temp_max_c)}`;
        if (day.tropical_night) {
            cell.classList.add('tropical');
        }
        cell.title = `${formatDay(day.date)}: max ${formatNumber(day.temp_max_c, '°C')}, min ${formatNumber(day.temp_min_c, '°C')}`;
        strip.appendChild(cell);
    });
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
loadStory();
loadHeat();