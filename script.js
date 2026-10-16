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
    return value === null ? '–' : `${value.toFixed(1).replace('.0', '').replace('.', ',')} ${unit}`;
}

// ---------- Diagramme ----------

Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = '#5c6784';

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
        borderColor: '#e8590c',
        backgroundColor: 'rgba(246, 196, 83, 0.25)',
        borderWidth: 2,
        fill: true,
        tension: 0.3,
    }];
    temperatureChart.update();

    rainChart.data.labels = daily.map((row) => formatDay(row.date));
    rainChart.data.datasets = [{
        label: 'Niederschlag',
        data: daily.map((row) => row.precipitation_mm),
        backgroundColor: '#1d3f8f',
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

// ---------- Geschichte (fester Zeitraum) ----------

// Die Texte beziehen sich auf diesen Zeitraum, darum bleiben diese Grafiken fix
const STORY_START = '2026-07-29';
const STORY_END = '2026-09-30';

const FARBE_HITZE = '#d7301f';
const FARBE_NEUTRAL = '#f6c453';
const FARBE_KUEHL = '#8fbfe0';
const FARBE_REGEN = '#1d3f8f';
const FARBE_LINIE = '#14213d';

function inStory(date) {
    return date >= STORY_START && date <= STORY_END;
}

function isAugust(date) {
    return date.slice(5, 7) === '08';
}

// "2026-08-03" → "03.08."
function shortDate(text) {
    return `${text.slice(8, 10)}.${text.slice(5, 7)}.`;
}

async function loadStory() {
    try {
        let heat;
        let allDays;
        try {
            [heat, allDays] = await Promise.all([
                loadJson(`${ENDPUNKT}?view=heat`),
                loadJson(`${ENDPUNKT}?view=daily&days=365`),
            ]);
        } catch (error) {
            // Datenbank nicht erreichbar: dieselben Daten aus der Notfall-Kopie
            console.warn('Geschichte: Endpunkt nicht erreichbar, nutze Fallback:', error.message);
            const fallback = await loadJson(FALLBACK);
            heat = fallback.heat;
            allDays = fallback.daily;
        }
        const days = heat.days.filter((day) => inStory(day.date));
        const waves = heat.heatwaves.filter((wave) => inStory(wave.from));
        const rainDays = allDays.filter((row) => inStory(row.date) && row.hours >= 20);

        renderStrip(days);
        renderHeatDays(days);
        renderNights(days);
        renderWaves(days, waves);
        renderRain(rainDays);
    } catch (error) {
        console.warn('Geschichte: Daten nicht verfügbar:', error.message);
    }
}

// Balkendiagramm mit gestrichelten Grenzlinien (z. B. 30 °C)
function storyChart(id, days, values, colors, label, unit, lines = []) {
    const labels = days.map((day) => shortDate(day.date));
    const datasets = [{
        type: 'bar',
        label,
        data: values,
        backgroundColor: colors,
        borderRadius: 2,
        barPercentage: 1,
        categoryPercentage: 0.85,
    }];
    lines.forEach((line) => {
        datasets.push({
            type: 'line',
            label: line.label,
            data: labels.map(() => line.value),
            borderColor: FARBE_LINIE,
            borderWidth: 1.5,
            borderDash: [6, 4],
            pointRadius: 0,
        });
    });

    new Chart(document.querySelector(`#${id}`), {
        data: { labels, datasets },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { maxTicksLimit: 9, maxRotation: 0 }, grid: { display: false } },
                y: { title: { display: true, text: unit } },
            },
        },
    });
}

// Farbklasse je nach Tageshöchstwert
function temperatureClass(value) {
    if (value >= 30) return 't-hot';
    if (value >= 25) return 't-warm';
    if (value >= 20) return 't-mild';
    if (value >= 15) return 't-cool';
    return 't-cold';
}

// Ein Streifen pro Tag, gefärbt nach dem Höchstwert
function renderStrip(days) {
    const strip = document.querySelector('#heat-strip');
    strip.innerHTML = '';
    days.forEach((day) => {
        const cell = document.createElement('span');
        cell.className = `day ${temperatureClass(day.temp_max_c)}`;
        if (day.tropical_night) {
            cell.classList.add('tropical');
        }
        cell.title = `${formatDay(day.date)}: max ${formatNumber(day.temp_max_c, '°C')}, min ${formatNumber(day.temp_min_c, '°C')}`;
        strip.appendChild(cell);
    });
}

