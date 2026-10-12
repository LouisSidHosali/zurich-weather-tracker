<?php
// Vorlage: als config.php kopieren und eigene Zugangsdaten eintragen.
// config.php steht in .gitignore.

$host     = 'KONTO.mysql.db.internal';
$dbname   = 'KONTO_weather';
$username = 'KONTO_wthr';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

date_default_timezone_set('Europe/Zurich');
