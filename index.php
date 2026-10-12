<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zürich Weather Tracker</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>
<main>
    <header>
        <p class="eyebrow">IM3 · Data Story</p>
        <h1>Zürich Weather Tracker</h1>
        <p class="lead">
            Wie entwickeln sich Temperatur und Niederschlag in Zürich?
            Jede Stunde holt ein Skript die aktuellen Werte und speichert sie in einer eigenen Datenbank.
        </p>
    </header>

    <!-- Kennzahlen -->
    <section class="kpis">
        <div class="kpi">
            <span class="kpi-label">Zuletzt gemessen</span>
            <span class="kpi-value" id="kpi-now">–</span>
            <span class="kpi-sub" id="kpi-now-time">&nbsp;</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Wärmste Stunde</span>
            <span class="kpi-value" id="kpi-max">–</span>
            <span class="kpi-sub" id="kpi-max-time">&nbsp;</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Kälteste Stunde</span>
            <span class="kpi-value" id="kpi-min">–</span>
            <span class="kpi-sub" id="kpi-min-time">&nbsp;</span>
        </div>
        <div class="kpi">
            <span class="kpi-label">Regen total</span>
            <span class="kpi-value" id="kpi-rain">–</span>
            <span class="kpi-sub" id="kpi-rain-days">&nbsp;</span>
        </div>
    </section>

    <nav class="range" aria-label="Zeitraum">
        <button data-days="3">3 Tage</button>
        <button data-days="7" class="active">7 Tage</button>
        <button data-days="30">30 Tage</button>
        <button data-days="60">60 Tage</button>
    </nav>

    <p class="status" id="status">Daten werden geladen …</p>

    <section class="card">
        <h2>Temperatur</h2>
        <p class="hint">Stündliche Werte in °C</p>
        <div class="chart-box"><canvas id="chart-temperature"></canvas></div>
    </section>

    <section class="card">
        <h2>Niederschlag pro Tag</h2>
        <p class="hint">Summe in Millimetern</p>
        <div class="chart-box"><canvas id="chart-rain"></canvas></div>
    </section>

    <section class="card method">
        <h2>Methode</h2>
        <div class="method-grid">
            <div>
                <h3>Sammeln</h3>
                <p>Ein Cronjob ruft jede Stunde <code>etl/load.php</code> auf.
                   Bisher <strong id="m-runs">–</strong> Durchläufe,
                   <strong id="m-total">–</strong> Stunden gespeichert
                   (<span id="m-range">–</span>).</p>
            </div>
            <div>
                <h3>Bereinigen</h3>
                <p>Letzter Durchlauf (<span id="m-last">–</span>):
                   <strong id="m-input">–</strong> Stunden geholt,
                   <strong id="m-future">–</strong> Prognosestunden entfernt,
                   <strong id="m-missing">–</strong> ohne Temperatur verworfen,
                   <strong id="m-invalid">–</strong> unmögliche Werte auf null gesetzt,
                   <strong id="m-inserted">–</strong> neu gespeichert.</p>
            </div>
            <div>
                <h3>Grenze</h3>
                <p>Die Werte sind stündliche Modelldaten von Open-Meteo für einen Punkt in Zürich,
                   keine Messung einer einzelnen Wetterstation. Prognosen werden bewusst nicht gespeichert.</p>
            </div>
        </div>
    </section>

    <footer>
        <p>
            Daten: <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a>
            (CC BY 4.0), Standort Zürich (47.38 N, 8.54 E).
        </p>
        <p>Ablauf: etl/extract.php → etl/transform.php → etl/load.php (stündlich per Cronjob) → MySQL → unload.php → Chart.js</p>
    </footer>
</main>

<script src="script.js"></script>
</body>
</html>
