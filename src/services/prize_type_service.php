<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PrizeTypeModel;
use App\Validators\PrizeTypeValidator;
use App\Repositories\PrizeTypeRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class PrizeTypeService extends BaseService {

  public function __construct(PrizeTypeRepository $repository, PrizeTypeValidator $validator) {
    parent::__construct($repository, $validator);
  }

  public function create(array $data): array|PrizeTypeModel {
    try {
      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null) {
          return ["errors" => ["name" => "Prize type name already exists."]];
        }
      }

      [$prizeType, $errors] = $this->hydrate_and_validate_fillables($data);

      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->create_prize_type($prizeType->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create prize type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating prize type', ['error' => $e->getMessage()]);
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

  public function update(int|string $pk, array $data): array|PrizeTypeModel {
    try {
      $prizeType = $this->repository->find_by_id($pk);
      if ($prizeType === null) {
        return ["errors" => ["not_found_error" => "Prize type not found."]];
      }

      foreach ($data as $field => $value) {
        if (!in_array($field, $prizeType->get_fillables(), true)) {
          return ['errors' => [$field => 'Not fillable attribute received to update.']];
        }
      }

      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null && (int) $existing->get_id() !== (int) $pk) {
          return ["errors" => ["name" => "Prize type name already in use by another prize type."]];
        }
      }

      foreach ($data as $field => $value) {
        $setter = 'set_' . $field;
        if (method_exists($prizeType, $setter)) {
          $prizeType->$setter($value);
        }
      }

      $errors = $this->validator->validate_fillables($prizeType);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->update_prize_type($pk, $prizeType->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to update prize type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error updating prize type', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function find_by_id(int $id): null|PrizeTypeModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding prize type by ID', ['id' => $id, 'error' => $e->getMessage()]);
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

  public function find_by_name(string $name): null|PrizeTypeModel|array {
    try {
      return $this->repository->find_by_name($name);
    } catch (Exception $e) {
      Logger::error('Error finding prize type by name', ['name' => $name, 'error' => $e->getMessage()]);
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
      Logger::error('Error finding active prize types', ['error' => $e->getMessage()]);
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
      Logger::error('Error finding all prize types', ['error' => $e->getMessage()]);
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

  public function activate(int $pk): bool|array|PrizeTypeModel {
    try {
      $prizeType = $this->repository->find_by_id($pk);
      if ($prizeType === null) {
        return ["errors" => ["not_found_error" => "Prize type not found."]];
      }

      $prizeType->activate();
      $result = $this->repository->update_prize_type($pk, ['active' => $prizeType->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate prize type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating prize type', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function deactivate(int $pk): bool|array|PrizeTypeModel {
    try {
      $prizeType = $this->repository->find_by_id($pk);
      if ($prizeType === null) {
        return ["errors" => ["not_found_error" => "Prize type not found."]];
      }

      $prizeType->deactivate();
      $result = $this->repository->update_prize_type($pk, ['active' => $prizeType->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate prize type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating prize type', ['pk' => $pk, 'error' => $e->getMessage()]);
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