<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zürich Weather Tracker</title>
    <link rel="stylesheet" href="style.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>
<main>
    <header>
        <p class="eyebrow">Zürich Weather Tracker · Einzelprojekt IM3</p>
        <h1 id="headline">Wie sich das Wetter in Zürich verändert</h1>
        <p class="lead" id="lead">
            Wie entwickeln sich Temperatur und Niederschlag in Zürich?
            Jede Stunde holt ein Skript die aktuellen Werte und speichert sie in einer eigenen Datenbank.
        </p>
    </header>

    <section class="intro" aria-labelledby="intro-title">
        <h2 id="intro-title">Warum Zürichs Wetter beobachten?</h2>
        <p>
            Der Sommer 2026 war in der Schweiz der heisseste seit Messbeginn, begleitet von extremer Trockenheit.
            In Zürich stieg das Thermometer auf 38,3 Grad, so hoch wie noch nie seit 1864.
            Wer auf Hitze und Trockenheit reagieren will, muss zuerst wissen, wie sich das Wetter entwickelt.
        </p>
        <p>
            Dieser Tracker beobachtet deshalb Stunde für Stunde Temperatur und Regen in Zürich und fragt:
            Wie lange bleibt es warm? Wie schnell kühlt es ab? Und fällt genug Regen?
        </p>
        <p class="source">
            Quellen: <a href="https://www.meteoschweiz.admin.ch/service-und-publikationen/publikationen/berichte-und-bulletins/2026/klimabulletin-sommer-2026.html" target="_blank" rel="noopener">MeteoSchweiz, Klimabulletin Sommer 2026</a>,
            <a href="https://tsri.ch/a/zuerich-und-die-schweiz-erlebten-den-heissesten-sommer-seit-messbeginn" target="_blank" rel="noopener">Tsüri.ch</a>
        </p>
    </section>

    <section class="story" aria-labelledby="story-title">
        <h2 id="story-title">Was die Daten zeigen</h2>
        <div class="story-grid">
            <article class="finding">
                <span class="finding-label">Abkühlung</span>
                <span class="finding-value" id="f-cooling">–</span>
                <p id="f-cooling-text">&nbsp;</p>
            </article>
            <article class="finding">
                <span class="finding-label">Spannweite</span>
                <span class="finding-value" id="f-range">–</span>
                <p id="f-range-text">&nbsp;</p>
            </article>
            <article class="finding">
                <span class="finding-label">Regen in Schüben</span>
                <span class="finding-value" id="f-rain">–</span>
                <p id="f-rain-text">&nbsp;</p>
            </article>
        </div>
    </section>
    <section class="heat" id="heat" aria-labelledby="heat-title">
        <h2 id="heat-title">Der Hitzesommer in Zahlen</h2>
        <p class="hint">Berechnet aus den stündlichen Werten in der Datenbank, seit dem 29. Juli.</p>

        <div class="heat-grid">
            <article class="heat-card">
                <span class="heat-value" id="h-days">–</span>
                <h3>Hitzetage</h3>
                <p>Tage mit mindestens 30 °C. Der Körper muss ständig kühlen, das belastet Herz und Kreislauf.</p>
            </article>
            <article class="heat-card">
                <span class="heat-value" id="h-nights">–</span>
                <h3>Tropennächte</h3>
                <p>Nächte, in denen es nicht unter 20 °C abkühlt. Der Körper erholt sich im Schlaf kaum – besonders gefährlich für ältere Menschen.</p>
            </article>
            <article class="heat-card">
                <span class="heat-value" id="h-waves">–</span>
                <h3>Hitzewellen</h3>
                <p id="h-waves-text">Mindestens drei Tage in Folge mit einem Tagesmittel ab 25 °C. Die Belastung summiert sich von Tag zu Tag.</p>
            </article>
        </div>

        <div class="heat-strip" id="heat-strip" aria-label="Tageshöchstwerte seit dem 29. Juli"></div>
        <div class="heat-axis"><span id="h-start">&nbsp;</span><span>heute</span></div>
        <ul class="heat-legend">
            <li><span class="swatch t-cold"></span>unter 15 °C</li>
            <li><span class="swatch t-cool"></span>15–20 °C</li>
            <li><span class="swatch t-mild"></span>20–25 °C</li>
            <li><span class="swatch t-warm"></span>25–30 °C</li>
            <li><span class="swatch t-hot"></span>ab 30 °C (Hitzetag)</li>
            <li><span class="swatch dot"></span>Tropennacht</li>
        </ul>
        <p class="hint" id="h-last">&nbsp;</p>
        <p class="source">
            Definitionen: MeteoSchweiz. Gesundheitsfolgen:
            <a href="https://www.gsi.be.ch/de/start/themen/gesundheit/gesundheitsfoerderung-und-praevention/gesundheit-und-hitze/faq.html" target="_blank" rel="noopener">Kanton Bern, Gesundheit und Hitze</a>.
        </p>
    </section>
    <h2 class="section-title">Im Detail</h2>

    <nav class="range" aria-label="Zeitraum">
        <button data-days="3">3 Tage</button>
        <button data-days="7" class="active">7 Tage</button>
        <button data-days="30">30 Tage</button>
        <button data-days="60">60 Tage</button>
        <button data-days="365">Alles</button>
    </nav>

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

    <p class="status" id="status">Daten werden geladen …</p>

    <section class="card">
        <h2 id="title-temperature">Temperatur</h2>
        <p class="hint" id="hint-temperature">Stündliche Werte in °C</p>
        <div class="chart-box"><canvas id="chart-temperature"></canvas></div>
    </section>

    <section class="card">
        <h2 id="title-rain">Niederschlag pro Tag</h2>
        <p class="hint" id="hint-rain">Summe in Millimetern</p>
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

<script src="script.js?v=2"></script>
</body>
</html>
