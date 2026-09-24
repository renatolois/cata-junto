<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\RoleModel;
use App\Validators\RoleValidator;
use App\Repositories\RoleRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class RoleService extends BaseService {

  public function __construct(RoleRepository $repository, RoleValidator $validator) {
    parent::__construct($repository, $validator);
  }

  public function create(array $data): array|RoleModel {
    try {
      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null) {
          return ["errors" => ["name" => "Role name already exists."]];
        }
      }

      [$role, $errors] = $this->hydrate_and_validate_fillables($data);

      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->create_role($role->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create role."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating role', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function update(string|int $pk, array $data): array|RoleModel {
    try {
      $role = $this->repository->find_by_id($pk);
      if ($role === null) {
        return ["errors" => ["not_found_error" => "Role not found."]];
      }

      foreach ($data as $field => $value) {
        if (!in_array($field, $role->get_fillables(), true)) {
          return ['errors' => [$field => 'Not fillable attribute received to update.']];
        }
      }

      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null && (int) $existing->get_id() !== (int) $pk) {
          return ["errors" => ["name" => "Role name already in use by another role."]];
        }
      }

      foreach ($data as $field => $value) {
        $setter = 'set_' . $field;
        if (method_exists($role, $setter)) {
          $role->$setter($value);
        }
      }

      $errors = $this->validator->validate_fillables($role);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->update_role($pk, $role->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to update role."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error updating role', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_by_id(int $id): ?RoleModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding role by ID', ['id' => $id, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_by_name(string $name): ?RoleModel|array {
    try {
      return $this->repository->find_by_name($name);
    } catch (Exception $e) {
      Logger::error('Error finding role by name', ['name' => $name, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_all_active(): array {
    try {
      return $this->repository->find_all_active();
    } catch (Exception $e) {
      Logger::error('Error finding active roles', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_all(): array {
    try {
      return $this->repository->find_all();
    } catch (Exception $e) {
      Logger::error('Error finding all roles', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function activate(int $pk): bool|array {
    try {
      $role = $this->repository->find_by_id($pk);
      if ($role === null) {
        return ["errors" => ["not_found_error" => "Role not found."]];
      }

      $role->activate();
      $result = $this->repository->update_role($pk, ['active' => $role->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate role."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating role', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function deactivate(int $pk): bool|array {
    try {
      $role = $this->repository->find_by_id($pk);
      if ($role === null) {
        return ["errors" => ["not_found_error" => "Role not found."]];
      }

      $role->deactivate();
      $result = $this->repository->update_role($pk, ['active' => $role->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate role."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating role', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }
}