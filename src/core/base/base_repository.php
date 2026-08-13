<?php
declare(strict_types=1);

namespace Core\Base;

use Db\Database;

abstract class BaseRepository {
  protected Database $db;
  protected string $table;
  
  public function __construct(Database $db) {
    $this->db = $db;
  }
  
  public function find_all(): array {
    return $this->db->select($this->table);
  }
  
  public function find_by_pk($pk): ?array {
    return $this->db->select_by_pk($this->table, $pk);
  }
  
  public function find_where(array $where): array {
    return $this->db->select($this->table, $where);
  }
  
  public function create(array $data): array {
    return $this->db->insert($this->table, $data);
  }
  
  public function update($pk, array $data): array {
    return $this->db->update($this->table, $pk, $data);
  }
  
  public function delete($pk): bool {
    return $this->db->delete($this->table, $pk);
  }
}