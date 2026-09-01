<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\InPersonCollectionModel;
use App\Validators\InPersonCollectionValidator;
use App\Repositories\InPersonCollectionRepository;
use App\Repositories\ContractRepository;
use App\Repositories\MaterialTypeRepository;

class InPersonCollectionService extends BaseService {

  private ContractRepository $contract_repository;
  private MaterialTypeRepository $material_type_repository;
  private PersonService $person_service;

  public function __construct(
    InPersonCollectionRepository $repository,
    InPersonCollectionValidator $validator,
    ContractRepository $contract_repository,
    MaterialTypeRepository $material_type_repository,
    PersonService $person_service
  ) {
    parent::__construct($repository, $validator);
    $this->contract_repository = $contract_repository;
    $this->material_type_repository = $material_type_repository;
    $this->person_service = $person_service;
  }

  private function hydrate_and_validate(array $data): array {
    $collection = $this->repository->hydrate($data);
    $errors = $this->validator->validate($collection);
    return [$collection, $errors];
  }

  public function create(array $data): array|InPersonCollectionModel {
    [$collection, $errors] = $this->hydrate_and_validate($data);
    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $contract = $this->contract_repository->find_by_id($data['contract_id']);
    if ($contract === null) {
      return ["errors" => ["contract_id" => "Contract not found."]];
    }
    if ($contract->get_status() !== 'approved' || $contract->get_contract_end_at() !== null) {
      return ["errors" => ["contract_id" => "Contract is not active."]];
    }

    $material = $this->material_type_repository->find_by_id($data['material_type_id']);
    if ($material === null) {
      return ["errors" => ["material_type_id" => "Material type not found."]];
    }

    $collection->set_collected_by($data['contract_id']);
    $collection->set_material_type_id($data['material_type_id']);
    $collection->set_collect_type($data['collect_type']);
    $collection->set_quantity($data['quantity']);
    $collection->set_collected_at(date('Y-m-d H:i:s'));
    $collection->set_active(true);

    $points = 0;
    if ($data['collect_type'] === 'weight') {
      $points = $material->calculate_points_by_weight($data['quantity']);
    } else {
      $points = $material->calculate_points_by_units((int) $data['quantity']);
    }

    $result = $this->repository->create_in_person_collection($collection->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create in-person collection."]];
    }

    if ($points > 0) {
      $person_id = $contract->get_person_id();
      $addResult = $this->person_service->add_points($person_id, $points);
      if (isset($addResult['errors'])) {
        return ["errors" => ["service_error" => "Failed to add points to person."]];
      }
    }

    return $result;
  }

  public function find_by_id(string $id): ?InPersonCollectionModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_contract(string $contract_id): array {
    return $this->repository->find_by_collected_by($contract_id);
  }

  public function find_by_material(int $material_type_id): array {
    return $this->repository->find_by_material_type_id($material_type_id);
  }

  public function find_by_collect_type(string $collect_type): array {
    return $this->repository->find_by_collect_type($collect_type);
  }

  public function find_active(): array {
    return $this->repository->find_all_active();
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }

  public function count(): int {
    return $this->repository->count();
  }

  public function hard_delete(string $pk): bool|array {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    return $this->repository->delete($pk);
  }
}