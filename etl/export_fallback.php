<?php
// Schreibt die letzten 60 Tage nach data/fallback.json (Notfall-Kopie fürs Frontend).

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../config.php';
    require __DIR__ . '/../lib/queries.php';

    $pdo   = new PDO($dsn, $username, $password, $options);
    $since = sinceDate(60);

    $data = [
        'status' => ['exported_at' => date('Y-m-d H:i:s')] + fetchStatus($pdo),
        'hourly' => fetchHourly($pdo, $since),
        'daily'  => fetchDaily($pdo, $since),
    ];

    file_put_contents(
        __DIR__ . '/../data/fallback.json',
        json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
    );

    echo json_encode([
        'status' => 'ok',
        'hourly' => count($data['hourly']),
        'daily'  => count($data['daily']),
    ], JSON_PRETTY_PRINT);

} catch (Throwable $error) {
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode(['error' => 'Export fehlgeschlagen.']);
}
