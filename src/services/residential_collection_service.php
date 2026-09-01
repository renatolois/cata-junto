<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\ResidentialCollectionModel;
use App\Validators\ResidentialCollectionValidator;
use App\Repositories\ResidentialCollectionRepository;
use App\Repositories\ContractRepository;
use App\Repositories\MaterialTypeRepository;
use App\Repositories\CollectionLocationRepository;

class ResidentialCollectionService extends BaseService {

  private ContractRepository $contract_repository;
  private MaterialTypeRepository $material_type_repository;
  private CollectionLocationRepository $location_repository;

  public function __construct(
    ResidentialCollectionRepository $repository,
    ResidentialCollectionValidator $validator,
    ContractRepository $contract_repository,
    MaterialTypeRepository $material_type_repository,
    CollectionLocationRepository $location_repository
  ) {
    parent::__construct($repository, $validator);
    $this->contract_repository = $contract_repository;
    $this->material_type_repository = $material_type_repository;
    $this->location_repository = $location_repository;
  }

  private function hydrate_and_validate(array $data): array {
    $collection = $this->repository->hydrate($data);
    $errors = $this->validator->validate($collection);
    return [$collection, $errors];
  }

  public function create(array $data): array|ResidentialCollectionModel {
    [$collection, $errors] = $this->hydrate_and_validate($data);

    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $location = $this->location_repository->find_by_id($collection->get_collection_location_id());
    if ($location === null) {
      return ["errors" => ["collection_location_id" => "Collection location not found."]];
    }

    $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
    if ($material === null) {
      return ["errors" => ["material_type_id" => "Material type not found."]];
    }

    $collection->set_status('pending');
    $collection->set_requested_at(date('Y-m-d H:i:s'));
    $collection->set_collected_by(null);
    $collection->set_collected_at(null);
    $collection->set_quantity(null);
    $collection->set_collect_type(null);

    $result = $this->repository->create_collection($collection->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create residential collection."]];
    }

    return $result;
  }

  public function update(string|int $pk, array $data): array|ResidentialCollectionModel {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    if ($collection->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending collections can be updated."]];
    }

    foreach ($data as $field => $value) {
      if (property_exists($collection, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($collection, $setter)) {
          $collection->$setter($value);
        }
      }
    }

    $errors = $this->validator->validate($collection);
    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    if (isset($data['collection_location_id'])) {
      $location = $this->location_repository->find_by_id($collection->get_collection_location_id());
      if ($location === null) {
        return ["errors" => ["collection_location_id" => "Collection location not found."]];
      }
    }

    if (isset($data['material_type_id'])) {
      $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
      if ($material === null) {
        return ["errors" => ["material_type_id" => "Material type not found."]];
      }
    }

    $result = $this->repository->update_collection($pk, $collection->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to update collection."]];
    }

    return $result;
  }

  public function complete(
    string $pk,
    string $contract_id,
    float $quantity,
    string $collect_type
  ): array|ResidentialCollectionModel {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    if ($collection->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending collections can be completed."]];
    }

    if (!in_array($collect_type, ['weight', 'unit'])) {
      return ["errors" => ["collect_type" => "Collect type must be 'weight' or 'unit'."]];
    }

    if ($quantity <= 0) {
      return ["errors" => ["quantity" => "Quantity must be greater than zero."]];
    }

    $contract = $this->contract_repository->find_by_id($contract_id);
    if ($contract === null) {
      return ["errors" => ["contract_id" => "Contract not found."]];
    }
    if ($contract->get_status() !== 'approved' || $contract->get_contract_end_at() !== null) {
      return ["errors" => ["contract_id" => "Contract is not approved or active."]];
    }

    $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
    if ($material === null) {
      return ["errors" => ["material_type_id" => "Material type not found."]];
    }

    $collection->complete($contract_id, $quantity);
    $collection->set_collect_type($collect_type);

    $points = 0;
    if ($collect_type === 'weight') {
      $points = $material->calculate_points_by_weight($quantity);
    } else {
      $points = $material->calculate_points_by_units((int) $quantity);
    }

    if ($points > 0) {
      $location = $this->location_repository->find_by_id($collection->get_collection_location_id());
      if ($location !== null) {
        $newPoints = $location->get_current_points() + $points;
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $newPoints]
        );
      }
    }

    $result = $this->repository->update_collection($pk, $collection->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to complete collection."]];
    }

    return $result;
  }

  public function cancel(string $pk, string $justification): array|ResidentialCollectionModel {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    if ($collection->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending collections can be canceled."]];
    }

    $collection->cancel();
    $collection->set_collected_at(date('Y-m-d H:i:s'));

    $altered_data = [
      'status' => $collection->get_status(),
      'collected_at' => $collection->get_collected_at(),
      'deactivation_justification' => $justification,
    ];

    $mappedData = $this->repository->map_to_database($altered_data);
    $result = $this->repository->update_collection($pk, $mappedData);

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to cancel collection."]];
    }

    return $result;
  }

  public function activate(string $pk): bool|array {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    $collection->activate();

    $altered_data = [
      'active' => $collection->is_active(),
      'deactivation_at' => null,
      'deactivation_justification' => null,
    ];

    $mappedData = $this->repository->map_to_database($altered_data);
    $result = $this->repository->update_collection($pk, $mappedData);

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to activate collection."]];
    }

    return $result;
  }

  public function deactivate(string $pk, string $justification): array|ResidentialCollectionModel {
    $collection = $this->repository->find_by_id($pk);
    if ($collection === null) {
      return ["errors" => ["service_error" => "Collection not found."]];
    }

    $collection->deactivate($justification);

    $altered_data = [
      'active' => $collection->is_active(),
      'deactivation_at' => $collection->get_deactivation_at(),
      'deactivation_justification' => $collection->get_deactivation_justification(),
    ];

    $mappedData = $this->repository->map_to_database($altered_data);
    $result = $this->repository->update_collection($pk, $mappedData);

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deactivate collection."]];
    }

    return $result;
  }

  public function find_by_id(string $id): ?ResidentialCollectionModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_location(string $location_id): array {
    return $this->repository->find_by_collection_location($location_id);
  }

  public function find_by_status(string $status): array {
    return $this->repository->find_by_status($status);
  }

  public function find_pending(): array {
    return $this->repository->find_by_status('pending');
  }

  public function find_completed(): array {
    return $this->repository->find_by_status('completed');
  }

  public function find_canceled(): array {
    return $this->repository->find_by_status('cancelled');
  }

  public function find_active(): array {
    return $this->repository->find_active();
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