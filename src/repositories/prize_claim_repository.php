<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\PrizeClaimModel;
use App\Core\Utils\NeutralValue;

class PrizeClaimRepository extends BaseRepository {

  protected string $table = 'prize_claim';

  public function find_by_id(int $id): ?PrizeClaimModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_claimed_by(string $claimed_by): array {
    $result = $this->db->select($this->table, ['claimed_by' => $claimed_by]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_prize_type_id(int $prize_type_id): array {
    $result = $this->db->select($this->table, ['prize_type_id' => $prize_type_id]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_collection_point_id(string $collection_point_id): array {
    $result = $this->db->select($this->table, ['collection_point_id' => $collection_point_id]);
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

  public function create_prize_claim(array $data): PrizeClaimModel {
    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    $id = (int) $this->db->lastInsertId();
    return $this->find_by_id($id);
  }

  public function update_prize_claim(int $id, array $data): PrizeClaimModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_prize_claim(int $id): bool {
    return $this->db->delete($this->table, $id);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  private function hydrate(array $data): PrizeClaimModel {
    $prize_claim = new PrizeClaimModel();

    $prize_claim->set_id($data['id'] ?? null);
    $prize_claim->set_claimed_by($data['claimed_by'] ?? NeutralValue::instance());
    $prize_claim->set_prize_type_id((int) ($data['prize_type_id'] ?? 0));
    $prize_claim->set_collection_point_id($data['collection_point_id'] ?? NeutralValue::instance());
    $prize_claim->set_status($data['status'] ?? 'pending');
    $prize_claim->set_claimed_at($data['claimed_at'] ?? NeutralValue::instance());
    $prize_claim->set_collected_at($data['collected_at'] ?? NeutralValue::instance());

    return $prize_claim;
  }

  private function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['claimed_by'])) {
      $mapped['claimed_by'] = $data['claimed_by'] instanceof NeutralValue ? null : $data['claimed_by'];
    }

    if (isset($data['prize_type_id'])) {
      $mapped['prize_type_id'] = $data['prize_type_id'];
    }

    if (isset($data['collection_point_id'])) {
      $mapped['collection_point_id'] = $data['collection_point_id'] instanceof NeutralValue ? null : $data['collection_point_id'];
    }

    if (isset($data['status'])) {
      $mapped['status'] = $data['status'];
    }

    if (isset($data['claimed_at'])) {
      $mapped['claimed_at'] = $data['claimed_at'] instanceof NeutralValue ? null : $data['claimed_at'];
    }

    if (isset($data['collected_at'])) {
      $mapped['collected_at'] = $data['collected_at'] instanceof NeutralValue ? null : $data['collected_at'];
    }

    return $mapped;
  }
}