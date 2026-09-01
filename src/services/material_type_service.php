<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\MaterialTypeModel;
use App\Validators\MaterialTypeValidator;
use App\Repositories\MaterialTypeRepository;

class MaterialTypeService extends BaseService {

  public function __construct(MaterialTypeRepository $repository, MaterialTypeValidator $validator) {
    parent::__construct($repository, $validator);
  }

  private function hydrate_and_validate(array $data): array {
    $materialType = $this->repository->hydrate($data);
    $errors = $this->validator->validate($materialType);
    return [$materialType, $errors];
  }

  public function create(array $data): array|MaterialTypeModel {
    [$materialType, $errors] = $this->hydrate_and_validate($data);

    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->create_material_type($materialType->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create material type."]];
    }

    return $result;
  }

  public function update(int|string $pk, array $data): array|MaterialTypeModel {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    foreach ($data as $field => $value) {
      if (property_exists($materialType, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($materialType, $setter)) {
          $materialType->$setter($value);
        }
      }
    }

    $errors = $this->validator->validate($materialType);
    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->update_material_type($pk, $materialType->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to update material type."]];
    }

    return $result;
  }

  public function hard_delete(int $pk): bool|array {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function find_by_id(int $id): ?MaterialTypeModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_name(string $name): ?MaterialTypeModel {
    return $this->repository->find_by_name($name);
  }

  public function find_all_active(): array {
    return $this->repository->find_all_active();
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }

  public function activate_weight(int $pk): bool|array {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    $materialType->activate_weight();
    $result = $this->repository->update_material_type($pk, ['weight_active' => $materialType->is_weight_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to activate weight calculation."]];
    }

    return $result;
  }

  public function deactivate_weight(int $pk): bool|array {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    $materialType->deactivate_weight();
    $result = $this->repository->update_material_type($pk, ['weight_active' => $materialType->is_weight_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deactivate weight calculation."]];
    }

    return $result;
  }

  public function activate_unit(int $pk): bool|array {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    $materialType->activate_unit();
    $result = $this->repository->update_material_type($pk, ['unit_active' => $materialType->is_unit_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to activate unit calculation."]];
    }

    return $result;
  }

  public function deactivate_unit(int $pk): bool|array {
    $materialType = $this->repository->find_by_id($pk);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    $materialType->deactivate_unit();
    $result = $this->repository->update_material_type($pk, ['unit_active' => $materialType->is_unit_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deactivate unit calculation."]];
    }

    return $result;
  }

  public function calculate_price_by_weight(int $id, float $weight): float|array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_price_by_weight($weight);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_points_by_weight(int $id, float $weight): int|array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_points_by_weight($weight);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_price_by_units(int $id, int $units): float|array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_price_by_units($units);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_points_by_units(int $id, int $units): int|array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_points_by_units($units);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_by_weight(int $id, float $weight): array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_by_weight($weight);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }

  public function calculate_by_units(int $id, int $units): array {
    $materialType = $this->repository->find_by_id($id);
    if ($materialType === null) {
      return ["errors" => ["service_error" => "Material type not found."]];
    }

    try {
      return $materialType->calculate_by_units($units);
    } catch (\Exception $e) {
      return ["errors" => ["calculation_error" => $e->getMessage()]];
    }
  }
}