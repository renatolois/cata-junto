<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\ResidentialCollectionModel;
use App\Core\Utils\NeutralValue;

class ResidentialCollectionRepository extends BaseRepository {
  protected string $table = 'residential_collection';

  public function find_by_id(string $id): ?ResidentialCollectionModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_collection_location_id(string $collection_location_id): array {
    $result = $this->db->select($this->table, ['collection_location_id' => $collection_location_id]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_status(string $status): array {
    $result = $this->db->select($this->table, ['status' => $status]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all(): array {
    $result = $this->db->select($this->table);
    return array_map([$this, 'hydrate'], $result);
  }

  public function create_residential_collection(array $data): ResidentialCollectionModel {
    if (!isset($data['id'])) {
      $data['id'] = $this->generate_uuid();
    }

    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    return $this->find_by_id($data['id']);
  }

  public function update_residential_collection(string $id, array $data): ResidentialCollectionModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_residential_collection(string $id): bool {
    return $this->db->update($this->table, $id, ['status' => 'inactive']);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  private function hydrate(array $data): ResidentialCollectionModel {
    $collection = new ResidentialCollectionModel();

    $collection->set_id($data['id'] ?? null);
    $collection->set_collected_by($data['collected_by'] ?? NeutralValue::instance());
    $collection->set_material_type_id((int) ($data['material_type_id'] ?? 0));
    $collection->set_collected_at($data['collected_at'] ?? NeutralValue::instance());
    $collection->set_collect_type($data['collect_type'] ?? NeutralValue::instance());
    $collection->set_quantity((float) ($data['quantity'] ?? 0));
    $collection->set_observation($data['observation'] ?? NeutralValue::instance());
    $collection->set_active((bool) ($data['active'] ?? true));
    $collection->set_collection_location_id($data['collection_location_id'] ?? NeutralValue::instance());
    $collection->set_requested_at($data['requested_at'] ?? NeutralValue::instance());
    $collection->set_description($data['description'] ?? NeutralValue::instance());
    $collection->set_status($data['status'] ?? 'pending');
    $collection->set_deactivation_at($data['deactivation_at'] ?? NeutralValue::instance());
    $collection->set_deactivation_justification($data['deactivation_justification'] ?? NeutralValue::instance());

    return $collection;
  }

  private function map_to_database(array $data): array {
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

    if (isset($data['collection_location_id'])) {
      $mapped['collection_location_id'] = $data['collection_location_id'] instanceof NeutralValue ? null : $data['collection_location_id'];
    }

    if (isset($data['requested_at'])) {
      $mapped['requested_at'] = $data['requested_at'] instanceof NeutralValue ? null : $data['requested_at'];
    }

    if (isset($data['description'])) {
      $mapped['description'] = $data['description'] instanceof NeutralValue ? null : $data['description'];
    }

    if (isset($data['status'])) {
      $mapped['status'] = $data['status'];
    }

    if (isset($data['deactivation_at'])) {
      $mapped['deactivation_at'] = $data['deactivation_at'] instanceof NeutralValue ? null : $data['deactivation_at'];
    }

    if (isset($data['deactivation_justification'])) {
      $mapped['deactivation_justification'] = $data['deactivation_justification'] instanceof NeutralValue ? null : $data['deactivation_justification'];
    }

    return $mapped;
  }
}