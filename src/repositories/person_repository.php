<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Base\BaseRepository;
use App\Models\PersonModel;
use App\Core\Utils\NeutralValue;

class PersonRepository extends BaseRepository {

  protected string $table = 'person';

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
    $result = $this->db->select($this->table, ['active' => 1]);
    return array_map([$this, 'hydrate'], $result);
  }

  public function find_all(): array {
    $result = $this->db->select($this->table);
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
    return $this->db->update($this->table, $id, ['active' => 0]);
  }

  public function hydrate(array $data): PersonModel {
    $person = new PersonModel();

    $person->set_id($data['id'] ?? null);
    $person->set_cpf($data['cpf'] ?? NeutralValue::instance());
    $person->set_name($data['name'] ?? NeutralValue::instance());
    $person->set_email($data['email'] ?? NeutralValue::instance());
    $person->set_phone_number($data['phone_number'] ?? NeutralValue::instance());
    $person->set_birth_date($data['birth_date'] ?? NeutralValue::instance());
    $person->set_current_points((int) ($data['current_points'] ?? 0));
    $person->set_active((bool) ($data['active'] ?? true));
    $person->set_verified_email((bool) ($data['verified_email'] ?? false));
    $person->set_password_hash($data['password_hash'] ?? NeutralValue::instance());

    return $person;
  }

  public function map_to_database(array $data): array {
    $mapped = [];

    if (isset($data['id'])) {
      $mapped['id'] = $data['id'];
    }

    if (isset($data['cpf'])) {
      $mapped['cpf'] = $data['cpf'] instanceof NeutralValue ? null : $data['cpf'];
    }

    if (isset($data['name'])) {
      $mapped['name'] = $data['name'] instanceof NeutralValue ? null : $data['name'];
    }

    if (isset($data['email'])) {
      $mapped['email'] = $data['email'] instanceof NeutralValue ? null : $data['email'];
    }

    if (isset($data['phone_number'])) {
      $mapped['phone_number'] = $data['phone_number'] instanceof NeutralValue ? null : $data['phone_number'];
    }

    if (isset($data['birth_date'])) {
      $mapped['birth_date'] = $data['birth_date'] instanceof NeutralValue ? null : $data['birth_date'];
    }

    if (isset($data['current_points'])) {
      $mapped['current_points'] = (int) $data['current_points'];
    }

    if (isset($data['active'])) {
      $mapped['active'] = (int) $data['active'];
    }

    if (isset($data['verified_email'])) {
      $mapped['verified_email'] = (int) $data['verified_email'];
    }

    if (isset($data['password_hash'])) {
      $mapped['password_hash'] = $data['password_hash'] instanceof NeutralValue ? null : $data['password_hash'];
    }

    return $mapped;
  }
}