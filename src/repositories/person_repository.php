<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\PersonModel;
use App\Core\Utils\NeutralValue;

class PersonRepository extends BaseRepository {

  protected string $table = 'pessoa';

  public function find_by_id(string $id): ?PersonModel {
    $result = $this->db->select($this->table, ['id' => $id]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_email(string $email): ?PersonModel {
    $result = $this->db->select($this->table, ['email' => $email]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_by_cpf(string $cpf): ?PersonModel {
    $result = $this->db->select($this->table, ['cpf' => $cpf]);
    return empty($result) ? null : $this->hydrate($result[0]);
  }

  public function find_all_active(): array {
    $result = $this->db->select($this->table, ['ativo' => 1]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function search_by_name(string $name): array {
    $sql = "SELECT * FROM {$this->table} WHERE nome LIKE ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(["%{$name}%"]);
    $result = $stmt->fetchAll();
    return array_map([$this, 'hydrate'], $result);
  }

  public function create_person(array $data): PersonModel {
    if (!isset($data['id'])) {
      $data['id'] = $this->generate_uuid();
    }

    $db_data = $this->map_to_database($data);
    $this->db->insert($this->table, $db_data);
    return $this->find_by_id($data['id']);
  }

  public function update_person(string $id, array $data): PersonModel {
    $db_data = $this->map_to_database($data);
    $this->db->update($this->table, $id, $db_data);
    return $this->find_by_id($id);
  }

  public function delete_person(string $id): bool {
    return $this->db->update($this->table, $id, ['ativo' => 0]);
  }

  public function count(): int {
    $sql = "SELECT COUNT(*) as total FROM {$this->table}";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch();
    return (int) $result['total'];
  }

  private function hydrate(array $data): PersonModel {
    $person = new PersonModel();

    $person->set_id($data['id'] ?? null);
    $person->set_cpf($data['cpf'] ?? NeutralValue::instance());
    $person->set_name($data['nome'] ?? NeutralValue::instance());
    $person->set_email($data['email'] ?? NeutralValue::instance());
    $person->set_phone_number($data['telefone'] ?? NeutralValue::instance());
    $person->set_birth_date($data['data_nascimento'] ?? NeutralValue::instance());
    $person->set_current_points($data['pontos_atuais'] ?? 0);
    $person->set_active($data['ativo'] ?? true);
    $person->set_verified_email($data['email_verificado'] ?? false);
    $person->set_password_hash($data['senha_hash'] ?? NeutralValue::instance());

    return $person;
  }

  private function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['cpf'])) {
      $mapped['cpf'] = $data['cpf'] instanceof NeutralValue ? null : $data['cpf'];
    }

    if (isset($data['name'])) {
      $mapped['nome'] = $data['name'] instanceof NeutralValue ? null : $data['name'];
    }

    if (isset($data['email'])) {
      $mapped['email'] = $data['email'] instanceof NeutralValue ? null : $data['email'];
    }

    if (isset($data['phone_number'])) {
      $mapped['telefone'] = $data['phone_number'] instanceof NeutralValue ? null : $data['phone_number'];
    }

    if (isset($data['birth_date'])) {
      $mapped['data_nascimento'] = $data['birth_date'] instanceof NeutralValue ? null : $data['birth_date'];
    }

    if (isset($data['current_points'])) {
      $mapped['pontos_atuais'] = $data['current_points'];
    }

    if (isset($data['active'])) {
      $mapped['ativo'] = (int) $data['active'];
    }

    if (isset($data['verified_email'])) {
      $mapped['email_verificado'] = (int) $data['verified_email'];
    }

    if (isset($data['password_hash'])) {
      $mapped['senha_hash'] = $data['password_hash'] instanceof NeutralValue ? null : $data['password_hash'];
    }

    return $mapped;
  }

  private function generate_uuid(): string {
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