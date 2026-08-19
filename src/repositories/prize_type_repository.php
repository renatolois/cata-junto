<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\PrizeTypeModel;
use App\Core\Utils\NeutralValue;

class PrizeTypeRepository extends BaseRepository {

  protected string $table = 'prize_type';

  public function find_by_id(int $id): ?PrizeTypeModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_name(string $name): ?PrizeTypeModel {
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

  public function create_prize_type(array $data): PrizeTypeModel {
    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    $id = (int) $this->db->lastInsertId();
    return $this->find_by_id($id);
  }

  public function update_prize_type(int $id, array $data): PrizeTypeModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_prize_type(int $id): bool {
    return $this->db->update($this->table, $id, ['active' => 0]);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  private function hydrate(array $data): PrizeTypeModel {
    $prize_type = new PrizeTypeModel();

    $prize_type->set_id($data['id'] ?? null);
    $prize_type->set_name($data['name'] ?? NeutralValue::instance());
    $prize_type->set_description($data['description'] ?? NeutralValue::instance());
    $prize_type->set_cost_points((int) ($data['cost_points'] ?? 0));
    $prize_type->set_active((bool) ($data['active'] ?? true));

    return $prize_type;
  }

  private function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['name'])) {
      $mapped['name'] = $data['name'] instanceof NeutralValue ? null : $data['name'];
    }

    if (isset($data['description'])) {
      $mapped['description'] = $data['description'] instanceof NeutralValue ? null : $data['description'];
    }

    if (isset($data['cost_points'])) {
      $mapped['cost_points'] = $data['cost_points'];
    }

    if (isset($data['active'])) {
      $mapped['active'] = (int) $data['active'];
    }

    return $mapped;
  }
}