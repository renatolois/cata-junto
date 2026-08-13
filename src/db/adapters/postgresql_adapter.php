<?php
declare(strict_types=1);

namespace db\adapters;

require "../../vendor/autoload.php";
require "../../core/base/base_adapter.php";
require "../../utils/load_dotenv.php";
require "../../core/utils/logger.php";

use core\base\base_adapter;
use core\utils\logger;
use PDO;
use PDOException;
use Exception;
use RuntimeException;

class postgresql_adapter extends base_adapter {
  private array $env_vars;
  private ?PDO $pdo = null;

  public function __construct() {
    try {
      $this->env_vars = load_dotenv();
      logger::set_log_level($this->env_vars['log_level'] ?? 'all');
      
      $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $this->env_vars["db_host"] ?? 'localhost',
        $this->env_vars["db_port"] ?? '5432',
        $this->env_vars["db_name"] ?? 'postgres'
      );
      
      $this->pdo = new PDO(
        $dsn,
        $this->env_vars["db_user"] ?? 'postgres',
        $this->env_vars["db_password"] ?? '',
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      
      logger::all("pgsql_adapter inicializado", ['database' => $this->env_vars["db_name"]]);
    } catch (PDOException $e) {
      logger::error("Erro ao conectar ao PostgreSQL", ['error' => $e->getMessage()]);
      throw new RuntimeException("Erro ao conectar ao PostgreSQL: " . $e->getMessage());
    } catch (Exception $e) {
      logger::error("Erro ao iniciar pgsql_adapter", ['error' => $e->getMessage()]);
      throw new RuntimeException("Erro ao iniciar o banco de dados: " . $e->getMessage());
    }
  }

  public function connect(array $config): void {
    if ($config) {
      $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $config['host'] ?? $this->env_vars["db_host"] ?? 'localhost',
        $config['port'] ?? $this->env_vars["db_port"] ?? '5432',
        $config['database'] ?? $this->env_vars["db_name"] ?? 'postgres'
      );
      
      $this->pdo = new PDO(
        $dsn,
        $config['user'] ?? $this->env_vars["db_user"] ?? 'postgres',
        $config['password'] ?? $this->env_vars["db_password"] ?? '',
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      
      logger::info("PostgreSQL conectado via config", ['database' => $config['database'] ?? 'unknown']);
    }
  }

  public function disconnect(): void {
    $this->pdo = null;
    logger::all("PostgreSQL desconectado");
  }

  public function insert(string $table, array $data): array {
    logger::all("Insert em {$table}", $data);
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($data);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $id = $result['id'] ?? null;
    
    $result = $this->select_by_id($table, $id);
    
    logger::info("Insert concluído em {$table}", ['id' => $id]);
    
    return $result;
  }

  public function select(string $table, array $where = []): array {
    logger::all("Select em {$table}", ['where' => $where]);
    
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
    
    logger::all("Select retornou " . count($result) . " registros de {$table}");
    
    return $result;
  }

  public function select_by_id(string $table, $id): ?array {
    logger::all("SelectById em {$table}", ['id' => $id]);
    
    $result = $this->select($table, ['id' => $id]);
    
    if (!$result[0]) {
      logger::warning("Registro nao encontrado", ['table' => $table, 'id' => $id]);
    }
    
    return $result[0] ?? null;
  }

  public function update(string $table, $id, array $data): array {
    logger::all("Update em {$table}", ['id' => $id, 'data' => $data]);
    
    $sets = [];
    foreach ($data as $key => $value) {
      $sets[] = "{$key} = :{$key}";
    }
    
    $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
    $data['id'] = $id;
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($data);
    
    $result = $this->select_by_id($table, $id);
    
    logger::info("Update concluído em {$table}", ['id' => $id]);
    
    return $result;
  }

  public function delete(string $table, $id): bool {
    logger::warning("Delete em {$table}", ['id' => $id]);
    
    $sql = "DELETE FROM {$table} WHERE id = :id";
    $stmt = $this->pdo->prepare($sql);
    $success = $stmt->execute(['id' => $id]);
    
    if ($success) {
      logger::info("Delete concluído em {$table}", ['id' => $id]);
    } else {
      logger::error("Delete falhou em {$table}", ['id' => $id]);
    }
    
    return $success;
  }

  public function join(string $main_table, string $join_table, string $foreign_key, string $select = '*'): array {
    logger::all("Join entre {$main_table} e {$join_table}", [
      'foreign_key' => $foreign_key,
      'select' => $select
    ]);
    
    $on = "{$main_table}.{$foreign_key} = {$join_table}.id";
    $sql = "SELECT {$select} FROM {$main_table} 
            INNER JOIN {$join_table} ON {$on}";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    
    $result = $stmt->fetchAll();
    
    logger::all("Join retornou " . count($result) . " registros");
    
    return $result;
  }

  public function get_pdo(): ?PDO {
    return $this->pdo;
  }
}