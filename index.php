<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wenn die Nacht keine Abkühlung bringt – Zürich Weather Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- Titel -->
<header class="hero" id="hero">
    <div class="hero-sticky">
        <div class="sun">
            <p class="eyebrow">Zürich Weather Tracker · Sommer 2026</p>
            <h1>Wenn die Nacht keine Abkühlung bringt</h1>
        </div>
        <div class="hero-text">
            <p class="lead">
                Im August 2026 leistete ich Zivildienst in einem Altersheim in Zürich.
                Eine Reportage über die Hitze – und darüber, was die Wetterdaten dazu sagen.
            </p>
            <p class="byline">Text, Daten und Umsetzung: Louis Hosali</p>
        </div>
    </div>
</header>

<main>

    <!-- Reportage -->
    <article class="text reportage">
        <p>
            Der Schweiss tropft mir den Rücken hinunter, die langen grauen Hosen kleben an meinen Beinen.
            Erschöpft lehne ich mich an die Küchenablage. Das kalte Glas Wasser in meiner Hand ist schon wieder lauwarm.
            Vor mir legt Herr M. den Stift fürs Kreuzworträtsel auf den Tisch und stützt den Kopf in die Hände.
            Rechts von mir fallen Frau R. immer wieder die Augen zu. Eigentlich möchte sie schlafen, aber nicht in ihrem Zimmer.
            Dort könne sie nicht atmen, sagt sie, sie habe das Gefühl zu ersticken. Sie beschreibt es genau so wie meine Grossmutter.
            In deren Altersheim sind in den letzten zwei Wochen drei Bewohnende gestorben. Hier zum Glück noch niemand.
            Gut geht es aber niemandem.
        </p>
        <p>
            Ich stosse mich von der Ablage ab und schlurfe durch den Gang. Hier ist es etwas erträglicher. Ein wenig.
            Im Büro mache ich einen Boxenstopp, stelle mich vor den Ventilator und fächle mir zusätzlich mit meinem
            hellgrünen T-Shirt Luft zu. Mein Kollege kommt völlig verschwitzt herein. «Ich habe gerade Frau S. gewaschen,
            es ist fast nicht auszuhalten. Keine Ahnung, wie lange ich das noch schaffe.» Ich verstehe ihn kaum,
            zwischen fast jedem Wort nimmt er einen Schluck aus seiner Wasserflasche.
        </p>
        <p>
            Jetzt bin ich an der Reihe, in eines der Zimmer zu gehen. Vorher lege ich mir noch ein Kühltuch um den Nacken.
            Die Abkühlung hält nur kurz. Sobald ich durch den Türrahmen trete, fühlt es sich an, als liefe ich gegen eine Wand.
            Mein Atem wird flacher, ich beginne sofort wieder zu schwitzen. Herr G. begrüsst mich fröhlich und spielt mir
            etwas auf seinem Charango vor. Auch ihm ist es zu heiss, aber wenigstens erinnert ihn die Hitze an seine Heimat.
        </p>
        <p>
            Völlig erschlagen komme ich aus dem Zimmer. Drei Wochen liegen noch vor mir, keine Abkühlung in Sicht.
            Ich kann nur hoffen und mir vorstellen, wie es den Bewohnerinnen und Bewohnern geht.
            Dass sie unter der Hitze leiden, ist offensichtlich.
        </p>
        <p class="question">Ich frage mich: Ab wann wird Hitze gefährlich?</p>
    </article>

    <!-- Überblick: Tagesstreifen -->
    <section class="band band-dark" id="overview">
        <div class="wrap">
            <p class="chapter-no">Überblick</p>
            <h2>Der Sommer auf einen Blick</h2>
            <p class="text-intro">
                Der Sommer 2026 war in der Schweiz der heisseste seit Messbeginn. In Zürich wurden bis zu 38,3 Grad gemessen,
                so viel wie noch nie seit 1864. Seit dem 29. Juli speichert mein Tracker jede Stunde die Temperatur in Zürich.
                Jeder Streifen ist ein Tag, gefärbt nach dem Höchstwert.
            </p>
            <div class="heat-strip" id="heat-strip" aria-label="Tageshöchstwerte vom 29. Juli bis 30. September"></div>
            <div class="heat-axis"><span>29. Juli</span><span>August</span><span>September</span><span>30. September</span></div>
            <ul class="legend">
                <li><span class="swatch t-cold"></span>unter 15 °C</li>
                <li><span class="swatch t-cool"></span>15–20 °C</li>
                <li><span class="swatch t-mild"></span>20–25 °C</li>
                <li><span class="swatch t-warm"></span>25–30 °C</li>
                <li><span class="swatch t-hot"></span>ab 30 °C</li>
                <li><span class="swatch dot"></span>Tropennacht</li>
            </ul>
            <p class="source">
                Quellen: <a href="https://www.meteoschweiz.admin.ch/service-und-publikationen/publikationen/berichte-und-bulletins/2026/klimabulletin-sommer-2026.html" target="_blank" rel="noopener">MeteoSchweiz, Klimabulletin Sommer 2026</a>,
                <a href="https://tsri.ch/a/zuerich-und-die-schweiz-erlebten-den-heissesten-sommer-seit-messbeginn" target="_blank" rel="noopener">Tsüri.ch</a>
            </p>
        </div>
    </section>

    <!-- Kapitel 1: Hitzetage -->
    <section class="chapter" id="chapter-heatdays">
        <div class="text">
            <p class="chapter-no">Kapitel 1</p>
            <h2>Hitzetage</h2>
        </div>
        <div class="figure">
            <div class="big-number"><span id="n-heatdays">21</span> Hitzetage</div>
            <div class="chart-box"><canvas id="chart-heatdays"></canvas></div>
            <p class="caption">Höchsttemperatur pro Tag, 29. Juli bis 30. September. Rot: Hitzetag (ab 30 °C).</p>
        </div>
        <div class="text">
            <p>
                Ein Hitzetag ist ein Tag, an dem es mindestens 30 °C heiss wird. Zwischen Ende Juli und Ende September
                zählte mein Tracker <strong id="n-heatdays-2">21</strong> Hitzetage, <strong id="n-heatdays-aug">16</strong> davon im August –
                genau in den Wochen meines Zivildiensts.
            </p>
            <p>
                Bei solcher Hitze muss der Körper ständig kühlen. Das belastet Herz und Kreislauf. Ältere Menschen sind
                besonders gefährdet: Sie schwitzen später und weniger, spüren weniger Durst und trocknen schneller aus.
                Chronische Krankheiten und manche Medikamente erhöhen das Risiko zusätzlich.
            </p>
            <div class="tip">
                <h3>Was hilft</h3>
                <ul>
                    <li>Körperliche Anstrengung in den heissesten Stunden vermeiden.</li>
                    <li>Regelmässig trinken, mindestens 1,5 Liter pro Tag, auch ohne Durst.</li>
                    <li>Leicht essen: Früchte, Salate, Gemüse.</li>
                </ul>
            </div>
            <p class="source">
                Quellen: <a href="https://www.gsi.be.ch/de/start/themen/gesundheit/gesundheitsfoerderung-und-praevention/gesundheit-und-hitze/faq.html" target="_blank" rel="noopener">Kanton Bern, Gesundheit und Hitze</a>,
                <a href="https://www.bern.ch/mediencenter/medienmitteilungen/aktuell_ptk/es-wird-heiss-tipps-fuer-den-umgang-mit-der-sommerhitze-3/dokumente/drei-goldene-regeln-fur-hitzetage.pdf/download" target="_blank" rel="noopener">BAG, Drei goldene Regeln bei Hitze</a>
            </p>
        </div>
    </section>

    <!-- Kapitel 2: Tropennächte -->
    <section class="chapter band band-sand" id="chapter-nights">
        <div class="text">
            <p class="chapter-no">Kapitel 2</p>
            <h2>Tropennächte</h2>
        </div>
        <div class="figure">
            <div class="big-number"><span id="n-nights">12</span> Tropennächte</div>
            <div class="chart-box"><canvas id="chart-nights"></canvas></div>
            <p class="caption">Tiefsttemperatur pro Tag. Rot: Tropennacht (nicht unter 20 °C).</p>
        </div>
        <div class="text">
            <p>
                Frau R. wollte nicht in ihr Zimmer, weil sie dort keine Luft bekam. Gefährlich wird Hitze vor allem dann,
                wenn auch die Nacht keine Abkühlung bringt. In einer Tropennacht sinkt die Temperatur nicht unter 20 °C.
                Der Körper kann sich im Schlaf kaum erholen, und die Belastung nimmt von Tag zu Tag zu.
            </p>
            <p>
                Mein Tracker zählte <strong id="n-nights-2">12</strong> Tropennächte,
                <strong id="n-nights-aug">10</strong> davon im August.
            </p>
            <div class="tip">
                <h3>Was hilft</h3>
                <ul>
                    <li>Tagsüber Fenster schliessen und mit Vorhängen oder Läden abdunkeln.</li>
                    <li>Nachts und frühmorgens lüften.</li>
                    <li>Den Körper kühlen: Duschen, kalte Tücher auf Stirn und Nacken, kalte Fuss- und Handbäder.</li>
                </ul>
            </div>
            <p class="note">
                Als Nachttemperatur dient hier der tiefste Stundenwert des Tages. Er liegt fast immer in der Nacht oder am frühen Morgen.
            </p>
        </div>
    </section>

    <!-- Kapitel 3: Hitzewellen -->
    <section class="chapter" id="chapter-waves">
        <div class="text">
            <p class="chapter-no">Kapitel 3</p>
            <h2>Hitzewellen</h2>
        </div>
        <div class="figure">
            <div class="big-number"><span id="n-waves">3</span> Hitzewellen</div>
            <div class="chart-box"><canvas id="chart-waves"></canvas></div>
            <p class="caption">Tagesmitteltemperatur. Rot: Tag in einer Hitzewelle. Linien: Warnschwellen von MeteoSchweiz (25 und 27 °C).</p>
        </div>
        <div class="text">
            <p>
                Von einer Hitzewelle spricht MeteoSchweiz, wenn die mittlere Tagestemperatur an mindestens drei Tagen in Folge
                25 °C oder mehr erreicht. Dann warnt der Bund mit Stufe 3, «erhebliche Gefahr». Liegt das Tagesmittel an drei Tagen
                über 27 °C, gilt Stufe 4, «grosse Gefahr».
            </p>
            <p id="waves-text">
                In meinen Daten gab es drei Hitzewellen. Die längste dauerte acht Tage, vom 9. bis 16. August –
                mitten in meinem Zivildienst. Stufe 4 wurde knapp nicht erreicht: Über 27 °C lag das Tagesmittel nie mehr als zwei Tage in Folge.
            </p>
            <ul class="wave-list" id="wave-list"></ul>
            <p class="source">
                Quelle: <a href="https://www.meteoswiss.admin.ch/weather/hazards/explanation-of-the-danger-levels/heat.html" target="_blank" rel="noopener">MeteoSchweiz, Gefahrenstufen Hitze</a>
            </p>
        </div>
    </section>

    <!-- Kapitel 4: Regen -->
    <section class="chapter band band-blue" id="chapter-rain">
        <div class="text">
            <p class="chapter-no">Kapitel 4</p>
            <h2>Und der Regen?</h2>
        </div>
        <div class="figure">
            <div class="big-number"><span id="n-rain">–</span> mm Regen</div>
            <div class="chart-box"><canvas id="chart-story-rain"></canvas></div>
            <p class="caption">Niederschlag pro Tag in Millimetern, 29. Juli bis 30. September.</p>
        </div>
        <div class="text">
            <p id="rain-text">
                Wie viel Regen fiel im Hitzesommer?
            </p>
        </div>
    </section>

    <!-- Schluss -->
    <section class="chapter" id="ending">
        <div class="text">
            <p class="chapter-no">Schluss</p>
            <h2>Was bleibt</h2>
            <p>
                Gegen Ende meines Zivildiensts, am 20. August, regnete es zum ersten Mal wieder
                richtig und etwas länger. Vor allem in der Nacht kühlte es ab: Die Temperatur fiel
                von rund 25 auf 15 Grad. Als ich am nächsten Morgen aus dem Lift kam, merkte ich den
                Unterschied sofort. Ich konnte besser atmen. Meine Chefin begrüsste mich erleichtert,
                ihr Schritt war viel leichter. Am Zmorgentisch sah man auch den Bewohnerinnen und
                Bewohnern die Erleichterung an. Das Wetter war das Thema des Tages. Frau R. machte ihr
                Nachmittagsschläfchen endlich wieder in ihrem eigenen Zimmer, und Herr M. löste sein
                Kreuzworträtsel in Ruhe, ohne alle fünf Minuten eine Pause zu brauchen.
            </p>
            <p>
                Die Erleichterung auf dem Stock zeigte, wie sehr die Hitze alle dort an ihre Grenzen
                gebracht hatte. Ab wann Hitze gefährlich wird, lässt sich nicht an einer einzigen Zahl
                festmachen. Auch MeteoSchweiz warnt nicht wegen eines einzelnen heissen Nachmittags,
                sondern erst, wenn die Tagesmitteltemperatur an mindestens drei Tagen in Folge 25 Grad
                erreicht. Meine Daten zeigen dasselbe Muster. Ein heisser Tag allein ist auszuhalten.
                Schwierig wird es, wenn die Hitze bleibt: wenn auf einen Hitzetag eine Tropennacht folgt,
                und dann noch eine und noch eine. Genau das geschah im August. 16 der 21 Hitzetage und
                10 der 12 Tropennächte fielen in diesen einen Monat. Die längste Hitzewelle dauerte acht
                Tage, vom 9. bis 16. August. Für Menschen, deren Körper sich nicht mehr so gut anpasst,
                gab es in dieser Zeit kaum eine Pause.
            </p>
            <p>
                Nach dem 20. August zählte mein Tracker noch einige Hitzetage, den letzten am
                8. September, aber keine einzige Tropennacht mehr. Die Tage blieben heiss, doch die
                Nächte brachten wieder Abkühlung.
            </p>
            <p>
                Was die Hitze mit den Bewohnerinnen und Bewohnern genau gemacht hat, zeigen meine Daten
                nicht. Sie messen die Temperatur, nicht die Müdigkeit, den Durst oder die Unruhe in den
                Nächten. Das habe ich gesehen, nicht gemessen. Aber die Zahlen zeigen, wie lange dieser
                Zustand dauerte. Und sie zeigen, dass die gefährlichste Zeit nicht der heisseste
                Nachmittag war, sondern die Nacht danach.
            </p>
            <p>
                Der Sommer 2026 war der heisseste seit Messbeginn. Es wird nicht der letzte dieser Art
                bleiben. Mein Tracker sammelt weiter, jede Stunde ein neuer Wert. Vielleicht zeigt er im
                nächsten Sommer schon früher, wann es wieder so weit ist.
            </p>
            <p class="question">
                Wenn die Nacht keine Abkühlung bringt, braucht es Menschen, die es tun.
            </p>
        </div>
    </section>

    <!-- Live-Ansicht -->
    <section class="band band-live" id="live">
        <div class="wrap">
            <p class="chapter-no">Live</p>
            <h2>Und jetzt? Die aktuellen Daten</h2>
            <p class="text-intro">
                Der Tracker sammelt weiter: Jede Stunde kommt ein neuer Wert dazu.
                Hier lässt sich der Zeitraum selbst wählen.
            </p>

            <nav class="range" aria-label="Zeitraum">
                <button data-days="3">3 Tage</button>
                <button data-days="7" class="active">7 Tage</button>
                <button data-days="30">30 Tage</button>
                <button data-days="60">60 Tage</button>
                <button data-days="365">Alles</button>
            </nav>

            <div class="kpis">
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
            </div>

            <p class="status" id="status">Daten werden geladen …</p>

            <div class="card">
                <h3 id="title-temperature">Temperatur</h3>
                <p class="hint" id="hint-temperature">Stündliche Werte in °C</p>
                <div class="chart-box"><canvas id="chart-temperature"></canvas></div>
            </div>

            <div class="card">
                <h3 id="title-rain">Niederschlag pro Tag</h3>
                <p class="hint" id="hint-rain">Summe in Millimetern</p>
                <div class="chart-box"><canvas id="chart-rain"></canvas></div>
            </div>
        </div>
    </section>

    <!-- Methode -->
    <section class="wrap method" id="method">
        <h2>Methode und Grenzen</h2>
        <div class="method-grid">
            <div>
                <h3>Sammeln</h3>
                <p>Ein Cronjob ruft jede Stunde <code>etl/load.php</code> auf. Das Skript holt die Daten von der
                   API von Open-Meteo, bereinigt sie und speichert sie in einer MySQL-Datenbank.</p>
            </div>
            <div>
                <h3>Grenzen</h3>
                <p>Die Werte sind Modelldaten von Open-Meteo für einen Punkt in Zürich, keine Messung einer
                   einzelnen Wetterstation. Extremwerte fallen darum etwas tiefer aus als gemessen (36,8 statt 38,3 °C).
                   Die Daten beginnen am 29. Juli, der Juni und Anfang Juli fehlen. Gesundheitsdaten habe ich keine:
                   Die Seite zeigt die Hitze, nicht ihre Folgen.</p>
            </div>
        </div>

        <h3 class="audit-title">Bisher gesammelt</h3>
        <div class="audit">
            <div class="audit-tile"><span class="audit-value" id="m-runs">–</span><span class="audit-label">Durchläufe</span></div>
            <div class="audit-tile"><span class="audit-value" id="m-total">–</span><span class="audit-label">Stunden gespeichert</span></div>
        </div>
        <p class="hint" id="m-range">&nbsp;</p>

        <h3 class="audit-title">Letzter Durchlauf <span id="m-last">–</span></h3>
        <div class="audit">
            <div class="audit-tile"><span class="audit-value" id="m-input">–</span><span class="audit-label">Stunden geholt</span></div>
            <div class="audit-tile"><span class="audit-value" id="m-future">–</span><span class="audit-label">Prognosen entfernt</span></div>
            <div class="audit-tile"><span class="audit-value" id="m-missing">–</span><span class="audit-label">ohne Temperatur verworfen</span></div>
            <div class="audit-tile"><span class="audit-value" id="m-invalid">–</span><span class="audit-label">unmögliche Werte auf null</span></div>
            <div class="audit-tile audit-new"><span class="audit-value" id="m-inserted">–</span><span class="audit-label">neu gespeichert</span></div>
        </div>
    </section>

</main>

<footer>
    <div class="wrap">
        <p>Louis Hosali, Einzelprojekt IM3, FHGR</p>
        <p>
            Daten: <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a> (CC BY 4.0),
            Standort Zürich (47.38 N, 8.54 E)
        </p>
    </div>
</footer>

<script src="script.js?v=5"></script>
</body>
</html>
