<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PrizeClaimModel;
use App\Validators\PrizeClaimValidator;
use App\Repositories\PrizeClaimRepository;
use App\Repositories\CollectionLocationRepository;
use App\Repositories\PrizeTypeRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class PrizeClaimService extends BaseService {

  private CollectionLocationRepository $location_repository;
  private PrizeTypeRepository $prize_type_repository;

  public function __construct(
    PrizeClaimRepository $repository,
    PrizeClaimValidator $validator,
    CollectionLocationRepository $location_repository,
    PrizeTypeRepository $prize_type_repository
  ) {
    parent::__construct($repository, $validator);
    $this->location_repository = $location_repository;
    $this->prize_type_repository = $prize_type_repository;
  }

  public function create(array $data): array|PrizeClaimModel {    
    try {
      $data['claimed_at'] = date('Y-m-d H:i:s');
      $data['status'] = 'pending';
      
      [$claim, $errors] = $this->hydrate_and_validate_fillables($data);

      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $location = $this->location_repository->find_by_id($data['claimed_by']);
      if ($location === null) {
        return ["errors" => ["claimed_by" => "Collection location not found."]];
      }

      $prize_type = $this->prize_type_repository->find_by_id($data['prize_type_id']);
      if ($prize_type === null) {
        return ["errors" => ["prize_type_id" => "Prize type not found."]];
      }

      if (!$prize_type->is_active()) {
        return ["errors" => ["prize_type_id" => "Prize type is not active."]];
      }

      $cost = $prize_type->get_cost_points();
      $current_points = $location->get_current_points();

      if ($current_points < $cost) {
        return ["errors" => ["current_points" => "Insufficient points."]];
      }

      $this->repository->begin_transaction();

      try {
        $new_points = $current_points - $cost;
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );

        $result = $this->repository->create_prize_claim($claim->get_attributes());

        if ($result === null) {
          throw new Exception("Failed to create prize claim.");
        }

        $this->repository->commit();
        return $result;
      } catch (Exception $e) {
        $this->repository->rollback();
        throw $e;
      }
    } catch (Exception $e) {
      Logger::error('Error creating prize claim', ['error' => $e->getMessage()]);
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

  public function complete(string|int $pk): array|PrizeClaimModel {
    try {
      $claim = $this->repository->find_by_id($pk);
      if ($claim === null) {
        return ["errors" => ["not_found_error" => "Prize claim not found."]];
      }

      if ($claim->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending claims can be completed."]];
      }

      $claim->complete();

      $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to complete prize claim."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error completing prize claim', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function cancel(string|int $pk): array|PrizeClaimModel {
    try {
      $claim = $this->repository->find_by_id($pk);
      if ($claim === null) {
        return ["errors" => ["not_found_error" => "Prize claim not found."]];
      }

      if ($claim->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending claims can be canceled."]];
      }

      $prize_type = $this->prize_type_repository->find_by_id($claim->get_prize_type_id());
      if ($prize_type === null) {
        return ["errors" => ["prize_type_id" => "Prize type not found."]];
      }

      $location = $this->location_repository->find_by_id($claim->get_claimed_by());
      if ($location === null) {
        return ["errors" => ["claimed_by" => "Collection location not found."]];
      }

      $this->repository->begin_transaction();

      try {
        $claim->cancel();

        $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
        if ($result === null) {
          throw new Exception("Failed to cancel prize claim.");
        }

        $new_points = $location->get_current_points() + $prize_type->get_cost_points();
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );

        $this->repository->commit();
        return $result;
      } catch (Exception $e) {
        $this->repository->rollback();
        throw $e;
      }
    } catch (Exception $e) {
      Logger::error('Error canceling prize claim', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function reject(string|int $pk): array|PrizeClaimModel {
    try {
      $claim = $this->repository->find_by_id($pk);
      if ($claim === null) {
        return ["errors" => ["not_found_error" => "Prize claim not found."]];
      }

      if ($claim->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending claims can be rejected."]];
      }

      $prize_type = $this->prize_type_repository->find_by_id($claim->get_prize_type_id());
      if ($prize_type === null) {
        return ["errors" => ["prize_type_id" => "Prize type not found."]];
      }

      $location = $this->location_repository->find_by_id($claim->get_claimed_by());
      if ($location === null) {
        return ["errors" => ["claimed_by" => "Collection location not found."]];
      }

      $this->repository->begin_transaction();

      try {
        $claim->reject();

        $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
        if ($result === null) {
          throw new Exception("Failed to reject prize claim.");
        }

        $new_points = $location->get_current_points() + $prize_type->get_cost_points();
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );

        $this->repository->commit();
        return $result;
      } catch (Exception $e) {
        $this->repository->rollback();
        throw $e;
      }
    } catch (Exception $e) {
      Logger::error('Error rejecting prize claim', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function find_by_id(int $id): null|PrizeClaimModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding prize claim by ID', ['id' => $id, 'error' => $e->getMessage()]);
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
      return $this->repository->find_by_claimed_by($location_id);
    } catch (Exception $e) {
      Logger::error('Error finding prize claims by location', ['location_id' => $location_id, 'error' => $e->getMessage()]);
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

  public function find_by_prize_type(int $prize_type_id): array {
    try {
      return $this->repository->find_by_prize_type_id($prize_type_id);
    } catch (Exception $e) {
      Logger::error('Error finding prize claims by prize type', ['prize_type_id' => $prize_type_id, 'error' => $e->getMessage()]);
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
      Logger::error('Error finding prize claims by status', ['status' => $status, 'error' => $e->getMessage()]);
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

  public function find_all(): array {
    try {
      return $this->repository->find_all();
    } catch (Exception $e) {
      Logger::error('Error finding all prize claims', ['error' => $e->getMessage()]);
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