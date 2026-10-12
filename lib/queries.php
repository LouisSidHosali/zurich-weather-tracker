<?php
// Gemeinsame Abfragen für unload.php und etl/export_fallback.php.

// PDO liefert DECIMAL als String, der Datenvertrag verlangt Zahlen
function toFloat($value): ?float
{
    return $value === null ? null : round((float) $value, 1);
}

// Erster Zeitpunkt des Zeitraums: heute + ($days - 1) Tage davor, ab 00:00
function sinceDate(int $days): string
{
    return date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
}

function fetchHourly(PDO $pdo, string $since): array
{
    $statement = $pdo->prepare(
        'SELECT measured_at, temperature_c, precipitation_mm, humidity_pct, wind_speed_kmh
         FROM weather_hourly
         WHERE measured_at >= :since
         ORDER BY measured_at'
    );
    $statement->execute(['since' => $since]);

    return array_map(fn($row) => [
        'measured_at'      => $row['measured_at'],
        'temperature_c'    => toFloat($row['temperature_c']),
        'precipitation_mm' => toFloat($row['precipitation_mm']),
        'humidity_pct'     => $row['humidity_pct'] === null ? null : (int) $row['humidity_pct'],
        'wind_speed_kmh'   => toFloat($row['wind_speed_kmh']),
    ], $statement->fetchAll());
}

function fetchDaily(PDO $pdo, string $since): array
{
    $statement = $pdo->prepare(
        'SELECT DATE(measured_at)     AS date,
                MIN(temperature_c)    AS temp_min_c,
                MAX(temperature_c)    AS temp_max_c,
                AVG(temperature_c)    AS temp_avg_c,
                SUM(precipitation_mm) AS precipitation_mm,
                COUNT(*)              AS hours
         FROM weather_hourly
         WHERE measured_at >= :since
         GROUP BY DATE(measured_at)
         ORDER BY date'
    );
    $statement->execute(['since' => $since]);

    return array_map(fn($row) => [
        'date'             => $row['date'],
        'temp_min_c'       => toFloat($row['temp_min_c']),
        'temp_max_c'       => toFloat($row['temp_max_c']),
        'temp_avg_c'       => toFloat($row['temp_avg_c']),
        'precipitation_mm' => toFloat($row['precipitation_mm']),
        'hours'            => (int) $row['hours'],
    ], $statement->fetchAll());
}

function fetchStatus(PDO $pdo): array
{
    $summary = $pdo->query(
        'SELECT COUNT(*) AS total_hours,
                MIN(measured_at) AS first_measured_at,
                MAX(measured_at) AS last_measured_at
         FROM weather_hourly'
    )->fetch();

    $lastRun = $pdo->query(
        'SELECT run_at, input_hours, future_hours, duplicates, invalid_values,
                missing_temperature, output_rows, inserted_rows
         FROM etl_runs
         ORDER BY id DESC
         LIMIT 1'
    )->fetch();

    return [
        'total_hours'       => (int) $summary['total_hours'],
        'first_measured_at' => $summary['first_measured_at'],
        'last_measured_at'  => $summary['last_measured_at'],
        'runs'              => (int) $pdo->query('SELECT COUNT(*) FROM etl_runs')->fetchColumn(),
        'last_run'          => $lastRun === false ? null : array_map(
            fn($value) => is_numeric($value) ? (int) $value : $value,
            $lastRun
        ),
    ];
}
