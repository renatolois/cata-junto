<?php
declare(strict_types=1);

namespace Db\Adapters;

require "../../vendor/autoload.php";
require "../../core/base/base_adapter.php";
require "../../utils/load_dotenv.php";
require "../../core/utils/logger.php";

use Core\Base\BaseAdapter;
use Core\Utils\Logger;
use PHPSupabase\Service;
use Exception;
use RuntimeException;

class SupabaseAdapter extends BaseAdapter {
  private array $env_vars;
  private ?Service $service = null;

  public function __construct() {
    try {
      $this->env_vars = load_dotenv();
      Logger::setLogLevel($this->env_vars['log_level'] ?? 'all');
      
      $this->service = new Service(
        $this->env_vars["SUPABASE_KEY"],
        $this->env_vars["SUPABASE_URL"]
      );
      
      Logger::all("SupabaseAdapter inicializado");
    } catch (Exception $e) {
      Logger::error("Erro ao iniciar SupabaseAdapter", ['error' => $e->getMessage()]);
      throw new RuntimeException("Erro ao iniciar o banco de dados: " . $e->getMessage());
    }
  }

  public function connect(array $config): void {
    if ($config) {
      $this->service = new Service(
        $config['key'] ?? $this->env_vars["SUPABASE_KEY"],
        $config['url'] ?? $this->env_vars["SUPABASE_URL"]
      );
      Logger::info("Supabase conectado via config");
    }
  }

  public function disconnect(): void {
    $this->service = null;
    Logger::all("Supabase desconectado");
  }

  public function insert(string $table, array $data): array {
    Logger::all("Insert em {$table}", $data);
    
    $result = $this->service->from($table)->insert($data)->execute();
    $data = $result->getData();
    
    Logger::info("Insert concluído em {$table}", ['id' => $data[0]['id'] ?? null]);
    
    return $data[0] ?? [];
  }

  public function select(string $table, array $where = []): array {
    Logger::all("Select em {$table}", ['where' => $where]);
    
    $query = $this->service->from($table)->select('*');
    foreach ($where as $key => $value) {
      $query->eq($key, $value);
    }
    
    $result = $query->get();
    $data = $result->getData() ?? [];
    
    Logger::all("Select retornou " . count($data) . " registros de {$table}");
    
    return $data;
  }

  public function selectById(string $table, int $id): ?array {
    Logger::all("SelectById em {$table}", ['id' => $id]);
    
    $result = $this->select($table, ['id' => $id]);
    
    if (!$result[0]) {
      Logger::warning("Registro não encontrado", ['table' => $table, 'id' => $id]);
    }
    
    return $result[0] ?? null;
  }

  public function update(string $table, int $id, array $data): array {
    Logger::all("Update em {$table}", ['id' => $id, 'data' => $data]);
    
    $result = $this->service->from($table)
      ->update($data)
      ->eq('id', $id)
      ->execute();
    $data = $result->getData();
    
    Logger::info("Update concluído em {$table}", ['id' => $id]);
    
    return $data[0] ?? [];
  }

  public function delete(string $table, int $id): bool {
    Logger::warning("Delete em {$table}", ['id' => $id]);
    
    $result = $this->service->from($table)
      ->delete()
      ->eq('id', $id)
      ->execute();
    
    $success = $result->getData() !== null;
    
    if ($success) {
      Logger::info("Delete concluído em {$table}", ['id' => $id]);
    } else {
      Logger::error("Delete falhou em {$table}", ['id' => $id]);
    }
    
    return $success;
  }

  public function join(string $main_table, string $join_table, string $foreign_key, string $select = '*'): array {
    Logger::all("Join entre {$main_table} e {$join_table}", [
      'foreign_key' => $foreign_key,
      'select' => $select
    ]);
    
    $query = $this->service->initializeQueryBuilder();
  
    $result = $query->select($select)
      ->from($main_table)
      ->join($join_table, $foreign_key)
      ->execute()
      ->getResult();
  
    if (empty($result)) {
      Logger::warning("Join retornou vazio", [
        'main_table' => $main_table,
        'join_table' => $join_table
      ]);
      return [];
    }
    
    $data = json_decode(json_encode($result), true);
    Logger::all("Join retornou " . count($data) . " registros");
    
    return $data;
  }

  public function getService(): ?Service {
    return $this->service;
  }
}