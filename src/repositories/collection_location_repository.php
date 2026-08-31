<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\CollectionLocationModel;
use App\Core\Utils\NeutralValue;

class CollectionLocationRepository extends BaseRepository {

  protected string $table = 'collection_location';

  public function find_by_id(string $id): ?CollectionLocationModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_email(string $email): ?CollectionLocationModel {
    $result = $this->db->select($this->table, ['responsable_email' => $email]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_cep(string $cep): array {
    $result = $this->db->select($this->table, ['cep' => $cep]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_city(string $city): array {
    $result = $this->db->select($this->table, ['city' => $city]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_state(string $state): array {
    $result = $this->db->select($this->table, ['state' => $state]);
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

  public function create_collection_location(array $data): CollectionLocationModel {
    if (!isset($data['id'])) {
      $data['id'] = $this->generate_uuid();
    }

    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    return $this->find_by_id($data['id']);
  }

  public function update_collection_location(string $id, array $data): CollectionLocationModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_collection_location(string $id): bool {
    return $this->db->update($this->table, $id, ['active' => 0]);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  public function hydrate(array $data): CollectionLocationModel {
    $collection_location = new CollectionLocationModel();

    $collection_location->set_id($data['id'] ?? null);
    $collection_location->set_responsable_email($data['responsable_email'] ?? NeutralValue::instance());
    $collection_location->set_verified_email((bool) ($data['verified_email'] ?? false));
    $collection_location->set_password_hash($data['password_hash'] ?? NeutralValue::instance());
    $collection_location->set_responsable_phone_number($data['responsable_phone_number'] ?? NeutralValue::instance());
    $collection_location->set_street($data['street'] ?? NeutralValue::instance());
    $collection_location->set_number($data['number'] ?? NeutralValue::instance());
    $collection_location->set_neighborhood($data['neighborhood'] ?? NeutralValue::instance());
    $collection_location->set_complement($data['complement'] ?? NeutralValue::instance());
    $collection_location->set_city($data['city'] ?? NeutralValue::instance());
    $collection_location->set_state($data['state'] ?? NeutralValue::instance());
    $collection_location->set_cep($data['cep'] ?? NeutralValue::instance());
    $collection_location->set_current_points((int) ($data['current_points'] ?? 0));
    $collection_location->set_active((bool) ($data['active'] ?? true));

    return $collection_location;
  }

  public function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['responsable_email'])) {
      $mapped['responsable_email'] = $data['responsable_email'] instanceof NeutralValue ? null : $data['responsable_email'];
    }

    if (isset($data['verified_email'])) {
      $mapped['verified_email'] = (int) $data['verified_email'];
    }

    if (isset($data['password_hash'])) {
      $mapped['password_hash'] = $data['password_hash'] instanceof NeutralValue ? null : $data['password_hash'];
    }

    if (isset($data['responsable_phone_number'])) {
      $mapped['responsable_phone_number'] = $data['responsable_phone_number'] instanceof NeutralValue ? null : $data['responsable_phone_number'];
    }

    if (isset($data['street'])) {
      $mapped['street'] = $data['street'] instanceof NeutralValue ? null : $data['street'];
    }

    if (isset($data['number'])) {
      $mapped['number'] = $data['number'] instanceof NeutralValue ? null : $data['number'];
    }

    if (isset($data['neighborhood'])) {
      $mapped['neighborhood'] = $data['neighborhood'] instanceof NeutralValue ? null : $data['neighborhood'];
    }

    if (isset($data['complement'])) {
      $mapped['complement'] = $data['complement'] instanceof NeutralValue ? null : $data['complement'];
    }

    if (isset($data['city'])) {
      $mapped['city'] = $data['city'] instanceof NeutralValue ? null : $data['city'];
    }

    if (isset($data['state'])) {
      $mapped['state'] = $data['state'] instanceof NeutralValue ? null : $data['state'];
    }

    if (isset($data['cep'])) {
      $mapped['cep'] = $data['cep'] instanceof NeutralValue ? null : $data['cep'];
    }

    if (isset($data['current_points'])) {
      $mapped['current_points'] = $data['current_points'];
    }

    if (isset($data['active'])) {
      $mapped['active'] = (int) $data['active'];
    }

    return $mapped;
  }
}