function renderHeatDays(days) {
    const heatDays = days.filter((day) => day.heat_day);
    setText('n-heatdays', heatDays.length);
    setText('n-heatdays-2', heatDays.length);
    setText('n-heatdays-aug', heatDays.filter((day) => isAugust(day.date)).length);

    storyChart('chart-heatdays', days,
        days.map((day) => day.temp_max_c),
        days.map((day) => (day.heat_day ? FARBE_HITZE : FARBE_NEUTRAL)),
        'Höchstwert', '°C',
        [{ value: 30, label: 'Hitzetag ab' }]);
}

function renderNights(days) {
    const nights = days.filter((day) => day.tropical_night);
    setText('n-nights', nights.length);
    setText('n-nights-2', nights.length);
    setText('n-nights-aug', nights.filter((day) => isAugust(day.date)).length);

    storyChart('chart-nights', days,
        days.map((day) => day.temp_min_c),
        days.map((day) => (day.tropical_night ? FARBE_HITZE : FARBE_KUEHL)),
        'Tiefstwert', '°C',
        [{ value: 20, label: 'Tropennacht ab' }]);
}

function renderWaves(days, waves) {
    setText('n-waves', waves.length);

    const inWave = (date) => waves.some((wave) => date >= wave.from && date <= wave.to);
    storyChart('chart-waves', days,
        days.map((day) => day.temp_avg_c),
        days.map((day) => (inWave(day.date) ? FARBE_HITZE : FARBE_NEUTRAL)),
        'Tagesmittel', '°C',
        [{ value: 25, label: 'Stufe 3 ab' }, { value: 27, label: 'Stufe 4 ab' }]);

    // Liste der Hitzewellen
    const list = document.querySelector('#wave-list');
    list.innerHTML = '';
    waves.forEach((wave) => {
        const item = document.createElement('li');
        const period = document.createElement('strong');
        period.textContent = `${shortDate(wave.from)}–${shortDate(wave.to)}`;
        item.append(period, ` ${wave.days} Tage, bis ${formatNumber(wave.temp_max_c, '°C')}`);
        list.appendChild(item);
    });
}

function renderRain(days) {
    if (days.length === 0) {
        return;
    }
    const total = days.reduce((sum, day) => sum + (day.precipitation_mm ?? 0), 0);
    setText('n-rain', Math.round(total));

    storyChart('chart-story-rain', days,
        days.map((day) => day.precipitation_mm),
        FARBE_REGEN,
        'Regen', 'mm');

    // Grösster Temperatursturz von einem Tag auf den nächsten
    let drop = null;
    for (let i = 1; i < days.length; i++) {
        const change = days[i].temp_avg_c - days[i - 1].temp_avg_c;
        if (drop === null || change < drop.change) {
            drop = { change, before: days[i - 1], after: days[i] };
        }
    }

    // Längste Phase ohne nennenswerten Regen (unter 1 mm pro Tag)
    let longest = { length: 0, from: 0 };
    let current = 0;
    days.forEach((day, i) => {
        current = (day.precipitation_mm ?? 0) < 1 ? current + 1 : 0;
        if (current > longest.length) {
            longest = { length: current, from: i - current + 1 };
        }
    });
    const dryFrom = days[longest.from].date;
    const dryTo = days[longest.from + longest.length - 1].date;

    setText('rain-text',
        `Zwischen Ende Juli und Ende September fielen in Zürich rund ${Math.round(total)} mm Regen. ` +
        `Den Schlusspunkt unter die Hitze setzte ein Regentag: Am ${formatDay(drop.after.date)} fielen ` +
        `${formatNumber(drop.after.precipitation_mm, 'mm')}, und das Tagesmittel sank innerhalb eines Tages ` +
        `von ${formatNumber(drop.before.temp_avg_c, '°C')} auf ${formatNumber(drop.after.temp_avg_c, '°C')} – der grösste Temperatursturz des Sommers. ` +
        `Danach blieb es oft trocken: Die längste Phase ohne nennenswerten Regen dauerte ` +
        `${longest.length} Tage (${shortDate(dryFrom)}–${shortDate(dryTo)}).`);
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

// ---------- Sonne im Titel ----------

// Rechnet aus, wie weit man durch den Titelbereich gescrollt hat (0 bis 1),
// und gibt den Wert als CSS-Variable --p weiter. Den Rest macht style.css.
const hero = document.getElementById('hero');

function updateSun() {
    const scrollbar = hero.offsetHeight - window.innerHeight;   // so viel kann man im Titel scrollen
    const gescrollt = -hero.getBoundingClientRect().top;        // so viel ist schon gescrollt
    const p = Math.min(Math.max(gescrollt / scrollbar, 0), 1);  // auf 0 bis 1 begrenzen
    hero.style.setProperty('--p', p.toFixed(3));
}

window.addEventListener('scroll', updateSun, { passive: true });
updateSun();

loadData();
loadStatus();
loadStory();
