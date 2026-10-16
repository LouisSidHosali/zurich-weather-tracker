<?php

$raw = include __DIR__ . '/extract.php';

$hourly        = $raw['hourly'] ?? [];
$times         = $hourly['time'] ?? [];
$temperatures  = $hourly['temperature_2m'] ?? [];
$precipitation = $hourly['precipitation'] ?? [];
$humidity      = $hourly['relative_humidity_2m'] ?? [];
$wind          = $hourly['wind_speed_10m'] ?? [];

$audit = [
    'input_hours'         => count($times),
    'future_hours'        => 0,
    'duplicates'          => 0,
    'invalid_values'      => 0,
    'missing_temperature' => 0,
    'output_rows'         => 0,
];


function cleanNumber($value, float $min, float $max, array &$audit): ?float
{
    if ($value === null || !is_numeric($value)) {
        return null;
    }
    $number = (float) $value;
    if ($number < $min || $number > $max) {
        $audit['invalid_values']++;
        return null;
    }
    return $number;
}

$now  = (new DateTime('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d\TH:i');
$rows = [];
$seen = [];

for ($i = 0; $i < count($times); $i++) {
    $time = $times[$i];

    if ($time > $now) {
        $audit['future_hours']++;
        continue;
    }

    if (isset($seen[$time])) {
        $audit['duplicates']++;
        continue;
    }
    $seen[$time] = true;

    $temperatureC = cleanNumber($temperatures[$i] ?? null, -40, 50, $audit);
    if ($temperatureC === null) {
        $audit['missing_temperature']++;
        continue;
    }
    $precipitationMm = cleanNumber($precipitation[$i] ?? null, 0, 200, $audit);
    $humidityPct     = cleanNumber($humidity[$i] ?? null, 0, 100, $audit);
    $windKmh         = cleanNumber($wind[$i] ?? null, 0, 250, $audit);

    $rows[] = [
        'measured_at'      => str_replace('T', ' ', $time) . ':00',
        'temperature_c'    => round($temperatureC, 1),
        'precipitation_mm' => $precipitationMm === null ? null : round($precipitationMm, 1),
        'humidity_pct'     => $humidityPct === null ? null : (int) round($humidityPct),
        'wind_speed_kmh'   => $windKmh === null ? null : round($windKmh, 1),
    ];
}

$audit['output_rows'] = count($rows);

return [
    'rows'  => $rows,
    'audit' => $audit,
];
