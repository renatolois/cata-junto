<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\RoleModel;
use App\Validators\RoleValidator;
use App\Repositories\RoleRepository;

class RoleService extends BaseService {

  private RoleRepository $repository;
  private RoleValidator $validator;

  public function __construct(RoleRepository $repository, RoleValidator $validator) {
    parent::__construct($repository, $validator);
    $this->repository = $repository;
    $this->validator = $validator;
  }

  private function hydrate_and_validate(array $data): array {
    $role = $this->repository->hydrate($data);
    $errors = $this->validator->validate($role);
    return [$role, $errors];
  }

  public function create(array $data): array|RoleModel {
    [$role, $errors] = $this->hydrate_and_validate($data);

    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->create_role($role->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create role."]];
    }

    return $result;
  }

  public function update(int $pk, array $data): array|RoleModel {
    $role = $this->repository->find_by_id($pk);
    if ($role === null) {
      return ["errors" => ["service_error" => "Role not found."]];
    }

    foreach ($data as $field => $value) {
      if (property_exists($role, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($role, $setter)) {
          $role->$setter($value);
        }
      }
    }

    $errors = $this->validator->validate($role);
    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->update_role($pk, $role->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to update role."]];
    }

    return $result;
  }

  public function hard_delete(int $pk): bool|array {
    $role = $this->repository->find_by_id($pk);
    if ($role === null) {
      return ["errors" => ["service_error" => "Role not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function find_by_id(int $id): ?RoleModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_name(string $name): ?RoleModel {
    return $this->repository->find_by_name($name);
  }

  public function find_all_active(): array {
    return $this->repository->find_all_active();
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }

  public function count(): int {
    return $this->repository->count();
  }

	public function activate(int $pk): bool|array {
	  $role = $this->repository->find_by_id($pk);
	  if ($role === null) {
	    return ["errors" => ["service_error" => "Role not found."]];
	  }
	
	  $role->activate();
	  $result = $this->repository->update_role($pk, ['active' => $role->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to activate role."]];
	  }
	
	  return $result;
	}
	
	public function deactivate(int $pk): bool|array {
	  $role = $this->repository->find_by_id($pk);
	  if ($role === null) {
	    return ["errors" => ["service_error" => "Role not found."]];
	  }
	
	  $role->deactivate();
	  $result = $this->repository->update_role($pk, ['active' => $role->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to deactivate role."]];
	  }
	
	  return $result;
	}
}