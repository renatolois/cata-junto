<?php
declare(strict_types=1);

namespace Core\Utils;

class Logger {
  private static string $log_file = __DIR__ . '/../../logs/app.log';
  private static string $log_level = 'all'; // all, info, warning, error
  
  public static function set_log_level(string $level): void {
    $allowed = ['all', 'info', 'warning', 'error'];
    if (in_array($level, $allowed)) {
      self::$log_level = $level;
    }
  }
  
  private static function should_log(string $level): bool {
    static $levels = ['all' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    $current = $levels[self::$log_level] ?? 0;
    $target = $levels[$level] ?? 0;
    return $target >= $current;
  }
  
  public static function all(string $message, array $context = []): void {
    if (self::should_log('all')) {
      self::write('ALL', $message, $context);
    }
  }
  
  public static function info(string $message, array $context = []): void {
    if (self::should_log('info')) {
      self::write('INFO', $message, $context);
    }
  }
  
  public static function warning(string $message, array $context = []): void {
    if (self::should_log('warning')) {
      self::write('WARNING', $message, $context);
    }
  }
  
  public static function error(string $message, array $context = []): void {
    if (self::should_log('error')) {
      self::write('ERROR', $message, $context);
    }
  }
  
  private static function write(string $level, string $message, array $context = []): void {
    $log = sprintf(
      "[%s] %s: %s %s\n",
      date('Y-m-d H:i:s'),
      $level,
      $message,
      !empty($context) ? json_encode($context) : ''
    );
    
    $log_dir = dirname(self::$log_file);
    if (!is_dir($log_dir)) {
      mkdir($log_dir, 0755, true);
    }
    
    error_log($log, 3, self::$log_file);
  }
  
  public static function set_log_file(string $file): void {
    self::$log_file = $file;
  }
}