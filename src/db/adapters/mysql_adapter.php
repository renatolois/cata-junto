<?php
declare(strict_types=1);

namespace Db\Adapters;

use Core\Base\BaseAdapter;
use Core\Utils\Logger;
use \PDO;
use \PDOException;
use \RuntimeException;
use \Exception;

class MysqlAdapter extends BaseAdapter {
  private array $env_vars;
  private ?PDO $pdo = null;

  public function __construct() {
    try {
      $this->env_vars = $this->loadEnv();
      Logger::set_log_level($this->env_vars['log_level'] ?? 'all');
      
      $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        $this->env_vars["db_host"],
        $this->env_vars["db_port"],
        $this->env_vars["db_name"]
      );
      
      $this->pdo = new PDO(
        $dsn,
        $this->env_vars["db_user"] ?? 'root',
        $this->env_vars["db_password"] ?? '',
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      
      Logger::all("MySQLAdapter initialized", ['database' => $this->env_vars["db_name"]]);
    } catch (PDOException $e) {
      Logger::error("Error connecting to MySQL", ['error' => $e->getMessage()]);
      throw new RuntimeException("Error connecting to MySQL: " . $e->getMessage());
    } catch (Exception $e) {
      Logger::error("Error initializing MySQLAdapter", ['error' => $e->getMessage()]);
      throw new RuntimeException("Error initializing database: " . $e->getMessage());
    }
  }

  private function loadEnv(): array {
    $env_file = __DIR__ . '/../../.env';
    $vars = [];

    if (file_exists($env_file)) {
      $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        if (str_starts_with($line, '#')) {
          continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
          $key = trim($parts[0]);
          $value = trim($parts[1]);
          $value = trim($value, '"\'');
          $vars[$key] = $value;
          $_ENV[$key] = $value;
        }
      }
    }

    // Fallback values
    return [
      'supabase_url' => $vars['SUPABASE_URL'] ?? null,
      'supabase_key' => $vars['SUPABASE_KEY'] ?? null,
      'db_url'       => $vars['DB_URL'] ?? null,
      'db_host'      => $vars['DB_HOST'] ?? 'localhost',
      'db_port'      => $vars['DB_PORT'] ?? '3306',
      'db_name'      => $vars['DB_NAME'] ?? 'cooperativa',
      'db_key'       => $vars['DB_KEY'] ?? null,
      'db_user'      => $vars['DB_USER'] ?? 'root',
      'db_password'  => $vars['DB_PASSWORD'] ?? '',
      'app_mode'     => $vars['APP_MODE'] ?? 'debug',
      'app_env'      => $vars['APP_ENV'] ?? 'testing',
      'log_level'    => $vars['LOG_LEVEL'] ?? 'all',
    ];
  }

  public function connect(array $config): void {
    if ($config) {
      $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        $config['host'] ?? $this->env_vars["db_host"] ?? 'localhost',
        $config['port'] ?? $this->env_vars["db_port"] ?? '3306',
        $config['database'] ?? $this->env_vars["db_name"] ?? 'cooperativa'
      );
      
      $this->pdo = new PDO(
        $dsn,
        $config['user'] ?? $this->env_vars["db_user"] ?? 'root',
        $config['password'] ?? $this->env_vars["db_password"] ?? '',
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      
      Logger::info("MySQL connected via config", ['database' => $config['database'] ?? 'unknown']);
    }
  }

  public function disconnect(): void {
    $this->pdo = null;
    Logger::all("MySQL disconnected");
  }

  public function insert(string $table, array $data): array {
    Logger::all("Insert into {$table}", $data);
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($data);
    
    $id = (int) $this->pdo->lastInsertId();
    $result = $this->select_by_id($table, $id);
    
    Logger::info("Insert completed in {$table}", ['id' => $id]);
    
    return $result;
  }

  public function select(string $table, array $where = []): array {
    Logger::all("Select from {$table}", ['where' => $where]);
    
    $sql = "SELECT * FROM {$table}";
    $params = [];
    
    if (!empty($where)) {
      $conditions = [];
      foreach ($where as $key => $value) {
        $conditions[] = "{$key} = :{$key}";
        $params[$key] = $value;
      }
      $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    
    $result = $stmt->fetchAll();
    
    Logger::all("Select returned " . count($result) . " records from {$table}");
    
    return $result;
  }

  public function select_by_id(string $table, int|string $id): ?array {
    Logger::all("select_by_id from {$table}", ['id' => $id]);
    
    $result = $this->select($table, ['id' => $id]);
    
    if (empty($result[0])) {
      Logger::warning("Record not found", ['table' => $table, 'id' => $id]);
    }
    
    return $result[0] ?? null;
  }

  public function update(string $table, int|string $id, array $data): array {
    Logger::all("Update in {$table}", ['id' => $id, 'data' => $data]);
    
    $sets = [];
    foreach ($data as $key => $value) {
      $sets[] = "{$key} = :{$key}";
    }
    
    $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
    $data['id'] = $id;
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($data);
    
    $result = $this->select_by_id($table, $id);
    
    Logger::info("Update completed in {$table}", ['id' => $id]);
    
    return $result;
  }

  public function delete(string $table, int|string $id): bool {
    Logger::warning("Delete from {$table}", ['id' => $id]);
    
    $sql = "DELETE FROM {$table} WHERE id = :id";
    $stmt = $this->pdo->prepare($sql);
    $success = $stmt->execute(['id' => $id]);
    
    if ($success) {
      Logger::info("Delete succeeded in {$table}", ['id' => $id]);
    } else {
      Logger::error("Delete failed in {$table}", ['id' => $id]);
    }
    
    return $success;
  }

  public function join(string $main_table, string $join_table, string $foreign_key, string $select = '*'): array {
    Logger::all("Join between {$main_table} and {$join_table}", [
      'foreign_key' => $foreign_key,
      'select' => $select
    ]);
    
    $sql = "SELECT {$select} FROM {$main_table} 
            INNER JOIN {$join_table} ON {$main_table}.{$foreign_key} = {$join_table}.id";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    
    $result = $stmt->fetchAll();
    
    Logger::all("Join returned " . count($result) . " records");
    
    return $result;
  }

  public function get_pdo(): ?PDO {
    return $this->pdo;
  }
  
  public function query(string $sql, array $params = []): array {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function execute(string $sql, array $params = []): bool {
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($params);
  }
  
  public function select_where(string $table, array $where, array $options = []): array {
    $sql = "SELECT * FROM {$table}";
    $params = [];
    $conditions = [];
    
    foreach ($where as $key => $value) {
      if ($value === null) {
        $conditions[] = "{$key} IS NULL";
      } else {
        $conditions[] = "{$key} = :{$key}";
        $params[$key] = $value;
      }
    }
    
    if (!empty($conditions)) {
      $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    if (isset($options['order_by'])) {
      $sql .= " ORDER BY " . $options['order_by'];
    }
    
    if (isset($options['limit'])) {
      $sql .= " LIMIT " . (int) $options['limit'];
    }
  
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
}