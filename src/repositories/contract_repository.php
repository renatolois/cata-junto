<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\ContractModel;
use App\Core\Utils\NeutralValue;

class ContractRepository extends BaseRepository {

  protected string $table = 'contract';

  public function find_by_id(string $id): ?ContractModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_person_id(string $person_id): array {
    $result = $this->db->select($this->table, ['person_id' => $person_id]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_by_role_id(int $role_id): array {
    $result = $this->db->select($this->table, ['role_id' => $role_id]);
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

  public function create_contract(array $data): ContractModel {
    if (!isset($data['id'])) {
      $data['id'] = $this->generate_uuid();
    }

    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    return $this->find_by_id($data['id']);
  }

  public function update_contract(string $id, array $data): ContractModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_contract(string $id): bool {
    return $this->db->delete($this->table, $id);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  private function hydrate(array $data): ContractModel {
    $contract = new ContractModel();

    $contract->set_id($data['id'] ?? null);
    $contract->set_person_id($data['person_id'] ?? NeutralValue::instance());
    $contract->set_role_id((int) ($data['role_id'] ?? 0));
    $contract->set_responded_by_id($data['responded_by_id'] ?? NeutralValue::instance());
    $contract->set_contract_end_by($data['contract_end_by'] ?? NeutralValue::instance());
    $contract->set_requested_at($data['requested_at'] ?? NeutralValue::instance());
    $contract->set_responded_at($data['responded_at'] ?? NeutralValue::instance());
    $contract->set_status($data['status'] ?? 'pending');
    $contract->set_response_justification($data['response_justification'] ?? NeutralValue::instance());
    $contract->set_dismissal_justification($data['dismissal_justification'] ?? NeutralValue::instance());
    $contract->set_contract_end_at($data['contract_end_at'] ?? NeutralValue::instance());

    return $contract;
  }

  private function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['person_id'])) {
      $mapped['person_id'] = $data['person_id'] instanceof NeutralValue ? null : $data['person_id'];
    }

    if (isset($data['role_id'])) {
      $mapped['role_id'] = $data['role_id'];
    }

    if (isset($data['responded_by_id'])) {
      $mapped['responded_by_id'] = $data['responded_by_id'] instanceof NeutralValue ? null : $data['responded_by_id'];
    }

    if (isset($data['contract_end_by'])) {
      $mapped['contract_end_by'] = $data['contract_end_by'] instanceof NeutralValue ? null : $data['contract_end_by'];
    }

    if (isset($data['requested_at'])) {
      $mapped['requested_at'] = $data['requested_at'] instanceof NeutralValue ? null : $data['requested_at'];
    }

    if (isset($data['responded_at'])) {
      $mapped['responded_at'] = $data['responded_at'] instanceof NeutralValue ? null : $data['responded_at'];
    }

    if (isset($data['status'])) {
      $mapped['status'] = $data['status'];
    }

    if (isset($data['response_justification'])) {
      $mapped['response_justification'] = $data['response_justification'] instanceof NeutralValue ? null : $data['response_justification'];
    }

    if (isset($data['dismissal_justification'])) {
      $mapped['dismissal_justification'] = $data['dismissal_justification'] instanceof NeutralValue ? null : $data['dismissal_justification'];
    }

    if (isset($data['contract_end_at'])) {
      $mapped['contract_end_at'] = $data['contract_end_at'] instanceof NeutralValue ? null : $data['contract_end_at'];
    }

    return $mapped;
  }
}