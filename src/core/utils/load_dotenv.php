<?php
declare(strict_types=1);

namespace Core\Utils;

use Exception;
use RuntimeException;

class EnvLoader {
  private static array $env_vars = [];

  public static function load(): array {
    if (!empty(self::$env_vars)) {
      return self::$env_vars;
    }

    try {
      $path = __DIR__ . '/../../../.env';

      if (file_exists($path)) {
        self::parse_env_file($path);
      }

      self::$env_vars = [
        'db_host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
        'db_port'     => $_ENV['DB_PORT']     ?? '3306',
        'db_name'     => $_ENV['DB_NAME']     ?? 'database',
        'db_user'     => $_ENV['DB_USER']     ?? 'root',
        'db_password' => $_ENV['DB_PASSWORD'] ?? '',
        'app_mode'    => $_ENV['APP_MODE']    ?? 'debug',
        'app_env'     => $_ENV['APP_ENV']     ?? 'testing',
        'log_level'   => $_ENV['LOG_LEVEL']   ?? 'all',
        'jwt_secret'  => $_ENV['JWT_SECRET']  ?? null,
      ];

      return self::$env_vars;
    } catch (Exception $e) {
      throw new RuntimeException("Failed to load env: " . $e->getMessage());
    }
  }

  public static function get(string $key, $default = null) {
    $vars = self::load();
    return $vars[$key] ?? $default;
  }

  private static function parse_env_file(string $path): void {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      return;
    }

    foreach ($lines as $line) {
      $line = trim($line);

      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }

      if (!str_contains($line, '=')) {
        continue;
      }

      [$key, $value] = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);

      if (strlen($value) >= 2) {
        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
          $value = substr($value, 1, -1);
        }
      }

      $_ENV[$key] = $value;
      putenv("$key=$value");
    }
  }
}