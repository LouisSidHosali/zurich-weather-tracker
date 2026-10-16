# Wenn die Nacht keine Abkühlung bringt – Zürich Weather Tracker

**Einzelprojekt** von Louis Hosali im Modul Interaktive Medien 3 (HS26, FHGR).

> **Spezialauftrag:** IM3 muss vor dem Start von IM5 abgeschlossen sein. Deshalb habe ich das Projekt alleine und in verkürzter Zeit umgesetzt (Abgabe 17.10.2026). Backend (Extract, Transform, Load, Unload) und Frontend stammen beide aus diesem Einzelprojekt. Der Schwerpunkt liegt auf dem technischen Teil.

- **Live-Website:** https://avoluwiz.myhostpoint.ch
- **Frage:** Wie entwickeln sich Temperatur und Niederschlag in Zürich über die Zeit – und wann wird Hitze gefährlich?
- **Datenquelle:** [Open-Meteo Forecast API](https://open-meteo.com/en/docs) (kostenlos, ohne API-Key, CC BY 4.0)

---

## Kurzbeschreibung

Im Hitzesommer 2026 leistete ich Zivildienst in einem Zürcher Altersheim und sah, wie sehr die Bewohnenden unter der Hitze litten. Da stellte sich für mich die Frage: Wann wird Hitze gefährlich? Ich nutze die gesammelten Daten umd genau dieser Frage nachzugehen. Ein Cronjob holt jede Stunde Wetterdaten von Open-Meteo, PHP-Skripte bereinigen sie und speichern sie in einer MySQL-Datenbank. Die Website verbindet meine Reportage mit Grafiken zu Hitzetagen, Tropennächten, Hitzewellen und Regen – und zeigt in einer Live-Ansicht, wie es weitergeht.

---

## Screenshots

| Desktop | Mobile |
|---|---|
| ![Desktop](docs/screenshot-desktop.png) | ![Mobile](docs/screenshot-mobile.png) |

## Marktstand (13.10.2026, Zürich)

![Marktstand](docs/marktstand.jpg)

![Skizze des Datenflusses](docs/skizze-datenfluss.jpg)

Am Marktstand zeigte ich eine erste Version mit Temperatur- und Regendiagrammen und erklärte den Datenfluss anhand der Skizze. Diese erste Version war noch sehr rudimentär und noch nicht im Stil der finalen Version. Die Rückmeldungen halfen aber sehr, mich in meinen Ideen zu bestätigen und neue Anstösse zu geben. Wichtige Punkte waren mehr Geschichte, mehr Luft im Design und den Code selbst besser verstehen. Im Projektverlauf bin ich nochmals detailliert darauf eingegangen. 

---

## Aufbau der Seite

1. **Titel und Reportage** – meine Erfahrungen aus dem Zivildienst in einem Altersheim im August 2026 (Namen anonymisiert).
2. **Überblick** – ein Farbstreifen pro Tag (29.7.–30.9.), gefärbt nach dem Tageshöchstwert, Punkte für Tropennächte.
3. **Kapitel 1–4** – Hitzetage, Tropennächte, Hitzewellen, Regen. Jedes Kapitel: grosse Zahl, Grafik, Erklärung, Tipps und Quellen.
4. **Live-Ansicht** – aktuelle Werte mit wählbarem Zeitraum (3 Tage bis alles).
5. **Methode und Grenzen** – wie gesammelt und bereinigt wird, mit Live-Audit des letzten Durchlaufs.

**Entscheid:** Die Grafiken in den Kapiteln zeigen einen **festen Zeitraum** (29.7.–30.9.), damit Text und Zahlen immer übereinstimmen. Aktuelle Daten zeigt die separate Live-Ansicht.

---

## Datenfluss

```
Open-Meteo API
     │  (cURL, JSON)
     ▼
etl/extract.php ──► etl/transform.php ──► etl/load.php ──► MySQL (Hostpoint)
                                              ▲                   │
                                     Cronjob, jede Stunde         ▼
                                       um xx:05             unload.php (JSON)
                                                                  │
                                                                  ▼
                                                    script.js + Chart.js im Browser
```

| Datei | Aufgabe |
|---|---|
| `etl/extract.php` | Holt per cURL die stündlichen Werte der letzten 2 Tage (plus heute) für Zürich von Open-Meteo. |
| `etl/transform.php` | Bereinigt: entfernt Prognosen und Duplikate, setzt unmögliche Werte auf `null`, verwirft Stunden ohne Temperatur, benennt Felder um, zählt alles im Audit. |
| `etl/load.php` | Speichert neue Stunden mit `INSERT IGNORE` und protokolliert den Durchlauf in `etl_runs` – in einer Transaktion. |
| `etl/check_transform.php` | Kontrollansicht: Resultat von transform.php als JSON, ohne zu speichern. |
| `etl/export_fallback.php` | Schreibt eine Kopie der letzten 60 Tage nach `data/fallback.json`. |
| `etl/schema.sql` | SQL zum Anlegen der Tabellen. |
| `lib/queries.php` | Datenbank-Abfragen: stündlich, täglich, Status und Hitze-Kennzahlen (`fetchHeat`). |
| `unload.php` | JSON-Endpunkt: `view=hourly`, `daily`, `status`, `heat`; Parameter `days`. |
| `index.php`, `script.js`, `style.css` | Frontend: Reportage, Kapitel mit Grafiken, Live-Ansicht. |
| `data/fallback.json` | Notfall-Kopie, falls die Datenbank nicht antwortet. |
| `config.template.php` | Vorlage für die Zugangsdaten. Die echte `config.php` steht in `.gitignore`. |

---

## So funktioniert der Code

### Backend

- **`extract.php` / `fetchJson()`** – Ruft mit cURL die API von Open-Meteo auf. Die  Antwort wird mit `json_decode` in ein PHP-Array umgewandelt. Falls keine Antwort oder kein gültiges JSON zurückkommt, bricht das Skript mit einem Fehler ab, damit nichts Falsches gespeichert wird.
- **`transform.php` / `cleanNumber()`** – Prüft jeden Messwert auf 'unmögliche' Werte, wie z. B. Temperatur −40 bis 50 °C. Sie bilden eine Grenze und wenn der Messwert ausserhalb dieser Grenzen liegt, wird er `null` und im Audit gezählt. So kann ich sicherstellen, dass keine unmöglichen Werte in der Datenbank landen.
- **`load.php`** – Die bereinigten Stundendaten werden mit INSERT IGNORE in der Datenbank gespeichert. Da measured_at als UNIQUE definiert ist, werden doppelte Einträge automatisch übersprungen. Die Speicherung erfolgt innerhalb einer Transaktion. Falls ein Fehler auftritt, werden alle Änderungen rückgängig gemacht. So werden keine unvollständigen Daten gespeichert.
- **`lib/queries.php` / `fetchDaily()`** – Fasst mit SQL (`GROUP BY DATE(...)`) die Stunden zu Tagen zusammen: Minimum, Maximum, Mittel und Regensumme. Diese Tageswerte brauchen die Regen- und Kapitelgrafiken.
- **`lib/queries.php` / `fetchHeat()`** – Jeder vollständige Tag wird dadurch als Hitzetag (Maximum ≥ 30 °C), Tropennacht (Minimum ≥ 20 °C) und heissen Tag (Mittel ≥ 25 °C) markiert. Und eine Schleife sucht danach Serien von mindestens drei heissen Tagen in Folge – das sind Hitzewellen nach MeteoSchweiz.
- **`unload.php`** – Hier ist der Endpunkt für das Frontend. Je nach Parameter `view` ruft er die passende Abfrage auf und gibt das Ergebnis als JSON aus. Unbekannte Werte lehnt er mit Fehler 400 ab.

### Frontend

- **`loadData()`** – Die stündlichen und täglichen Werte für die Live-Ansicht werden durch den 'loadData()' angeholt. Wenn der der Server nicht antwortet, lädt sie die Notfall-Kopie `data/fallback.json`.
- **`loadStory()`** – Hier werden die Hitze- und Tageswerte für die Kapitel geholt und filtert sie auf den festen Zeitraum vom 29. Juli bis 30. September. Bei einem Ausfall nimmt auch sie die Daten aus `fallback.json`.
- **`storyChart()`** – Das ist eine gemeinsame Funktion für alle Kapitelgrafiken: Sie zeichnet Balken mit Chart.js und auf Wunsch gestrichelte Grenzlinien (30, 20, 25 und 27 °C). So muss auch nicht jede Grafik einzeln programmiert werden.
- **`renderStrip()` / `temperatureClass()`** – Baut den Farbstreifen im Überblick: Für jeden Tag entsteht ein Element. Seine Farbe `temperatureClass()` wird aus dem Höchstwert bestimmt. Tropennächte erhalten einen Punkt.
- **`renderRain()`** – Es berechnet die Regensumme, sucht den grössten Temperatursturz von einem Tag auf den nächsten und die längste Phase ohne Regen. Daraus wird dann der Text im Kapitel 4 geschrieben.

### Die Frage vom Marktstand: Passt sich die Schlagzeile automatisch an?

Am Marktstand lautete die Schlagzeile «Zürich ist seit Ende Juli um 13 Grad kühler geworden». Die Frage war, ob sich die Zahl mit der Zeit automatisch an die neuen Daten anpasst und der Titel entsprechend mitwechselt. Ich habe mit 'Ja' geantwortet. Das stimmte zwar, aber ich konnte die Stelle im Code nicht zeigen.

**Wo es passierte:** Es passierte nicht in der Datenbank und nicht in PHP, sondern im Browser, in `script.js` in der damaligen Funktion `renderStory()`. Der Ablauf war:

1. Der Cronjob holt jede Stunde neue Werte und speichert sie in der Datenbank (`etl/load.php`).
2. Beim Öffnen der Seite ruft `renderStory()` alle Tageswerte über `unload.php?view=daily&days=365` ab. Darin sind also immer auch die neuesten Tage.
3. Die Funktion nimmt die **ersten sieben** Tage (Ende Juli, bleiben immer gleich) und die **letzten sieben vollständigen** Tage (verschieben sich jeden Tag nach hinten) und berechnet von beiden den Durchschnitt der Tagesmitteltemperatur.
4. Die Differenz wird gerundet und mit `setText('headline', …)` in den Titel geschrieben.

**Der alte Code** (Stand so vor dem Marktstand, `script.js`):

​```js
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
    // ...
}
​```

**Was «automatisch» hier  genau heisst:** Die Zahl wurde bei jedem Laden der Seite neu berechnet, ohne dass ich etwas am Code ändern musste. Weil nur vollständige Tage zählen (mindestens 20 Stunden Messwerte), änderte sie sich höchstens einmal pro Tag, obwohl der Cronjob jede Stunde neue Werte speichert. Das heisst, solange die Seite offen ist, bleibt die Zahl gleich. Dafür muss man neu laden. Lag die Differenz zwischen −1 und +1 Grad, blieb der Titel unverändert.

**Warum es die Schlagzeile nicht mehr gibt:** Der Vergleich startete immer mit den ersten Tagen Ende Juli. Je weiter es in den Herbst ging, desto grösser wurde die Differenz tendenziell. Die Zahl beschrieb also vor allem den normalen Übergang vom Sommer in den Herbst und keinen besonderen Befund. Deshalb habe ich die Geschichte auf die Hitze umgestellt. In den Kapiteln zeige ich einen festen Zeitraum (29.7.–30.9.), damit Text und Zahlen übereinstimmen.

---

## Datenmodell

| Tabelle | Eine Zeile bedeutet |
|---|---|
| `weather_hourly` | eine Stunde Wetter in Zürich |
| `etl_runs` | ein Durchlauf von load.php mit seinem Audit |

`weather_hourly`:

| Spalte | Typ | Bemerkung |
|---|---|---|
| id | INT, PK, AUTO_INCREMENT | |
| measured_at | DATETIME, UNIQUE | verhindert doppelte Stunden |
| temperature_c | DECIMAL(4,1) | Pflichtfeld |
| precipitation_mm | DECIMAL(5,1) | darf null sein |
| humidity_pct | TINYINT | darf null sein |
| wind_speed_kmh | DECIMAL(5,1) | darf null sein |

### Definitionen (MeteoSchweiz)

| Begriff | Regel im Code |
|---|---|
| Hitzetag | Tageshöchstwert ≥ 30 °C |
| Tropennacht | Tagestiefstwert ≥ 20 °C (Annäherung: tiefster Stundenwert des Tages) |
| Hitzewelle | mindestens 3 Tage i Folge mit Tagesmittel ≥ 25 °C (Warnstufe 3) |

## Datenvertrag (unload.php)

`unload.php?view=hourly&days=7`
```json
[{ "measured_at": "2026-10-13 12:00:00", "temperature_c": 13.4, "precipitation_mm": 0, "humidity_pct": 66, "wind_speed_kmh": 2.3 }]
```

`unload.php?view=daily&days=30`
```json
[{ "date": "2026-10-05", "temp_min_c": 15.6, "temp_max_c": 23, "temp_avg_c": 18.4, "precipitation_mm": 0.3, "hours": 24 }]
```

`unload.php?view=heat`
```json
{ "heat_days": 21, "tropical_nights": 12, "last_heat_day": "2026-09-08",
  "heatwaves": [{ "from": "2026-08-09", "to": "2026-08-16", "days": 8, "temp_max_c": 36.1 }],
  "days": [{ "date": "2026-08-03", "temp_max_c": 36.8, "temp_min_c": 21.7, "temp_avg_c": 28.5,
             "heat_day": true, "tropical_night": true, "hot_mean": true }] }
```

`unload.php?view=status`
```json
{ "total_hours": 1836, "first_measured_at": "2026-07-29 01:00:00", "last_measured_at": "2026-10-13 12:00:00", "runs": 147,
  "last_run": { "run_at": "2026-10-13 12:05:17", "input_hours": 72, "future_hours": 11, "duplicates": 0,
                "invalid_values": 0, "missing_temperature": 0, "output_rows": 61, "inserted_rows": 1 } }
```

---

## Projektverlauf

Dieses Projekt stellte mich gleich zu Beginn vor einige Herausforderungen. Da ich von Montag bis Samstag arbeite, wusste ich, dass ich nicht viel Zeit haben würde. Zur Zeit der Themenfindung war der Cornell-Fall in aller Munde, und ich wollte eigentlich gerne etwas zu sexuellen Übergriffen in der Schweiz machen. Ich merkte aber schnell, dass mir die Zeit fehlte, um ein so grosses und wichtiges Thema angemessen anzugehen. Also reduzierte ich das Ganze auf ein einfacheres Thema: das Wetter. Fairerweise war auch das in aller Munde, denn der Sommer war extrem heiss. Da ich im August meinen Zivildienst in einem Altersheim leistete und selbst sah, welche Auswirkungen die heissen Tage auf die Bewohnenden hatten, entschied ich mich für diese Richtung. Einerseits wollte ich die Wetterdaten dieses Sommers und der kommenden Zeit sammeln, um ein Beobachtungstool zu erstellen. Dieses sollte als Hilfsmittel dienen, um Aussagen über die momentane Wetterlage zu machen. Andererseits wollte ich die gesammelten Daten nutzen, um über die Auswirkungen von Hitzetagen zu schreiben – allgemein, vor allem aber auf ältere Menschen.

Die erste Hürde, die Themenwahl, war damit überwunden. Doch schon kam die nächste: Da mir in einem Semester die Teilnahme am Modul verweigert wurde und ich das Modul danach aus familiären Gründen nochmals nicht bestand, lag die ganze IM-Thematik für mich schon ziemlich lange zurück. Neben dem Projekt selbst musste ich also auch das IM-Material nochmals von vorne durchgehen. Für mich war klar, dass ich einen genauen Zeitplan brauchte. Ich begann zu arbeiten, Skizzen zu machen und Texte zu schreiben. Da ich noch keine Studierendenkarte hatte, um mich zu verifizieren, mietete ich ein Webhosting bei Hostpoint. Doch schnell holte mich die nächste Hürde ein: meine Gesundheit. Eine mehrwöchige Grippe liess meinen Zeitplan völlig auseinanderfallen. Und dann stand auch schon der Marktstand vor der Tür, und ich hatte ausser meinen Plänen noch fast nichts.

Da ich am Marktstand trotzdem etwas zeigen und meine Ideen besprechen wollte, programmierte ich mit Claude Code direkt auf dem Hostpoint-Server eine erste Version der Webseite. Ich ging aber unzufrieden an den Marktstand. Es fehlte noch vieles von dem, was ich umsetzen wollte, und es sah noch nicht so aus, wie ich es mir vorgestellt hatte. Das UX-Design war noch sehr schwach: Die Schrift hob sich kaum vom Hintergrund ab, die Abschnitte waren viel zu dicht gedrängt, alles war noch sehr rudimentär. Ausserdem erzählte die Seite noch keine journalistische Geschichte. Zwar hatte ich einen angepassten Auftrag, bei dem dieser Teil weniger wichtig war, trotzdem war es noch zu wenig. Auch die Daten zu den Hitzetagen konnte ich noch nicht integrieren, und die Ansichten waren sehr minimal. Für mehr reichte die Zeit leider nicht.

Trotzdem bin ich froh, dass ich mit diesem ersten Produkt an den Marktstand gehen konnte, denn ich bekam viele wertvolle Rückmeldungen. Ich wurde darin bestärkt, den persönlichen, reportageähnlichen Stil einzubauen, damit das Projekt seinen journalistischen Aspekt erhält. Zur Darstellung bekam ich die Rückmeldung, dass man einige Grafiken zusammenfassen könnte und auf jeden Fall mehr Abstand zwischen den Abschnitten braucht. Allgemein sollte die Seite übersichtlicher werden, sie war noch sehr simpel. Bei der technischen Rückmeldung hiess es dasselbe: noch sehr simpel, da musste ich unbedingt nochmals dahinter. Ich merkte auch schnell, dass ich zwar den generellen Ablauf und die Funktionen der einzelnen Skripte erklären konnte, den Code selbst aber noch nicht im Detail verstanden hatte und die Übersicht verlor. Hier spürte ich die verlorene Zeit sehr, denn ich hatte den Code noch nicht selbst durchgehen und in VS Code aufsetzen können. Einzelne Codezeilen fand ich nicht, weil ich noch zu wenig damit vertraut war. Eine grosse und faire Kritik war deshalb auch, dass ich den Code direkt auf dem Server durch Claude Code hatte aufsetzen lassen. Mein Auftrag für die letzten Tage war damit klar: einerseits das UX-Design und den journalistischen Teil verbessern und so fertigstellen, wie ich es geplant hatte; andererseits den Code in VS Code übernehmen, um alles genau durchzugehen, mich damit vertraut zu machen und ihn weiter auszubauen.

Nach dem Marktstand holte ich das ganze Projekt von GitHub in VS Code. Das war schwieriger als gedacht. Zuerst blockierte der «Restricted Mode» Git, dann klappte die Verbindung zum Server nicht: Die SFTP-Erweiterung meldete immer wieder «authentication failed». Mit der Zeit fand ich heraus, dass Hostpoint zwei verschiedene Passwörter verwendet, eines für das Kundenkonto und eines für das Hosting. Ausserdem hatte die Erweiterung selbst einen Fehler. Ich testete die Verbindung deshalb direkt im Terminal mit `ssh`. Als das funktionierte, lud ich die Dateien mit `scp` statt über die Erweiterung hoch. Beim ersten Push landete mein Commit zudem in einem zweiten GitHub-Konto, das ich noch von früher hatte. Dort legte VS Code automatisch eine Kopie des Repositorys an. Ich erkannte das Problem schlussendlich mit `git remote -v`, korrigierte die Verbindung und meldete mich mit dem richtigen Konto an. Seither läuft der Ablauf so: in VS Code ändern, per `scp` hochladen, im Browser testen, committen.

Inhaltlich stellte ich die Seite ganz auf das Thema Hitze um. Zuerst wollte ich das Ganze ja noch mehr als Beobachtungstool nutzen. Diesen Aspekt habe ich nach dem Marktstand aber in den hintergrund gestellt. Dafür die Daten noch mehr in den geplanten Reportage-Teil eingebettet. Am Anfang steht jetzt meine eigene Reportage aus dem Zivildienst im August; die Namen der Bewohnenden und des Altersheim selbst habe ich anonymisiert. Danach folgen ein Überblick mit einem Farbstreifen für jeden Tag und vier Kapitel: Hitzetage, Tropennächte, Hitzewellen und Regen. Jedes Kapitel enthält eine Grafik, eine Erklärung, was die Hitze für den Körper bedeutet, sowie Tipps und Quellen. Dafür brauchte es eine neue Abfrage, `fetchHeat()` in `lib/queries.php`. Sie berechnet aus den stündlichen Werten für jeden Tag, ob es ein Hitzetag oder eine Tropennacht war, und sucht Hitzewellen nach der Definition von MeteoSchweiz. Die Kapitel zeigen bewusst einen festen Zeitraum vom 29. Juli bis 30. September, damit Text und Zahlen übereinstimmen. Die aktuellen Daten gibt es in einer separaten Live-Ansicht. Auch das Design ist neu: mehr Abstand, eigene Schriften für Titel und Text sowie Gelb und Dunkelblau als Farben.

Den Code habe ich mit Unterstützung von Claude entwickelt. Die erste Version für den Marktstand liess ich direkt auf dem Server erstellen. So hatte ich wenigstens schon etwas zum zeigen und besprechen. Danach arbeitete ich anders: in VS Code, mit Git und Schritt für Schritt. Kleinere Teile wie `fetchHeat()` und den Hitze-Abschnitt habe ich mit Anleitung selbst eingefügt und angepasst. Grössere Umbauten wie das neue Design, waren dann  umständlicher. Ich bin meine Skizzen und Notizen nochmals durchgegangen und hab so den Code Abschnitt für Abschnitt mithilfe von Claude Code und  habe ich als ganze Dateien übernommen und im Vergleich in VS Code durchgesehen. Thema, Fragestellung, Reportage, Datenauswahl, Aufbau der Geschichte und alle inhaltlichen Entscheide stammen von mir. Die wichtigsten Funktionen erkläre ich im Abschnitt «So funktioniert der Code».

Die Seite erzöhlt jetzt eine Geschichte und sammelt gleichzeitig weiter Daten. Inzwischen verstehe ich den Code besser und finde mich darin zurecht. Den grössten Lernschritt hat mir der Marktstand gebracht: Dort habe ich gemerkt, wo meine Design- & Wissenslücken sind. So konnte ich das weitere Vorgehen auch nochmals besser angehen. Ein grosser Unterschied war, dass  Änderungen in kleineren Schritten einbaute. Jetzt hatte ich auch wieder ein wenig mehr Zeit. 

Inhaltlich hab ich einfach noch einmal gesehen, dass eine Zahl nicht automatisch zur Geschichte wird, nur weil sie sich berechnen lässt. Die alte Schlagzeile war technisch korrekt, sagte aber wenig aus, weil sie vor allem den normalen Übergang in den Herbst beschrieb. Erst als ich die Daten mit meiner eigenen Erfahrung im Altersheim verbunden habe, wurde daraus eine Frage, die mich wirklich interessiert und einen journalistischen Wert hatte: Wann wird Hitze gefährlich, und warum sind die Nächte so wichtig? Dabei habe ich auch nochmals gelernt Begriffe wie Hitzetag, Tropennacht und Hitzewelle sauber zu definieren, was auch bei der Datendartellung half. Und auch offen zu sagen, was meine Daten nicht zeigen können. Weil das half mir, und auch den Lesenden, die Daten nochmals besser einzuordnen. 

Technisch habe ich vor allem gelernt, wie die einzelnen Teile zusammenspielen, wie der Flow funktioniert: Die API liefert Rohdaten, PHP bereinigt und speichert sie, SQL fasst sie zusammen, und JavaScript macht daraus Grafiken. Die Rohdaten muss man immer zuerst anschauen, denn die API lieferte auch Prognosen und leere Werte, die ich herausfiltern musste. auch ging ich am Anfang alles in grossen Schritten an, was mich ziemlich überforderte. Da lernte ich schnell, dass die Probleme kleiner werden, wenn ich sie Schritt für Schritt eingrenze: zuerst die Verbindung testen, dann das Werkzeug. So oft lag der Fehler an einem Leerzeichen in der `.gitignore` oder am Browser-Cache und nicht am Code.

Blöd war auch, dass ich durch die Grippe so viel Zeit verlor. Dadurch kam ich in einen ziemlichen Zeitstress, weshalb ich direkt im Server gearbeitet habe; mit dem Gedanken, dass ich die Arbeit im VS Code nachholen kann. Das raubte mir aber einfach im Nachhinein Zeit, weil ich alles nochmals kontrollieren und suchen musste. Das nächste Mal muss ich mich auf jeden Fall nicht beeindrucken lassen vom Zeitstress und von Anfang im VS Code arbeiten. Was auch geholfen hätte, die Fragestellung früher und vor allem konkreter festzulegen, damit Daten, Grafiken und Text von Anfang an auf dasselbe Ziel hinarbeiten. So bin ich jetzt immer wieder in eine leicht andere Richtung gegangen, wodurch ich den Code natürlich immer wieder neu anlegen musste.

### Übersicht

| Datum | Schritt |
|---|---|
| bis 6.10. | Thema gewählt, Datenquelle geprüft (Open-Meteo), Fragestellung formuliert. |
| 7.10. | Hosting bei Hostpoint, Datenbank in phpMyAdmin, ETL-Kette, einmaliges Auffüllen (92 Tage) und Cronjob. |
| 7.–12.10. | Unload-Endpunkt, Frontend mit Chart.js, Fallback-Datei, erste journalistische Ebene (Befunde, Diagrammtitel, Erfahrungen aus dem Zivi). |
| 13.10. | Marktstand in Zürich, Rückmeldung der Dozierenden. |
| 14.10. | Umstieg auf VS Code, Git und Upload per `scp`. Neue Abfrage `fetvhHeat()` und `view=heat`. |
| 15.10. | Reportage aus dem Zivildienst, neuer Aufbau mit Kapiteln, neues Design. |
| 16.–17.10. | Code-Tour, README, Screenshots, Abgabe. |

---

## Schwierigkeiten

| Problem | Lösung | Learning |
|---|---|---|

| Beim einmaligen Auffüllen mit `past_days=92` lieferte die API für die ältesten rund drei Wochen nur leere Werte (529 Stunden). | Transform verwirft Stunden ohne Temperatur und zählt sie im Audit. Weil es für diese Wochen keine Daten gibt, habe ich die Zeitraum-Auswahl auf 60 statt 90 Tage umgestellt. | Ich muss die Rohdaten zu Beginn genauer anschauen, statt sie blind zu übernehmen. |

| Die API liefert nicht nur vergangene Stunden, sondern mischt auch Prognosen für die kommenden Stunden darunter. Ohne Filter wären vorhergesagte Werte in der Datenbank als Messungen gelandet. | In `transform.php` vergleiche ich jede Stunde mit der aktuellen Zeit und verwerfe alle, die in der Zukunft liegen. Wie viele es waren, steht im Audit. | Ich muss klar trennen, was gemessen und was vorhergesagt ist. Sonst stimmen die Befunde nicht. |

| PDO gibt Werte aus DECIMAL-Spalten als Text zurück, zum Beispiel `"24.70"` statt `24.7`. Chart.js und meine Berechnungen brauchen aber Zahlen. | Ich habe in `lib/queries.php` die Hilfsfunktion `toFloat()` geschrieben, die jeden Wert in eine Zahl umwandelt oder `null` lässt, wenn er fehlt. | Im Datenvertrag muss ich festlegen, welchen Typ jedes Feld hat, und nicht davon ausgehen, dass die Datenbank das richtig liefert. |

| Zu Beginn habe ich die Dateien direkt im Datei-Explorer von Hostpoint bearbeitet. Das war umständlich, Änderungen wurden nicht immer gespeichert, und ich hatte keine Übersicht, was ich geändert hatte. | Nach dem Marktstand bin ich auf VS Code umgestiegen. Ich arbeite lokal, sehe die Änderungen im Vergleich, sichere sie mit Git und lade sie mit `scp` auf den Server. | Ein sauberer Arbeitsablauf spart Zeit und macht nachvollziehbar, was ich wann geändert habe. |

| Die SFTP-Erweiterung in VS Code meldete immer «authentication failed». Es gab zwei verschiedene Passwörter bei Hostpoint (Hostpoint-ID und Hosting-Konto), und die Erweiterung hatte zusätzlich einen eigenen Fehler. | Ich habe das Passwort für das Hosting-Konto neu gesetzt, die Verbindung zuerst im Terminal mit `ssh` getestet und danach mit `scp` statt mit der Erweiterung hochgeladen. | Fehler muss ich eingrenzen: zuerst die Grundverbindung testen, dann das Werkzeug. So finde ich heraus, wo das Problem wirklich liegt. |

| Die `.gitignore` griff nicht, weil die Zeilen mit einem Leerzeichen begannen. Dadurch hätte die `config.php` mit dem Datenbank-Passwort auf GitHub landen können. | Ich habe die Leerzeichen entfernt und geprüft, dass `config.php` nicht mehr unter den Änderungen auftaucht. | Schon ein kleiner Formatfehler kann eine Sicherheitsregel aushebeln. Ich muss prüfen, ob sie wirklich wirkt. |

| Der erste Push ging an ein zweites GitHub-Konto. VS Code legte dort automatisch einen Fork an und vertauschte die Verbindungen (`origin` und `upstream`). | Ich habe mit `git remote` die Verbindungen korrigiert, mich mit dem richtigen Konto angemeldet und erneut gepusht. | Mit `git remote -v` prüfe ich vor dem Push, wohin meine Änderungen gehen und mit welchem Konto ich angemeldet bin. |

| Nach dem Upload zeigte der Browser weiterhin die alten CSS- und JS-Dateien. Der neue Abschnitt erschien ohne Gestaltung und ohne Zahlen. | Ich hänge eine Versionsnummer an die Dateien (`style.css?v=3`) und erhöhe sie bei jeder Änderung. So lädt der Browser die neue Datei. | Beim Testen muss ich an den Browser-Cache denken, bevor ich den Fehler im Code suche. |

| Die alte Schlagzeile «Zürich ist seit Ende Juli um 13 Grad kühler geworden» war technisch korrekt, beschrieb aber nur den normalen Übergang in den Herbst. | Ich habe die Geschichte auf die Hitze umgestellt und zeige in den Kapiteln einen festen Zeitraum (29.7.–30.9.), damit Text und Zahlen übereinstimmen. | Eine Zahl wird nicht automatisch zur Geschichte. Befunde müssen zur Fragestellung passen. | |

---

## Learnings

- **ETL in Schritten denken:** Wenn Holen, Bereinigen und Speichern getrennt sind, finde ich Fehler viel schneller, weil ich jeden Schritt einzeln testen kann.
- **Rohdaten zuerst anschauen:** Die API lieferte Prognosen und leere Werte. Hätte ich sie blind übernommen, wären meine Zahlen falsch gewesen.
- **Ein Cronjob muss doppelt laufen dürfen:** Mit `UNIQUE` und `INSERT IGNORE` kann das Skript die letzten zwei Tage immer wieder holen, ohne Duplikate – so werden auch Ausfälle automatisch nachgeholt.
- **Daten brauchen eine Geschichte:** Zahlen allein sagten wenig. Erst mit der Reportage und klaren Definitionen (Hitzetag, Tropennacht, Hitzewelle) wurden sie verständlich.
- **Werkzeuge früh richtig einrichten:** VS Code, Git und eine funktionierende Serververbindung hätten mir viel Zeit gespart, wenn ich sie von Anfang an benutzt hätte.
- **Code, den ich abgebe, muss ich erklären können:** Diese Rückmeldung vom Marktstand war unangenehm, aber richtig.

---

## Was die Daten nicht sagen

- Die Werte sind **Modelldaten** von Open-Meteo für einen Punkt in Zürich, keine Messung einer Wetterstation. Extremwerte fallen etwas tiefer aus (36,8 statt gemessenen 38,3 °C).
- Die Daten beginnen am **29. Juli**. Juni und Anfang Juli, mit dem Rekordwert, fehlen.
- Die **Tropennacht** ist angenähert: Verwendet wird der tiefste Stundenwert des Tages.
- Es gibt **keine Gesundheitsdaten**: Die Seite zeigt die Hitze, nicht ihre Folgen.
- Zweieinhalb Monate zeigen **Wetter, nicht Klima**.
- Die Kapitel-Grafiken laden ihre Daten live von `view=heat`. Fällt die Datenbank aus, bleiben sie leer (die Live-Ansicht nutzt dann `data/fallback.json`).

---

## Betrieb (Installation auf einem Webserver)

1. **Datenbank anlegen** und in phpMyAdmin im Reiter «SQL» den Inhalt von `etl/schema.sql` ausführen.
2. **Zugangsdaten:** `config.template.php` kopieren, als `config.php` speichern und Host, Datenbank, Benutzer und Passwort eintragen.
3. **Dateien hochladen**, z. B. per `scp`:
   ```
   scp index.php script.js style.css unload.php BENUTZER@SERVER:www/DOMAIN/
   ```
4. **Testen:** `etl/check_transform.php` aufrufen → JSON mit `rows` und `audit`.
5. **Vergangenheit einmalig auffüllen:** `etl/load.php?past_days=92`.
6. **Cronjob** einrichten (jede Stunde um xx:05):
   ```
   5 * * * *  /usr/local/bin/wget -q -O /dev/null 'https://avoluwiz.myhostpoint.ch/etl/load.php'
   ```
7. **Fallback aktualisieren:** `etl/export_fallback.php` aufrufen.

---
## Ressourcen und Quellen

Alle Links zuletzt abgerufen am 16.10.2026.

### Daten
- Open-Meteo, Historical Weather / Forecast API (Wetterdaten Zürich, stündlich): https://open-meteo.com/en/docs
- Open-Meteo, Lizenz und Nennung (CC BY 4.0): https://open-meteo.com/en/license

### Inhalt und Definitionen
- MeteoSchweiz: Klimabulletin Sommer 2026. https://www.meteoschweiz.admin.ch/service-und-publikationen/publikationen/berichte-und-bulletins/2026/klimabulletin-sommer-2026.html
- MeteoSchweiz: Gefahrenstufen Hitze (Definition Hitzewelle, Stufen 3 und 4). https://www.meteoswiss.admin.ch/weather/hazards/explanation-of-the-danger-levels/heat.html
- Kanton Bern, Gesundheits-, Sozial- und Integrationsdirektion: Gesundheit und Hitze – FAQ. https://www.gsi.be.ch/de/start/themen/gesundheit/gesundheitsfoerderung-und-praevention/gesundheit-und-hitze/faq.html
- Bundesamt für Gesundheit (BAG): Drei goldene Regeln bei Hitze (PDF, via Stadt Bern). https://www.bern.ch/mediencenter/medienmitteilungen/aktuell_ptk/es-wird-heiss-tipps-fuer-den-umgang-mit-der-sommerhitze-3/dokumente/drei-goldene-regeln-fur-hitzetage.pdf/download
- Tsüri.ch: «Zürich und die Schweiz erlebten den heissesten Sommer seit Messbeginn». https://tsri.ch/a/zuerich-und-die-schweiz-erlebten-den-heissesten-sommer-seit-messbeginn
- Eigene Erfahrung: Zivildienst in einem Altersheim in Zürich, August 2026 (Personen anonymisiert).

### Technik und Dokumentation
- PHP-Handbuch, cURL: https://www.php.net/manual/de/book.curl.php
- PHP-Handbuch, PDO (Datenbankzugriff): https://www.php.net/manual/de/book.pdo.php
- Chart.js, Dokumentation: https://www.chartjs.org/docs/latest/
- MDN Web Docs, `position: sticky`: https://developer.mozilla.org/de/docs/Web/CSS/position
- MDN Web Docs, `color-mix()`: https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/color-mix
- MDN Web Docs, SVG `<line>` (Sonnenstrahlen): https://developer.mozilla.org/en-US/docs/Web/SVG/Element/line
- crontab.guru (Cronjob-Zeitplan prüfen): https://crontab.guru
- Kursunterlagen IM3, FHGR (Moodle)

### Schriften
- Fraunces (Google Fonts, SIL Open Font License): https://fonts.google.com/specimen/Fraunces
- Inter (Google Fonts, SIL Open Font License): https://fonts.google.com/specimen/Inter

### Werkzeuge
- VS Code, Git, GitHub
- Hostpoint (Hosting, MariaDB/phpMyAdmin, Cronjob)
- Claude (KI) als Unterstützung beim Programmieren, Recherchieren und Erklären