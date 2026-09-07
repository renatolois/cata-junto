<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PersonModel;
use App\Validators\PersonValidator;
use App\Repositories\PersonRepository;

class PersonService extends BaseService {
  public function __construct(PersonRepository $repository, PersonValidator $validator) {
    parent::__construct($repository, $validator);
  }

  public function create(array $data): array|PersonModel {
    [$person, $errors] = $this->hydrate_and_validate($data);

    if( !empty($errors) ) return ["errors" => $errors];

    if (isset($data['email'])) {
      $existing = $this->repository->find_by_email($data['email']);
      if ($existing !== null) {
        return ["errors" => ["email" => "Email already registered."]];
      }
    }

    if (isset($data['cpf'])) {
      $existing = $this->repository->find_by_cpf($data['cpf']);
      if ($existing !== null) {
        return ["errors" => ["cpf" => "CPF already registered."]];
      }
    }

    if (isset($data['password'])) {
      $person->set_password($data['password']);
    }

    $result = $this->repository->create_person($person->get_attributes());
    if($result === null) {
      return ["errors" => ["service_error" => "Failed to create person."]];
    }

    return $result;
  }

  public function update(string|int $pk, array $data): array|PersonModel {
    $person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }

    if (isset($data['email'])) {
      $existing = $this->repository->find_by_email($data['email']);
      if ($existing !== null && $existing->get_id() !== $pk) {
        return ["errors" => ["email" => "Email already in use by another user."]];
      }
    }

    if (isset($data['cpf'])) {
      $existing = $this->repository->find_by_cpf($data['cpf']);
      if ($existing !== null && $existing->get_id() !== $pk) {
        return ["errors" => ["cpf" => "CPF already in use by another user."]];
      }
    }
    
    foreach ($data as $field => $value) {
      if (property_exists($person, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($person, $setter)) {
          $person->$setter($value);
        }
      }
    }

    if (isset($data["password"])) {
      $person->set_password($data["password"]);
    }

    $errors = $this->validator->validate($person);

    if( !empty($errors) ) return ["errors" => $errors];

    $result = $this->repository->update_person($pk, $person->get_attributes());
    if($result === null) {
      $errors['service_error'] = ["failed to update person."];
      return ["errors" => $errors];
    }

    return $result;
  }

  public function hard_delete($pk): bool|array {
    $person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function find_by_id(string $id): ?PersonModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_email(string $email): ?PersonModel {
    return $this->repository->find_by_email($email);
  }

  public function find_by_cpf(string $cpf): ?PersonModel {
    return $this->repository->find_by_cpf($cpf);
  }

  public function find_all_active(): array {
    return $this->repository->find_all_active();
  }

  public function activate(string $pk): bool|array {
    $person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }
  
    $person->activate();
    $result = $this->repository->update_person($pk, ['active' => $person->is_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to activate person."]];
    }
  
    return $result;
  }

  public function deactivate(string $pk): bool|array {
    $person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }
  
    $person->deactivate();
    $result = $this->repository->update_person($pk, ['active' => $person->is_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deactivate person."]];
    }
  
    return $result;
  }

  public function verify_password(string $id, string $password): bool|array {
    $person = $this->repository->find_by_id($id);
    if ($person === null) {
        return ["errors" => ["service_error" => "Person not found."]];
    }
    return $person->verify_password($password);
  }

  public function add_points(string $id, int $points): bool|array {
    $person = $this->repository->find_by_id($id);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }

    $newPoints = $person->get_current_points() + $points;
    $person->set_current_points($newPoints);

    $result = $this->repository->update_person($id, ['current_points' => $newPoints]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to add points."]];
    }

    return $result;
  }

  public function deduct_points(string $id, int $points): bool|array {
    $person = $this->repository->find_by_id($id);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }

    $current = $person->get_current_points();
    if ($current < $points) {
      return ["errors" => ["current_points" => "Insufficient points."]];
    }

    $newPoints = $current - $points;
    $person->set_current_points($newPoints);

    $result = $this->repository->update_person($id, ['current_points' => $newPoints]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deduct points."]];
    }

    return $result;
  }
}