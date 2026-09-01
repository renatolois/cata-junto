<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\MaterialTypeModel;
use App\Core\Utils\NeutralValue;

class MaterialTypeRepository extends BaseRepository {

  protected string $table = 'material_type';

  public function find_by_id(int $id): ?MaterialTypeModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_name(string $name): ?MaterialTypeModel {
    $result = $this->db->select($this->table, ['name' => $name]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_all_active(): array {
    $result = $this->db->select($this->table, ['weight_active' => 1, 'unit_active' => 1]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all(): array {
    $result = $this->db->select($this->table);
    return array_map([$this, 'hydrate'], $result);
  }

  public function create_material_type(array $data): MaterialTypeModel {
    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    $id = (int) $this->db->lastInsertId();
    return $this->find_by_id($id);
  }

  public function update_material_type(int $id, array $data): MaterialTypeModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_material_type(int $id): bool {
    return $this->db->update($this->table, $id, ['weight_active' => 0, 'unit_active' => 0]);
  }

  public function hydrate(array $data): MaterialTypeModel {
    $material_type = new MaterialTypeModel();

    $material_type->set_id($data['id'] ?? null);
    $material_type->set_name($data['name'] ?? NeutralValue::instance());
    $material_type->set_price_per_weight((float) ($data['price_per_weight'] ?? 0));
    $material_type->set_points_per_weight((int) ($data['points_per_weight'] ?? 0));
    $material_type->set_price_per_unit((float) ($data['price_per_unit'] ?? 0));
    $material_type->set_points_per_unit((int) ($data['points_per_unit'] ?? 0));
    $material_type->set_weight_active((bool) ($data['weight_active'] ?? true));
    $material_type->set_unit_active((bool) ($data['unit_active'] ?? true));

    return $material_type;
  }

  public function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['name'])) {
      $mapped['name'] = $data['name'] instanceof NeutralValue ? null : $data['name'];
    }

    if (isset($data['price_per_weight'])) {
      $mapped['price_per_weight'] = $data['price_per_weight'];
    }

    if (isset($data['points_per_weight'])) {
      $mapped['points_per_weight'] = $data['points_per_weight'];
    }

    if (isset($data['price_per_unit'])) {
      $mapped['price_per_unit'] = $data['price_per_unit'];
    }

    if (isset($data['points_per_unit'])) {
      $mapped['points_per_unit'] = $data['points_per_unit'];
    }

    if (isset($data['weight_active'])) {
      $mapped['weight_active'] = (int) $data['weight_active'];
    }

    if (isset($data['unit_active'])) {
      $mapped['unit_active'] = (int) $data['unit_active'];
    }

    return $mapped;
  }
}