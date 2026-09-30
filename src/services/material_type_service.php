<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\MaterialTypeModel;
use App\Validators\MaterialTypeValidator;
use App\Repositories\MaterialTypeRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class MaterialTypeService extends BaseService {

  public function __construct(MaterialTypeRepository $repository, MaterialTypeValidator $validator) {
    parent::__construct($repository, $validator);
  }

  public function create(array $data): array|MaterialTypeModel {
    try {
      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null) {
          return ["errors" => ["name" => "Material type name already exists."]];
        }
      }

      [$materialType, $errors] = $this->hydrate_and_validate_fillables($data);

      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->create_material_type($materialType->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create material type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating material type', ['error' => $e->getMessage()]);
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

  public function update(int|string $pk, array $data): array|MaterialTypeModel {
    try {
      $materialType = $this->repository->find_by_id($pk);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      foreach ($data as $field => $value) {
        if (!in_array($field, $materialType->get_fillables(), true)) {
          return ['errors' => [$field => 'Not fillable attribute received to update.']];
        }
      }

      if (isset($data['name'])) {
        $existing = $this->repository->find_by_name($data['name']);
        if ($existing !== null && (int) $existing->get_id() !== (int) $pk) {
          return ["errors" => ["name" => "Material type name already in use by another material type."]];
        }
      }

      foreach ($data as $field => $value) {
        $setter = 'set_' . $field;
        if (method_exists($materialType, $setter)) {
          $materialType->$setter($value);
        }
      }

      $errors = $this->validator->validate_fillables($materialType);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->update_material_type($pk, $materialType->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to update material type."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error updating material type', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function find_by_id(int $id): null|MaterialTypeModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding material type by ID', ['id' => $id, 'error' => $e->getMessage()]);
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

  public function find_by_name(string $name): null|MaterialTypeModel|array {
    try {
      return $this->repository->find_by_name($name);
    } catch (Exception $e) {
      Logger::error('Error finding material type by name', ['name' => $name, 'error' => $e->getMessage()]);
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
      Logger::error('Error finding active material types', ['error' => $e->getMessage()]);
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
      Logger::error('Error finding all material types', ['error' => $e->getMessage()]);
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

  public function activate_weight(int $pk): bool|array|MaterialTypeModel {
    try {
      $materialType = $this->repository->find_by_id($pk);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      $materialType->activate_weight();
      $result = $this->repository->update_material_type($pk, ['weight_active' => $materialType->is_weight_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate weight calculation."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating weight', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function deactivate_weight(int $pk): bool|array|MaterialTypeModel {
    try {
      $materialType = $this->repository->find_by_id($pk);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      $materialType->deactivate_weight();
      $result = $this->repository->update_material_type($pk, ['weight_active' => $materialType->is_weight_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate weight calculation."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating weight', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function activate_unit(int $pk): bool|array|MaterialTypeModel {
    try {
      $materialType = $this->repository->find_by_id($pk);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      $materialType->activate_unit();
      $result = $this->repository->update_material_type($pk, ['unit_active' => $materialType->is_unit_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate unit calculation."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating unit', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function deactivate_unit(int $pk): bool|array|MaterialTypeModel {
    try {
      $materialType = $this->repository->find_by_id($pk);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      $materialType->deactivate_unit();
      $result = $this->repository->update_material_type($pk, ['unit_active' => $materialType->is_unit_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate unit calculation."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating unit', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function calculate_price_by_weight(int $id, float $weight): float|array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_price_by_weight($weight);
    } catch (Exception $e) {
      Logger::error('Error calculating price by weight', ['id' => $id, 'weight' => $weight, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_points_by_weight(int $id, float $weight): int|array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_points_by_weight($weight);
    } catch (Exception $e) {
      Logger::error('Error calculating points by weight', ['id' => $id, 'weight' => $weight, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_price_by_units(int $id, int $units): float|array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_price_by_units($units);
    } catch (Exception $e) {
      Logger::error('Error calculating price by units', ['id' => $id, 'units' => $units, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_points_by_units(int $id, int $units): int|array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_points_by_units($units);
    } catch (Exception $e) {
      Logger::error('Error calculating points by units', ['id' => $id, 'units' => $units, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_by_weight(int $id, float $weight): array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_by_weight($weight);
    } catch (Exception $e) {
      Logger::error('Error calculating by weight', ['id' => $id, 'weight' => $weight, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_by_units(int $id, int $units): array {
    try {
      $materialType = $this->repository->find_by_id($id);
      if ($materialType === null) {
        return ["errors" => ["not_found_error" => "Material type not found."]];
      }

      return $materialType->calculate_by_units($units);
    } catch (Exception $e) {
      Logger::error('Error calculating by units', ['id' => $id, 'units' => $units, 'error' => $e->getMessage()]);
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }
}