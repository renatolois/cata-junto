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
use App\Repositories\RoleRepository;
use App\Core\Utils\NeutralValue;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class ResidentialCollectionService extends BaseService {

  private ContractRepository $contract_repository;
  private MaterialTypeRepository $material_type_repository;
  private CollectionLocationRepository $location_repository;
  private RoleRepository $role_repository;

  public function __construct(
    ResidentialCollectionRepository $repository,
    ResidentialCollectionValidator $validator,
    ContractRepository $contract_repository,
    MaterialTypeRepository $material_type_repository,
    CollectionLocationRepository $location_repository,
    RoleRepository $role_repository
  ) {
    parent::__construct($repository, $validator);
    $this->contract_repository = $contract_repository;
    $this->material_type_repository = $material_type_repository;
    $this->location_repository = $location_repository;
    $this->role_repository = $role_repository;
  }

  public function create(array $data): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->hydrate($data);

      $collection->set_status('pending');
      $collection->set_requested_at(date('Y-m-d H:i:s'));
      $collection->set_active(true);
      $collection->set_conceded_points(0);

      $errors = $this->validator->validate_fillables($collection);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $location = $this->location_repository->find_by_id($collection->get_collection_location_id());
      if ($location === null) {
        return ["errors" => ["collection_location_id" => "Collection location not found."]];
      }
      if (!$location->is_active()) {
        return ["errors" => ["collection_location_id" => "Collection location is not active."]];
      }

      $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
      if ($material === null) {
        return ["errors" => ["material_type_id" => "Material type not found."]];
      }

      $result = $this->repository->create_residential_collection($collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating residential collection', ['error' => $e->getMessage()]);
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

  public function update(int|string $pk, array $data): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      if ($collection->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending collections can be updated."]];
      }

      foreach ($data as $field => $value) {
        if (!in_array($field, $collection->get_fillables(), true)) {
          return ["errors" => [$field => "Not fillable attribute received to update."]];
        }
      }

      $allowed = ['description', 'material_type_id', 'collect_type'];
      foreach ($data as $field => $value) {
        if (!in_array($field, $allowed, true)) {
          return ["errors" => [$field => "This attribute cannot be updated."]];
        }
      }

      foreach ($data as $field => $value) {
        $setter = 'set_' . $field;
        if (method_exists($collection, $setter)) {
          $collection->$setter($value);
        }
      }

      if (isset($data['material_type_id'])) {
        $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
        if ($material === null) {
          return ["errors" => ["material_type_id" => "Material type not found."]];
        }
      }

      $errors = $this->validator->validate_fillables($collection);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to update residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error updating residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function complete(string $pk, string $contract_id, float $quantity): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      if ($collection->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending collections can be completed."]];
      }

      if ($quantity <= 0) {
        return ["errors" => ["quantity" => "Quantity must be greater than zero."]];
      }

      $contract = $this->contract_repository->find_by_id($contract_id);
      if ($contract === null) {
        return ["errors" => ["contract_id" => "Contract not found."]];
      }

      $end_at = $contract->get_contract_end_at();
      if ($contract->get_status() !== 'approved' || ($end_at !== null && !($end_at instanceof NeutralValue))) {
        return ["errors" => ["contract_id" => "Contract is not active."]];
      }

      $material = $this->material_type_repository->find_by_id($collection->get_material_type_id());
      if ($material === null) {
        return ["errors" => ["material_type_id" => "Material type not found."]];
      }

      $location = $this->location_repository->find_by_id($collection->get_collection_location_id());
      if ($location === null) {
        return ["errors" => ["collection_location_id" => "Collection location not found."]];
      }

      $collect_type = (string) $collection->get_collect_type();

      try {
        if ($collect_type === 'weight') {
          $conceded_points = $material->calculate_points_by_weight($quantity);
        } else {
          $conceded_points = $material->calculate_points_by_units((int) $quantity);
        }
      } catch (Exception $e) {
        return ["errors" => ["collect_type" => $e->getMessage()]];
      }

      $this->repository->begin_transaction();

      try {
        $collection->complete($contract_id, $quantity, $conceded_points);

        $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
        if ($result === null) {
          throw new Exception("Failed to update residential collection.");
        }

        $new_points = $location->get_current_points() + $conceded_points;
        $location_result = $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );
        if ($location_result === null) {
          throw new Exception("Failed to add points to collection location.");
        }

        $this->repository->commit();
        return $result;
      } catch (Exception $e) {
        $this->repository->rollback();
        throw $e;
      }
    } catch (Exception $e) {
      Logger::error('Error completing residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function cancel(string $pk, string|NeutralValue $justification): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      if ($collection->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending collections can be canceled."]];
      }

      $collection->cancel($justification);

      $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to cancel residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error canceling residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function reject(string $pk, string $moderator_contract_id, string $justification): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      if ($collection->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending collections can be rejected."]];
      }

      $moderator_contract = $this->contract_repository->find_by_id($moderator_contract_id);
      if ($moderator_contract === null) {
        return ["errors" => ["rejected_by_id" => "Contract not found."]];
      }
      if ($moderator_contract->get_status() !== 'approved' || $moderator_contract->get_contract_end_at() !== null) {
        return ["errors" => ["rejected_by_id" => "Contract is not active."]];
      }

      $role = $this->role_repository->find_by_id($moderator_contract->get_role_id());
      if ($role === null) {
        return ["errors" => ["rejected_by_id" => "Role not found."]];
      }
      if ($role->get_name() !== 'admin') {
        return ["errors" => ["rejected_by_id" => "Has not permission."]];
      }
      if (!$role->is_active()) {
        return ["errors" => ["rejected_by_id" => "Administrator role is inactivated."]];
      }

      $collection->reject($moderator_contract_id, $justification);

      $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to reject residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error rejecting residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function activate(string $pk): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      $collection->activate();

      $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function deactivate(string $pk, string $justification): array|ResidentialCollectionModel {
    try {
      $collection = $this->repository->find_by_id($pk);
      if ($collection === null) {
        return ["errors" => ["not_found_error" => "Residential collection not found."]];
      }

      if (!$collection->is_active()) {
        return ["errors" => ["active" => "Collection is already deactivated."]];
      }

      $collection->deactivate($justification);

      $result = $this->repository->update_residential_collection($pk, $collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate residential collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating residential collection', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function find_by_id(string $id): null|ResidentialCollectionModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding residential collection by ID', ['id' => $id, 'error' => $e->getMessage()]);
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

  public function find_by_location(string $location_id): array {
    try {
      return $this->repository->find_by_collection_location_id($location_id);
    } catch (Exception $e) {
      Logger::error('Error finding residential collections by location', ['location_id' => $location_id, 'error' => $e->getMessage()]);
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

  public function find_by_collected_by(string $contract_id): array {
    try {
      return $this->repository->find_by_collected_by($contract_id);
    } catch (Exception $e) {
      Logger::error('Error finding residential collections by collected_by', [
        'contract_id' => $contract_id,
        'error' => $e->getMessage(),
      ]);
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

  public function find_by_location_and_status(string $location_id, string $status): array {
    try {
      return $this->repository->find_by_collection_location_id_and_status($location_id, $status);
    } catch (Exception $e) {
      Logger::error('Error finding residential collections by location and status', ['location_id' => $location_id, 'status' => $status, 'error' => $e->getMessage()]);
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

  public function find_by_status(string $status): array {
    try {
      return $this->repository->find_by_status($status);
    } catch (Exception $e) {
      Logger::error('Error finding residential collections by status', ['status' => $status, 'error' => $e->getMessage()]);
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

  public function find_pending(): array {
    return $this->find_by_status('pending');
  }

  public function find_completed(): array {
    return $this->find_by_status('completed');
  }

  public function find_cancelled(): array {
    return $this->find_by_status('cancelled');
  }

  public function find_rejected(): array {
    return $this->find_by_status('rejected');
  }

  public function find_active(): array {
    try {
      return $this->repository->find_all_active();
    } catch (Exception $e) {
      Logger::error('Error finding active residential collections', ['error' => $e->getMessage()]);
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
      Logger::error('Error finding all residential collections', ['error' => $e->getMessage()]);
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

  public function find_by_person(string $person_id): array {
    try {
      $contracts = $this->contract_repository->find_by_person_id($person_id);
      $all = [];

      foreach ($contracts as $contract) {
        $collections = $this->repository->find_by_collected_by($contract->get_id());
        foreach ($collections as $c) {
          $all[] = $c;
        }
      }

      return $all;
    } catch (Exception $e) {
      Logger::error('Error finding residential collections by person', [
        'person_id' => $person_id,
        'error' => $e->getMessage(),
      ]);
      return [
        'errors' => [
          'server_error' => AppConstants::RUN_MODE === 'debug'
            ? $e->getMessage()
            : 'unexpected_error',
        ],
      ];
    }
  }
}