<?php

function toFloat($value): ?float
{
    return $value === null ? null : round((float) $value, 1);
}

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
function fetchHeat(PDO $pdo): array
{

$statement = $pdo->query(
        'SELECT DATE(measured_at)  AS date,
                MAX(temperature_c) AS temp_max_c,
                MIN(temperature_c) AS temp_min_c,
                AVG(temperature_c) AS temp_avg_c
         FROM weather_hourly
         GROUP BY DATE(measured_at)
         HAVING COUNT(*) >= 20
         ORDER BY date'
    );

    $days = [];
    foreach ($statement->fetchAll() as $row) {
        $max = toFloat($row['temp_max_c']);
        $min = toFloat($row['temp_min_c']);
        $avg = toFloat($row['temp_avg_c']);

        $days[] = [
            'date'           => $row['date'],
            'temp_max_c'     => $max,
            'temp_min_c'     => $min,
            'temp_avg_c'     => $avg,
            'heat_day'       => $max >= 30,   
            'tropical_night' => $min >= 20,   
            'hot_mean'       => $avg >= 25,   
        ];
    }

    // Hitzewelle: mindestens 3 Tage in Folge mit Tagesmittel ab 25 °C
    $heatwaves = [];
    $run = [];
    foreach ($days as $day) {
        $followsLast = $run !== []
            && strtotime(end($run)['date'] . ' +1 day') === strtotime($day['date']);

        if ($day['hot_mean'] && ($run === [] || $followsLast)) {
            $run[] = $day;
            continue;
        }
        if (count($run) >= 3) {
            $heatwaves[] = summarizeHeatwave($run);
        }
        $run = $day['hot_mean'] ? [$day] : [];
    }
    if (count($run) >= 3) {
        $heatwaves[] = summarizeHeatwave($run);
    }

    $heatDays = array_filter($days, fn($day) => $day['heat_day']);

    return [
        'heat_days'       => count($heatDays),
        'tropical_nights' => count(array_filter($days, fn($day) => $day['tropical_night'])),
        'last_heat_day'   => $heatDays === [] ? null : end($heatDays)['date'],
        'heatwaves'       => $heatwaves,
        'days'            => $days,
    ];
}

function summarizeHeatwave(array $run): array
{
    return [
        'from'       => $run[0]['date'],
        'to'         => end($run)['date'],
        'days'       => count($run),
        'temp_max_c' => max(array_column($run, 'temp_max_c')),
    ];
}
