<?php
// Load: speichert die Stunden in der Datenbank und protokolliert den Durchlauf.
// Cronjob: stündlich etl/load.php · einmalig auffüllen: etl/load.php?past_days=92

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../config.php';

    $pastDays = (int) ($_GET['past_days'] ?? 2);
    $pastDays = max(1, min(92, $pastDays));

    $result = include __DIR__ . '/transform.php';
    $audit  = $result['audit'];

    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->beginTransaction();

    // measured_at ist UNIQUE: bestehende Stunden werden übersprungen
    $insertHour = $pdo->prepare(
        'INSERT IGNORE INTO weather_hourly
            (measured_at, temperature_c, precipitation_mm, humidity_pct, wind_speed_kmh)
         VALUES
            (:measured_at, :temperature_c, :precipitation_mm, :humidity_pct, :wind_speed_kmh)'
    );

    $inserted = 0;
    foreach ($result['rows'] as $row) {
        $insertHour->execute($row);
        $inserted += $insertHour->rowCount();
    }

    $insertRun = $pdo->prepare(
        'INSERT INTO etl_runs
            (run_at, input_hours, future_hours, duplicates, invalid_values,
             missing_temperature, output_rows, inserted_rows)
         VALUES
            (:run_at, :input_hours, :future_hours, :duplicates, :invalid_values,
             :missing_temperature, :output_rows, :inserted_rows)'
    );
    $insertRun->execute([
        'run_at'              => date('Y-m-d H:i:s'),
        'input_hours'         => $audit['input_hours'],
        'future_hours'        => $audit['future_hours'],
        'duplicates'          => $audit['duplicates'],
        'invalid_values'      => $audit['invalid_values'],
        'missing_temperature' => $audit['missing_temperature'],
        'output_rows'         => $audit['output_rows'],
        'inserted_rows'       => $inserted,
    ]);

    $pdo->commit();

    echo json_encode([
        'status'           => 'ok',
        'past_days'        => $pastDays,
        'audit'            => $audit,
        'inserted'         => $inserted,
        'already_existing' => count($result['rows']) - $inserted,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode(['error' => 'Laden fehlgeschlagen.']);
}
