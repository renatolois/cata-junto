<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PrizeTypeModel;
use App\Validators\PrizeTypeValidator;
use App\Repositories\PrizeTypeRepository;

class PrizeTypeService extends BaseService {

  private PrizeTypeRepository $repository;
  private PrizeTypeValidator $validator;

  public function __construct(PrizeTypeRepository $repository, PrizeTypeValidator $validator) {
    parent::__construct($repository, $validator);
    $this->repository = $repository;
    $this->validator = $validator;
  }

  private function hydrate_and_validate(array $data): array {
    $prizeType = $this->repository->hydrate($data);
    $errors = $this->validator->validate($prizeType);
    return [$prizeType, $errors];
  }

  public function create(array $data): array|PrizeTypeModel {
    [$prizeType, $errors] = $this->hydrate_and_validate($data);

    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->create_prize_type($prizeType->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create prize type."]];
    }

    return $result;
  }

  public function update(int $pk, array $data): array|PrizeTypeModel {
    $prizeType = $this->repository->find_by_id($pk);
    if ($prizeType === null) {
      return ["errors" => ["service_error" => "Prize type not found."]];
    }

    foreach ($data as $field => $value) {
      if (property_exists($prizeType, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($prizeType, $setter)) {
          $prizeType->$setter($value);
        }
      }
    }

    $errors = $this->validator->validate($prizeType);
    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $result = $this->repository->update_prize_type($pk, $prizeType->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to update prize type."]];
    }

    return $result;
  }

  public function hard_delete(int $pk): bool|array {
    $prizeType = $this->repository->find_by_id($pk);
    if ($prizeType === null) {
      return ["errors" => ["service_error" => "Prize type not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function find_by_id(int $id): ?PrizeTypeModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_name(string $name): ?PrizeTypeModel {
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
	  $prizeType = $this->repository->find_by_id($pk);
	  if ($prizeType === null) {
	    return ["errors" => ["service_error" => "Prize type not found."]];
	  }
	
	  $prizeType->activate();
	  $result = $this->repository->update_prize_type($pk, ['active' => $prizeType->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to activate prize type."]];
	  }
	
	  return $result;
	}
	
	public function deactivate(int $pk): bool|array {
	  $prizeType = $this->repository->find_by_id($pk);
	  if ($prizeType === null) {
	    return ["errors" => ["service_error" => "Prize type not found."]];
	  }
	
	  $prizeType->deactivate();
	  $result = $this->repository->update_prize_type($pk, ['active' => $prizeType->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to deactivate prize type."]];
	  }
	
	  return $result;
	}
}