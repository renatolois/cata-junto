<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\RoleModel;
use App\Core\Utils\NeutralValue;

class RoleRepository extends BaseRepository {

  protected string $table = 'role';

  public function find_by_id(int $id): ?RoleModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_name(string $name): ?RoleModel {
    $result = $this->db->select($this->table, ['name' => $name]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_all_active(): array {
    $result = $this->db->select($this->table, ['active' => 1]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all(): array {
    $result = $this->db->select($this->table);
    return array_map([$this, 'hydrate'], $result);
  }

  public function create_role(array $data): RoleModel {
    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    $id = (int) $this->db->lastInsertId();
    return $this->find_by_id($id);
  }

  public function update_role(int $id, array $data): RoleModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_role(int $id): bool {
    return $this->db->update($this->table, $id, ['active' => 0]);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  public function hydrate(array $data): RoleModel {
    $role = new RoleModel();

    $role->set_id($data['id'] ?? null);
    $role->set_name($data['name'] ?? NeutralValue::instance());
    $role->set_active($data['active'] ?? true);

    return $role;
  }

  public function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['name'])) {
      $mapped['name'] = $data['name'] instanceof NeutralValue ? null : $data['name'];
    }

    if (isset($data['active'])) {
      $mapped['active'] = (int) $data['active'];
    }

    return $mapped;
  }
}