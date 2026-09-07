<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\InPersonCollectionModel;
use App\Core\Utils\NeutralValue;

class InPersonCollectionRepository extends BaseRepository {

  protected string $table = 'in_person_collection';

  public function find_by_id(string $id): ?InPersonCollectionModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_collected_by(string $collected_by): array {
    $result = $this->db->select($this->table, ['collected_by' => $collected_by]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_material_type_id(int $material_type_id): array {
    $result = $this->db->select($this->table, ['material_type_id' => $material_type_id]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_collect_type(string $collect_type): array {
    $result = $this->db->select($this->table, ['collect_type' => $collect_type]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all_active(): array {
    $result = $this->db->select($this->table, ['active' => 1]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all(): array {
    $result = $this->db->select($this->table);
    return array_map([$this, 'hydrate'], $result);
  }

  public function create_in_person_collection(array $data): InPersonCollectionModel {
    if (!isset($data['id'])) {
      $data['id'] = $this->generate_uuid();
    }

    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    return $this->find_by_id($data['id']);
  }

  public function update_in_person_collection(string $id, array $data): InPersonCollectionModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_in_person_collection(string $id): bool {
    return $this->db->update($this->table, $id, ['active' => 0]);
  }

  public function hydrate(array $data): InPersonCollectionModel {
    $collection = new InPersonCollectionModel();

    $collection->set_id($data['id'] ?? null);
    $collection->set_collected_by($data['collected_by'] ?? NeutralValue::instance());
    $collection->set_material_type_id((int) ($data['material_type_id'] ?? 0));
    $collection->set_collected_at($data['collected_at'] ?? NeutralValue::instance());
    $collection->set_collect_type($data['collect_type'] ?? NeutralValue::instance());
    $collection->set_quantity((float) ($data['quantity'] ?? 0));
    $collection->set_observation($data['observation'] ?? NeutralValue::instance());
    $collection->set_active((bool) ($data['active'] ?? true));
    $collection->set_paid_value((float) ($data['paid_value'] ?? 0.0));

    return $collection;
  }

  public function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['collected_by'])) {
      $mapped['collected_by'] = $data['collected_by'] instanceof NeutralValue ? null : $data['collected_by'];
    }

    if (isset($data['material_type_id'])) {
      $mapped['material_type_id'] = $data['material_type_id'];
    }

    if (isset($data['collected_at'])) {
      $mapped['collected_at'] = $data['collected_at'] instanceof NeutralValue ? null : $data['collected_at'];
    }

    if (isset($data['collect_type'])) {
      $mapped['collect_type'] = $data['collect_type'] instanceof NeutralValue ? null : $data['collect_type'];
    }

    if (isset($data['quantity'])) {
      $mapped['quantity'] = $data['quantity'];
    }

    if (isset($data['observation'])) {
      $mapped['observation'] = $data['observation'] instanceof NeutralValue ? null : $data['observation'];
    }

    if (isset($data['active'])) {
      $mapped['active'] = (int) $data['active'];
    }

    if (isset($data['paid_value'])) {
      $mapped['paid_value'] = $data['paid_value'] instanceof NeutralValue ? null : (float) $data['paid_value'];
    }

    return $mapped;
  }
}