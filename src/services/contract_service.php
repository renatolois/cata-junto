<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\ContractModel;
use App\Validators\ContractValidator;
use App\Repositories\ContractRepository;
use App\Repositories\PersonRepository;
use App\Repositories\RoleRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use DateTime;
use Exception;

class ContractService extends BaseService {

  private PersonRepository $person_repository;
  private RoleRepository $role_repository;

  public function __construct(
    ContractRepository $repository,
    ContractValidator $validator,
    PersonRepository $person_repository,
    RoleRepository $role_repository
  ) {
    parent::__construct($repository, $validator);
    $this->person_repository = $person_repository;
    $this->role_repository = $role_repository;
  }

  public function create(array $data): array|ContractModel {
    try {
      [$contract, $errors] = $this->hydrate_and_validate_fillables($data);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $person_id = $data['person_id'];
      $role_id = $data['role_id'];

      $person = $this->person_repository->find_by_id($person_id);
      if ($person === null) {
        return ["errors" => ["person_id" => "Person not found."]];
      }

      $role = $this->role_repository->find_by_id($role_id);
      if ($role === null) {
        return ["errors" => ["role_id" => "Role not found."]];
      }

      $pending = $this->repository->find_pending_by_person_and_role($person_id, $role_id);
      if ($pending !== null) {
        return ["errors" => ["role_id" => "Already have a pending request for this role."]];
      }

      $active = $this->repository->find_active_by_person_and_role($person_id, $role_id);
      if ($active !== null) {
        return ["errors" => ["role_id" => "You already have an active contract for this role."]];
      }

      $last_rejection = $this->repository->find_last_rejection_for_person_and_role($person_id, $role_id);
      if ($last_rejection !== null) {
        $responded_at = new DateTime($last_rejection->get_responded_at());
        $now = new DateTime();
        $diff = $now->diff($responded_at);
        $months = ($diff->y * 12) + $diff->m;
        if ($months < 3) {
          return ["errors" => ["role_id" => "You must wait 3 months after a rejection before requesting again."]];
        }
      }

      $contract->set_status('pending');
      $contract->set_requested_at(date('Y-m-d H:i:s'));

      $result = $this->repository->create_contract($contract->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create contract."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating contract', ['error' => $e->getMessage()]);
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

  public function approve(string $pk, string $approved_by_contract_id, ?string $justification = null): array|ContractModel {
    try {
      $contract = $this->repository->find_by_id($pk);
      if ($contract === null) {
        return ["errors" => ["not_found_error" => "Contract not found."]];
      }

      if ($contract->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending contracts can be approved."]];
      }

      $admin_contract = $this->repository->find_by_id($approved_by_contract_id);
      if ($admin_contract === null) {
        return ["errors" => ["approved_by_id" => "Contract not found."]];
      }
      if ($admin_contract->get_status() !== 'approved' || $admin_contract->get_contract_end_at() !== null) {
        return ["errors" => ["approved_by_id" => "Contract is not active."]];
      }

      $role = $this->role_repository->find_by_id($admin_contract->get_role_id());
      if ($role === null) {
        return ["errors" => ["approved_by_id" => "Role not found."]];
      }
      if ($role->get_name() !== 'admin') {
        return ["errors" => ["approved_by_id" => "Has not permission."]];
      }
      if (!$role->is_active()) {
        return ["errors" => ["approved_by_id" => "Administrator role is inactivated."]];
      }

      $contract->approve($approved_by_contract_id);
      if ($justification !== null) {
        $contract->set_response_justification($justification);
      }

      $result = $this->repository->update_contract($pk, $contract->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to approve contract."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error approving contract', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function reject(string $pk, string $rejected_by_contract_id, string $justification): array|ContractModel {
    try {
      $contract = $this->repository->find_by_id($pk);
      if ($contract === null) {
        return ["errors" => ["not_found_error" => "Contract not found."]];
      }

      if ($contract->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending contracts can be rejected."]];
      }

      $admin_contract = $this->repository->find_by_id($rejected_by_contract_id);
      if ($admin_contract === null) {
        return ["errors" => ["rejected_by_id" => "Contract not found."]];
      }
      if ($admin_contract->get_status() !== 'approved' || $admin_contract->get_contract_end_at() !== null) {
        return ["errors" => ["rejected_by_id" => "Contract is not active."]];
      }

      $role = $this->role_repository->find_by_id($admin_contract->get_role_id());
      if ($role === null) {
        return ["errors" => ["rejected_by_id" => "Role not found."]];
      }
      if ($role->get_name() !== 'admin') {
        return ["errors" => ["rejected_by_id" => "Has not permission."]];
      }
      if (!$role->is_active()) {
        return ["errors" => ["rejected_by_id" => "Administrator role is inactivated."]];
      }

      $contract->reject($rejected_by_contract_id, $justification);

      $result = $this->repository->update_contract($pk, $contract->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to reject contract."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error rejecting contract', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function cancel(string $pk, string $cancelled_by_person_id): array|ContractModel {
    try {
      $contract = $this->repository->find_by_id($pk);
      if ($contract === null) {
        return ["errors" => ["not_found_error" => "Contract not found."]];
      }

      if ($cancelled_by_person_id !== $contract->get_person_id()) {
        return ["errors" => ["cancelled_by_id" => "Only the requester can cancel the contract."]];
      }

      if ($contract->get_status() !== 'pending') {
        return ["errors" => ["status" => "Only pending contracts can be canceled."]];
      }

      $contract->cancel();

      $result = $this->repository->update_contract($pk, $contract->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to cancel contract."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error canceling contract', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function terminate(string $pk, string $contract_end_by_contract_id, string $justification): array|ContractModel {
    try {
      $contract = $this->repository->find_by_id($pk);
      if ($contract === null) {
        return ["errors" => ["not_found_error" => "Contract not found."]];
      }

      if ($contract->get_status() !== 'approved' || $contract->get_contract_end_at() !== null) {
        return ["errors" => ["status" => "Only approved and not ended contracts can be terminated."]];
      }

      $admin_contract = $this->repository->find_by_id($contract_end_by_contract_id);
      if ($admin_contract === null) {
        return ["errors" => ["contract_end_by_id" => "Contract not found."]];
      }
      if ($admin_contract->get_status() !== 'approved' || $admin_contract->get_contract_end_at() !== null) {
        return ["errors" => ["contract_end_by_id" => "Contract is not active."]];
      }

      $role = $this->role_repository->find_by_id($admin_contract->get_role_id());
      if ($role === null) {
        return ["errors" => ["contract_end_by_id" => "Role not found."]];
      }
      if ($role->get_name() !== 'admin') {
        return ["errors" => ["contract_end_by_id" => "Has not permission."]];
      }
      if (!$role->is_active()) {
        return ["errors" => ["contract_end_by_id" => "Administrator role is inactivated."]];
      }

      $contract->dismiss($contract_end_by_contract_id, $justification);

      $result = $this->repository->update_contract($pk, $contract->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to terminate contract."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error terminating contract', ['pk' => $pk, 'error' => $e->getMessage()]);
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

  public function find_by_id(string $id): null|ContractModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding contract by ID', ['id' => $id, 'error' => $e->getMessage()]);
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
      return $this->repository->find_by_person_id($person_id);
    } catch (Exception $e) {
      Logger::error('Error finding contracts by person', ['person_id' => $person_id, 'error' => $e->getMessage()]);
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

  public function find_by_role(int $role_id): array {
    try {
      return $this->repository->find_by_role_id($role_id);
    } catch (Exception $e) {
      Logger::error('Error finding contracts by role', ['role_id' => $role_id, 'error' => $e->getMessage()]);
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
    try {
      return $this->repository->find_by_status('pending');
    } catch (Exception $e) {
      Logger::error('Error finding pending contracts', ['error' => $e->getMessage()]);
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
      return $this->repository->find_active();
    } catch (Exception $e) {
      Logger::error('Error finding active contracts', ['error' => $e->getMessage()]);
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
      Logger::error('Error finding all contracts', ['error' => $e->getMessage()]);
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