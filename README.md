# Zürich Weather Tracker

**Einzelprojekt** von Louis Hosali im Modul Interaktive Medien 3 (HS26, FHGR).

> Spezialauftrag: IM3 muss vor dem Start von IM5 abgeschlossen sein. Deshalb wurde das Projekt alleine und in verkürzter Zeit umgesetzt (Abgabe 17.10.2026 statt Januar). Backend (Extract, Transform, Load, Unload) und Frontend (Visualisierung) stammen beide von mir. Der Schwerpunkt liegt auf dem technischen Teil, die journalistische Einbettung wird gemäss Absprache nicht bewertet.

**Frage:** Wie entwickeln sich Temperatur und Niederschlag in Zürich über die Zeit?

- Live-Website: https://avoluwiz.myhostpoint.ch
- Datenquelle: [Open-Meteo Forecast API](https://open-meteo.com/en/docs) (kostenlos, ohne API-Key, CC BY 4.0)
- API-Aufruf: https://api.open-meteo.com/v1/forecast?latitude=47.3769&longitude=8.5417&hourly=temperature_2m,precipitation,relative_humidity_2m,wind_speed_10m&past_days=2&forecast_days=1&timezone=Europe/Zurich

## Kurzbeschreibung

Der Zürich Weather Tracker zeigt, wie sich Temperatur und Niederschlag in Zürich entwickeln – etwa, dass es seit Ende Juli um 13 Grad kühler geworden ist. Ein Cronjob ruft jede Stunde die Open-Meteo-API ab. PHP-Skripte bereinigen die Werte (keine Prognosen, keine Duplikate, unmögliche Werte werden null) und speichern sie in MariaDB. Ein JSON-Endpunkt liefert sie an die Website: drei automatisch berechnete Befunde, Chart.js-Diagramme und ein Methoden-Teil mit dem Audit.

## Screenshots

| Desktop | Mobile |
|---|---|
| ![Desktop](docs/screenshot-desktop.png) | ![Mobile](docs/screenshot-mobile.png) |

## Marktstand

![Marktstand Zürich, 13.10.2026](docs/marktstand.jpg)

## Wie es funktioniert

```
Open-Meteo API → etl/extract.php → etl/transform.php → etl/load.php → MySQL → unload.php → script.js (Chart.js)
                                                         ↑
                                               Cronjob, jede Stunde
```

| Datei | Aufgabe |
|---|---|
| `etl/extract.php` | Holt die stündlichen Wetterdaten für Zürich per cURL von Open-Meteo und gibt ein PHP-Array zurück. |
| `etl/transform.php` | Wendet die Regeln an: nur vergangene Stunden (keine Prognosen), Duplikate entfernen, unmögliche Werte → `null`, Feldnamen umbenennen. Gibt Datensätze plus Audit zurück. |
| `etl/check_transform.php` | Kontrollansicht: zeigt das Resultat von transform.php als JSON, ohne zu speichern. |
| `etl/load.php` | Schreibt die Datensätze mit `INSERT IGNORE` in `weather_hourly` und protokolliert das Audit in `etl_runs` – alles in einer Transaktion. Läuft stündlich per Cronjob. |
| `etl/schema.sql` | SQL zum Anlegen der beiden Tabellen. |
| `etl/export_fallback.php` | Schreibt eine Kopie der letzten 60 Tage nach `data/fallback.json`. |
| `lib/queries.php` | Gemeinsame Datenbank-Abfragen (stündlich, täglich, Status) für unload.php und export_fallback.php. |
| `unload.php` | JSON-Endpunkt fürs Frontend. Parameter `view=hourly|daily|status` und `days=1–365`. |
| `index.php`, `script.js`, `style.css` | Frontend mit Kennzahlen, zwei Chart.js-Diagrammen und Methoden-Teil (Audit). |
| `data/fallback.json` | Notfall-Kopie mit demselben Datenvertrag, falls der Endpunkt nicht antwortet. |
| `TRANSFORM.md` | Alle Transform-Regeln mit Begründung und Audit. |
| `config.template.php` | Vorlage für die Zugangsdaten (die echte `config.php` ist in `.gitignore`). |

## Datenmodell

| Tabelle | Eine Zeile bedeutet |
|---|---|
| `weather_hourly` | eine Stunde Wetter in Zürich |
| `etl_runs` | ein Durchlauf von load.php mit seinem Audit |

Für die Messwerte reicht eine Tabelle, weil nur ein Ort gemessen wird und sich kein Wert ständig wiederholt.

`weather_hourly`:

| Spalte | Typ | Bemerkung |
|---|---|---|
| id | INT, PK, AUTO_INCREMENT | |
| measured_at | DATETIME, UNIQUE | verhindert doppelte Stunden |
| temperature_c | DECIMAL(4,1) | Pflichtfeld |
| precipitation_mm | DECIMAL(5,1) | darf null sein |
| humidity_pct | TINYINT | darf null sein |
| wind_speed_kmh | DECIMAL(5,1) | darf null sein |

## Datenvertrag (unload.php)

`unload.php?view=hourly&days=7`
```json
[{ "measured_at": "2026-10-05 14:00:00", "temperature_c": 14.2, "precipitation_mm": 0, "humidity_pct": 71, "wind_speed_kmh": 8.3 }]
```

`unload.php?view=daily&days=30`
```json
[{ "date": "2026-10-05", "temp_min_c": 7.1, "temp_max_c": 15.8, "temp_avg_c": 11.2, "precipitation_mm": 3.4, "hours": 24 }]
```

`unload.php?view=status`
```json
{ "total_hours": 1650, "first_measured_at": "2026-07-29 01:00:00", "last_measured_at": "2026-10-06 14:00:00", "runs": 25,
  "last_run": { "run_at": "2026-10-06 14:05:01", "input_hours": 72, "future_hours": 9, "duplicates": 0, "invalid_values": 0, "missing_temperature": 0, "output_rows": 63, "inserted_rows": 1 } }
```

## Betrieb (Installation auf einem Webserver)

1. **Datenbank anlegen** (z. B. Hostpoint Control Panel) und in phpMyAdmin im Reiter «SQL» den Inhalt von `etl/schema.sql` ausführen.
2. **Zugangsdaten:** `config.template.php` kopieren, Kopie `config.php` nennen und Host, Datenbank, Benutzer und Passwort eintragen. `config.php` steht in `.gitignore`.
3. **Dateien hochladen** in den Web-Ordner der Domain.
4. **Testen:** `etl/check_transform.php` aufrufen → JSON mit `rows` und `audit`.
5. **Vergangenheit einmalig auffüllen:** `etl/load.php?past_days=92` aufrufen.
6. **Cronjob** einrichten (jede Stunde um xx:05):
   ```
   5 * * * *  /usr/local/bin/wget -q -O /dev/null 'https://avoluwiz.myhostpoint.ch/etl/load.php'
   ```
7. **Fallback aktualisieren** (z. B. vor einer Präsentation): `etl/export_fallback.php` aufrufen → schreibt `data/fallback.json`.

Lokal ohne Datenbank zeigen: `php -S localhost:8000` im Projektordner starten. Der Endpunkt antwortet dann mit Fehler, und die Seite lädt automatisch `data/fallback.json`.

## Was die Daten zeigen (Stand 13.10.2026)

Die Befunde werden in `script.js` (`renderStory`) aus allen vollständigen Tagen berechnet und aktualisieren sich automatisch.

- **Abkühlung:** Die erste Woche (29.07.–04.08.) hatte ein Tagesmittel von 26.8 °C, die letzte (06.10.–12.10.) noch 13.8 °C. Zürich ist also 13 Grad kühler geworden.
- **Spannweite:** Der Höchstwert war 36.8 °C (03.08.), der Tiefstwert 6.2 °C (12.10.).
- **Regen in Schüben:** 42 % des gesamten Regens (128.2 mm) fiel an nur drei Tagen. Die längste Trockenphase dauerte 14 Tage (17.09.–30.09.).

## Was die Daten nicht sagen

Die Werte sind stündliche Modelldaten von Open-Meteo für einen Punkt in Zürich, keine Messung einer einzelnen Wetterstation. Prognosen werden bewusst nicht gespeichert. Beim ersten Auffüllen mit `past_days=92` liefert die API für die ältesten rund drei Wochen nur `null` – diese Stunden verwirft der Transform und zählt sie im Audit.

## Learnings

- *(selbst ergänzen, z. B.: wie ETL in einzelne Dateien aufgeteilt wird, warum UNIQUE + INSERT IGNORE einen Cronjob sicher macht, warum PDO DECIMAL-Werte als Text liefert)*

## Schwierigkeiten

- *(selbst ergänzen)*

## Ressourcen

- Kursunterlagen IM3 (Folien A–F)
- MeteoSchweiz, Klimabulletin Sommer 2026 (Kontext im Einleitungstext): https://www.meteoschweiz.admin.ch/service-und-publikationen/publikationen/berichte-und-bulletins/2026/klimabulletin-sommer-2026.html
- Tsüri.ch, «Zürich und die Schweiz erlebten den heissesten Sommer seit Messbeginn»: https://tsri.ch/a/zuerich-und-die-schweiz-erlebten-den-heissesten-sommer-seit-messbeginn
- Open-Meteo Dokumentation: https://open-meteo.com/en/docs
- Chart.js: https://www.chartjs.org/docs/
- crontab.guru
- Claude (KI) als Unterstützung beim Schreiben und Erklären des Codes
