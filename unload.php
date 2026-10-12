<?php
// Unload: JSON-Endpunkt für das Frontend.
//   unload.php?view=hourly&days=7   stündliche Werte
//   unload.php?view=daily&days=30   Tageswerte (min/max/Ø, Regen-Summe)
//   unload.php?view=status          Stand der Sammlung + letzter Durchlauf

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/config.php';
    require __DIR__ . '/lib/queries.php';

    $view = $_GET['view'] ?? 'hourly';
    $days = max(1, min(365, (int) ($_GET['days'] ?? 7)));

    if (!in_array($view, ['hourly', 'daily', 'status'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'view muss hourly, daily oder status sein.']);
        exit;
    }

    $pdo = new PDO($dsn, $username, $password, $options);

    $data = match ($view) {
        'hourly' => fetchHourly($pdo, sinceDate($days)),
        'daily'  => fetchDaily($pdo, sinceDate($days)),
        'status' => fetchStatus($pdo),
    };

    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode(['error' => 'Daten konnten nicht geladen werden.']);
}
