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
  
  public function find_by_pk(int|string $pk): ?array {
    return $this->db->select_by_pk($this->table, $pk);
  }
  
  public function find_where(array $where): array {
    return $this->db->select($this->table, $where);
  }
  
  public function create(array $data): array {
    return $this->db->insert($this->table, $data);
  }
  
  public function update(int|string $pk, array $data): array {
    return $this->db->update($this->table, $pk, $data);
  }
  
  public function delete(int|string $pk): bool {
    return $this->db->delete($this->table, $pk);
  }

  public function generate_uuid(): string {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}