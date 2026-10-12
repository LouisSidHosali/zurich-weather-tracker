# Transform-Regeln

**Datenfrage:** Wie entwickeln sich Temperatur und Niederschlag in Zürich über die Zeit?
**Untersuchungseinheit:** eine Stunde in Zürich (eine Zeile in `weather_hourly`).

## Eingabe (Open-Meteo, Ausschnitt)

| Feld in der Quelle | Beispiel | Typ |
|---|---|---|
| `hourly.time[i]` | `"2026-10-07T10:00"` | String, Zürcher Zeit |
| `hourly.temperature_2m[i]` | `15.6` | Zahl oder `null` |
| `hourly.precipitation[i]` | `0.0` | Zahl oder `null` |
| `hourly.relative_humidity_2m[i]` | `74` | Zahl oder `null` |
| `hourly.wind_speed_10m[i]` | `6.2` | Zahl oder `null` |

## Regeln

| # | Art | Regel | Begründung |
|---|---|---|---|
| 1 | filtern | Nur Stunden bis jetzt behalten (`time <= jetzt`). | Spätere Stunden sind Prognosen, keine Messwerte. |
| 2 | deduplizieren | Jeder Zeitpunkt nur einmal. | Eine Stunde darf nicht doppelt zählen. |
| 3 | bereinigen | Werte ausserhalb der Grenzen werden `null`: Temperatur −40 bis 50 °C, Regen 0 bis 200 mm, Feuchte 0 bis 100 %, Wind 0 bis 250 km/h. | Unmögliche Werte sind Messfehler oder Platzhalter. |
| 4 | bereinigen | Stunden ohne gültige Temperatur fallen weg. | Ohne Temperatur ist die Zeile für die Frage nutzlos. Fehlender Regen bleibt `null`, nicht 0. |
| 5 | umbenennen | `temperature_2m` → `temperature_c`, `precipitation` → `precipitation_mm`, `relative_humidity_2m` → `humidity_pct`, `wind_speed_10m` → `wind_speed_kmh`, `time` → `measured_at`. | Einheit steht im Feldnamen (Datenvertrag). |
| 6 | umformen | Parallele Listen → eine Liste von Datensätzen; `measured_at` im Format `YYYY-MM-DD HH:MM:SS`. | Passt direkt in die Datenbank (DATETIME). |

## Ausgabe (Datenvertrag)

```json
{ "measured_at": "2026-10-07 10:00:00", "temperature_c": 15.6, "precipitation_mm": 0, "humidity_pct": 74, "wind_speed_kmh": 6.2 }
```

## Audit

Jeder Lauf zählt mit und speichert es in `etl_runs`:
`input_hours`, `future_hours`, `duplicates`, `invalid_values`, `missing_temperature`, `output_rows`, `inserted_rows`.

Beispiel erstes Auffüllen (7.10.2026, `past_days=92`): 2232 Stunden geholt, 13 Prognosestunden entfernt, 529 ohne Temperatur verworfen (die API liefert für die ältesten ~3 Wochen nur `null`), 1690 gespeichert.
