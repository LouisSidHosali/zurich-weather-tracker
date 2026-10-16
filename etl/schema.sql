
CREATE TABLE weather_hourly (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  measured_at      DATETIME NOT NULL UNIQUE,
  temperature_c    DECIMAL(4,1) NOT NULL,
  precipitation_mm DECIMAL(5,1) NULL,
  humidity_pct     TINYINT UNSIGNED NULL,
  wind_speed_kmh   DECIMAL(5,1) NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE etl_runs (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  run_at              DATETIME NOT NULL,
  input_hours         SMALLINT UNSIGNED NOT NULL,
  future_hours        SMALLINT UNSIGNED NOT NULL,
  duplicates          SMALLINT UNSIGNED NOT NULL,
  invalid_values      SMALLINT UNSIGNED NOT NULL,
  missing_temperature SMALLINT UNSIGNED NOT NULL,
  output_rows         SMALLINT UNSIGNED NOT NULL,
  inserted_rows       SMALLINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
