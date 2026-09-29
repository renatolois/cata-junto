<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\InPersonCollectionModel;
use App\Validators\InPersonCollectionValidator;
use App\Repositories\InPersonCollectionRepository;
use App\Repositories\ContractRepository;
use App\Repositories\MaterialTypeRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class InPersonCollectionService extends BaseService {

  private ContractRepository $contract_repository;
  private MaterialTypeRepository $material_type_repository;

  public function __construct(
    InPersonCollectionRepository $repository,
    InPersonCollectionValidator $validator,
    ContractRepository $contract_repository,
    MaterialTypeRepository $material_type_repository
  ) {
    parent::__construct($repository, $validator);
    $this->contract_repository = $contract_repository;
    $this->material_type_repository = $material_type_repository;
  }

  public function create(array $data): array|InPersonCollectionModel {
    try {
      $collection = $this->repository->hydrate($data);

      $collection->set_collected_at(date('Y-m-d H:i:s'));
      $collection->set_active(true);
      $collection->set_paid_value(0.0);

      $errors = $this->validator->validate_fillables($collection);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $contract = $this->contract_repository->find_by_id($data['collected_by']);
      if ($contract === null) {
        return ["errors" => ["collected_by" => "Contract not found."]];
      }
      if ($contract->get_status() !== 'approved' || $contract->get_contract_end_at() !== null) {
        return ["errors" => ["collected_by" => "Contract is not active."]];
      }

      $material = $this->material_type_repository->find_by_id((int) $data['material_type_id']);
      if ($material === null) {
        return ["errors" => ["material_type_id" => "Material type not found."]];
      }

      $collect_type = (string) $collection->get_collect_type();
      $quantity = (float) $collection->get_quantity();

      try {
        if ($collect_type === 'weight') {
          $paid_value = $material->calculate_price_by_weight($quantity);
        } else {
          $paid_value = $material->calculate_price_by_units((int) $quantity);
        }
      } catch (Exception $e) {
        return ["errors" => ["collect_type" => $e->getMessage()]];
      }

      $collection->set_paid_value($paid_value);

      $result = $this->repository->create_in_person_collection($collection->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create in-person collection."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating in-person collection', ['error' => $e->getMessage()]);
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

  public function find_by_id(string $id): null|InPersonCollectionModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding in-person collection by ID', ['id' => $id, 'error' => $e->getMessage()]);
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

  public function find_by_contract(string $contract_id): array {
    try {
      return $this->repository->find_by_collected_by($contract_id);
    } catch (Exception $e) {
      Logger::error('Error finding in-person collections by contract', ['contract_id' => $contract_id, 'error' => $e->getMessage()]);
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

  public function find_by_material(int $material_type_id): array {
    try {
      return $this->repository->find_by_material_type_id($material_type_id);
    } catch (Exception $e) {
      Logger::error('Error finding in-person collections by material', ['material_type_id' => $material_type_id, 'error' => $e->getMessage()]);
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

  public function find_by_collect_type(string $collect_type): array {
    try {
      return $this->repository->find_by_collect_type($collect_type);
    } catch (Exception $e) {
      Logger::error('Error finding in-person collections by collect type', ['collect_type' => $collect_type, 'error' => $e->getMessage()]);
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

  public function find_active(): array {
    try {
      return $this->repository->find_all_active();
    } catch (Exception $e) {
      Logger::error('Error finding active in-person collections', ['error' => $e->getMessage()]);
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

  public function find_active_by_contract(string $contract_id): array {
    try {
      return $this->repository->find_active_by_collected_by($contract_id);
    } catch (Exception $e) {
      Logger::error('Error finding active in-person collections by contract', ['contract_id' => $contract_id, 'error' => $e->getMessage()]);
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
      Logger::error('Error finding all in-person collections', ['error' => $e->getMessage()]);
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