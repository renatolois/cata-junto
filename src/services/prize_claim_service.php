<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PrizeClaimModel;
use App\Validators\PrizeClaimValidator;
use App\Repositories\PrizeClaimRepository;
use App\Repositories\CollectionLocationRepository;
use App\Repositories\PrizeTypeRepository;

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
    [$claim, $errors] = $this->hydrate_and_validate($data);

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

    $new_points = $current_points - $cost;
    $location->set_current_points($new_points);
    $update_result = $this->location_repository->update_collection_location(
      $location->get_id(),
      ['current_points' => $new_points]
    );

    if ($update_result === null) {
      return ["errors" => ["service_error" => "Failed to deduct points from location."]];
    }

    $claim->set_claimed_at(date('Y-m-d H:i:s'));
    $claim->set_status('pending');

    $result = $this->repository->create_prize_claim($claim->get_attributes());
    if ($result === null) {
      $this->location_repository->update_collection_location(
        $location->get_id(),
        ['current_points' => $current_points]
      );
      return ["errors" => ["service_error" => "Failed to create prize claim."]];
    }

    return $result;
  }

  public function complete(string|int $pk): array|PrizeClaimModel {
    $claim = $this->repository->find_by_id($pk);
    if ($claim === null) {
      return ["errors" => ["service_error" => "Prize claim not found."]];
    }

    if ($claim->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending claims can be completed."]];
    }

    try {
      $claim->complete();
    } catch (\Exception $e) {
      return ["errors" => ["status" => $e->getMessage()]];
    }

    $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to complete prize claim."]];
    }

    return $result;
  }

  public function cancel(string|int $pk): array|PrizeClaimModel {
    $claim = $this->repository->find_by_id($pk);
    if ($claim === null) {
      return ["errors" => ["service_error" => "Prize claim not found."]];
    }

    if ($claim->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending claims can be canceled."]];
    }

    try {
      $claim->cancel();
    } catch (\Exception $e) {
      return ["errors" => ["status" => $e->getMessage()]];
    }

    $prize_type = $this->prize_type_repository->find_by_id($claim->get_prize_type_id());
    if ($prize_type !== null) {
      $location = $this->location_repository->find_by_id($claim->get_claimed_by());
      if ($location !== null) {
        $new_points = $location->get_current_points() + $prize_type->get_cost_points();
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );
      }
    }

    $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to cancel prize claim."]];
    }

    return $result;
  }

  public function reject(string|int $pk): array|PrizeClaimModel {
    $claim = $this->repository->find_by_id($pk);
    if ($claim === null) {
      return ["errors" => ["service_error" => "Prize claim not found."]];
    }

    if ($claim->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending claims can be rejected."]];
    }

    try {
      $claim->reject();
    } catch (\Exception $e) {
      return ["errors" => ["status" => $e->getMessage()]];
    }

    $prize_type = $this->prize_type_repository->find_by_id($claim->get_prize_type_id());
    if ($prize_type !== null) {
      $location = $this->location_repository->find_by_id($claim->get_claimed_by());
      if ($location !== null) {
        $new_points = $location->get_current_points() + $prize_type->get_cost_points();
        $this->location_repository->update_collection_location(
          $location->get_id(),
          ['current_points' => $new_points]
        );
      }
    }

    $result = $this->repository->update_prize_claim($pk, $claim->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to reject prize claim."]];
    }

    return $result;
  }

  public function find_by_id(int $id): ?PrizeClaimModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_location(string $location_id): array {
    return $this->repository->find_by_claimed_by($location_id);
  }

  public function find_by_prize_type(int $prize_type_id): array {
    return $this->repository->find_by_prize_type($prize_type_id);
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

  public function find_cancelled(): array {
    return $this->repository->find_by_status('cancelled');
  }

  public function find_rejected(): array {
    return $this->repository->find_by_status('rejected');
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }

  public function hard_delete(int $pk): bool|array {
    $claim = $this->repository->find_by_id($pk);
    if ($claim === null) {
      return ["errors" => ["service_error" => "Prize claim not found."]];
    }

    if (in_array($claim->get_status(), ['pending', 'cancelled', 'rejected'])) {
      $prize_type = $this->prize_type_repository->find_by_id($claim->get_prize_type_id());
      if ($prize_type !== null) {
        $location = $this->location_repository->find_by_id($claim->get_claimed_by());
        if ($location !== null) {
          $new_points = $location->get_current_points() + $prize_type->get_cost_points();
          $this->location_repository->update_collection_location(
            $location->get_id(),
            ['current_points' => $new_points]
          );
        }
      }
    }

    return $this->repository->delete($pk);
  }
}