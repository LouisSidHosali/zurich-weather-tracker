<?php
// Extract: holt stündliche Wetterdaten für Zürich von Open-Meteo.

function fetchJson(string $url): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('API nicht erreichbar: ' . curl_error($ch));
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Ungültige Antwort der API.');
    }
    return $data;
}

// 2 Tage zurück, damit verpasste Stunden nachgeholt werden
$pastDays = $pastDays ?? 2;

$url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
    'latitude'      => 47.3769,
    'longitude'     => 8.5417,
    'hourly'        => 'temperature_2m,precipitation,relative_humidity_2m,wind_speed_10m',
    'past_days'     => $pastDays,
    'forecast_days' => 1,
    'timezone'      => 'Europe/Zurich',
]);

return fetchJson($url);
