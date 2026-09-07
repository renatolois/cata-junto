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
  private ?PDO $pdo = null;

  public function __construct() {
    $env = $this->load_env();
    Logger::set_log_level($env['log_level'] ?? 'all');
    Logger::all("MySQLAdapter initialized");
  }

  public function connect(array $config): void {
    try {
      $env = $this->load_env();
      
      $host = $config['host'] ?? $env['db_host'] ?? 'localhost';
      $port = $config['port'] ?? $env['db_port'] ?? '3306';
      $dbname = $config['dbname'] ?? $env['db_name'] ?? 'cooperativa';
      $user = $config['user'] ?? $env['db_user'] ?? 'root';
      $password = $config['password'] ?? $env['db_password'] ?? '';

      $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
      
      $this->pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      
      Logger::info("MySQL connected successfully", ['database' => $dbname, 'user' => $user]);
    } catch (PDOException $e) {
      Logger::error("Error connecting to MySQL", ['error' => $e->getMessage()]);
      throw new RuntimeException("Error connecting to MySQL: " . $e->getMessage());
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
    
    Logger::info("Insert completed in {$table}");
    
    if (isset($data['id']) && !empty($data['id'])) {
      $result = $this->select_by_id($table, $data['id']);
      return $result ?? [];
    }
    
    $id = $this->pdo->lastInsertId();
    $result = $this->select_by_id($table, $id);

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