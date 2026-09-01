<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\ContractModel;
use App\Validators\ContractValidator;
use App\Repositories\ContractRepository;
use App\Repositories\PersonRepository;
use App\Repositories\RoleRepository;
use DateTime;

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

  private function hydrate_and_validate(array $data): array {
    $contract = $this->repository->hydrate($data);
    $errors = $this->validator->validate($contract);
    return [$contract, $errors];
  }

  public function create(array $data): array|ContractModel {
    [$contract, $errors] = $this->hydrate_and_validate($data);

    if (!empty($errors)) {
      return ["errors" => $errors];
    }

    $person_id = $data['person_id'];
    $role_id = $data['role_id'];

    $person = $this->person_repository->find_by_id($person_id);
    if ($person === null) {
      return ["errors" => ["service_error" => "person_id -> Person not found."]];
    }

    $role = $this->role_repository->find_by_id($role_id);
    if ($role === null) {
      return ["errors" => ["service_error" => "role_id -> Role not found."]];
    }

    $pending = $this->repository->find_pending_by_person_and_role($person_id, $role_id);
    if ($pending !== null) {
      return ["errors" => ["service_error" => "role_id -> Already have a pending request for this role."]];
    }

    $active = $this->repository->find_active_by_person_and_role($person_id, $role_id);
    if ($active !== null) {
      return ["errors" => ["service_error" => "role_id -> You already have an active contract for this role."]];
    }

    $last_rejection = $this->repository->find_last_rejection_for_person_and_role($person_id, $role_id);
    if ($last_rejection !== null) {
      $responded_at = new DateTime($last_rejection->get_responded_at());
      $now = new DateTime();
      $diff = $now->diff($responded_at);
      $months = ($diff->y * 12) + $diff->m;
      if ($months < 3) {
        return ["errors" => ["service_error" => "role_id -> You must wait 3 months after a rejection before requesting again."]];
      }
    }

    $contract->set_status('pending');
    $contract->set_requested_at(date('Y-m-d H:i:s'));

    $result = $this->repository->create_contract($contract->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to create contract."]];
    }

    return $result;
  }

  /**
   * Don't implement this
   * public function update(string $pk, array $data): array|ContractModel { }
   */

  public function hard_delete(string $pk): bool|array {
    $contract = $this->repository->find_by_id($pk);
    if ($contract === null) {
      return ["errors" => ["service_error" => "Contract not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function approve(string $pk, string $approved_by_id, ?string $justificative = null): array|ContractModel {
    $contract = $this->repository->find_by_id($pk);
    if ($contract === null) {
      return ["errors" => ["service_error" => "Contract not found."]];
    }

    if ($contract->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending contracts can be approved."]];
    }

    $approved_by = $this->person_repository->find_by_id($approved_by_id);
    if ($approved_by === null) {
      return ["errors" => ["approved_by_id" => "Administrator not found."]];
    }

    $is_admin = false;
    $active_contracts = $this->repository->find_active_by_person($approved_by_id);
    foreach ($active_contracts as $active_contract) {
      $role_id = $active_contract->get_role_id();
      $role = $this->role_repository->find_by_id($role_id);
      if ($role && $role->get_name() === 'admin') {
        if (!$role->is_active()) {
          return ["errors" => ["approved_by_id" => "Administrator role is inactivated."]];
        }
        $is_admin = true;
        break;
      }
    }

    if ($is_admin === false) {
      return ["errors" => ["approved_by_id" => "Has not permission."]];
    }

    $contract->set_status('approved');
    $contract->set_responded_by_id($approved_by_id);
    $contract->set_responded_at(date('Y-m-d H:i:s'));
    if ($justificative !== null) {
      $contract->set_response_justification($justificative);
    }

    $result = $this->repository->update_contract($pk, 
      [
        'status' => $contract->get_status(),
        'responded_by_id' => $contract->get_responded_by_id(),
        'responded_at' => $contract->get_responded_at(),
        'response_justification' => $contract->get_response_justification()
      ]
    );

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to approve contract."]];
    }

    return $result;
  }

  public function reject(string $pk, string $refused_by_id, string $justificative): array|ContractModel {
    $contract = $this->repository->find_by_id($pk);
    if ($contract === null) {
      return ["errors" => ["service_error" => "Contract not found."]];
    }

    if ($contract->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending contracts can be rejected."]];
    }

    $refused_by = $this->person_repository->find_by_id($refused_by_id);
    if ($refused_by === null) {
      return ["errors" => ["refused_by_id" => "Administrator not found."]];
    }

    $is_admin = false;
    $active_contracts = $this->repository->find_active_by_person($refused_by_id);
    foreach ($active_contracts as $active_contract) {
      $role_id = $active_contract->get_role_id();
      $role = $this->role_repository->find_by_id($role_id);
      if ($role && $role->get_name() === 'admin') {
        if (!$role->is_active()) {
          return ["errors" => ["refused_by_id" => "Administrator role is inactivated."]];
        }
        $is_admin = true;
        break;
      }
    }

    if ($is_admin === false) {
      return ["errors" => ["refused_by_id" => "Has not permission."]];
    }

    $contract->set_status('rejected');
    $contract->set_responded_by_id($refused_by_id);
    $contract->set_responded_at(date('Y-m-d H:i:s'));
    $contract->set_response_justification($justificative);

    $result = $this->repository->update_contract(
      $pk,
      [
        'status' => $contract->get_status(),
        'responded_by_id' => $contract->get_responded_by_id(),
        'responded_at' => $contract->get_responded_at(),
        'response_justification' =>     $contract->get_response_justification()
      ]
    );

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to reject contract."]];
    }

    return $result;
  }

  public function cancel(string $pk, string $cancelled_by): array|ContractModel {
    $contract = $this->repository->find_by_id($pk);
    if ($contract === null) {
      return ["errors" => ["service_error" => "Contract not found."]];
    }

    if ($cancelled_by !== $contract->get_person_id()) {
      return ["errors" => ["canceled_by_id" => "Only the requester can cancel the contract."]];
    }

    if ($contract->get_status() !== 'pending') {
      return ["errors" => ["status" => "Only pending contracts can be canceled."]];
    }

    $contract->set_status('canceled');
    $contract->set_responded_at(date('Y-m-d H:i:s'));

    $result = $this->repository->update_contract(
      $pk,
      [
        'status' => $contract->get_status(),
        'responded_at' => $contract->get_responded_at(),
        'responded_by_id' => $contract->get_responded_by_id()
      ]
    );

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to cancel contract."]];
    }

    return $result;
  }

  public function terminate(string $pk, string $contract_end_by_id, string $justificative): array|ContractModel {
    $contract = $this->repository->find_by_id($pk);
    if ($contract === null) {
      return ["errors" => ["service_error" => "Contract not found."]];
    }

    if ($contract->get_status() !== 'approved' || $contract->get_contract_end_at() !== null) {
      return ["errors" => ["status" => "Only approved and not ended contracts can be terminated."]];
    }

    $contract_end_by = $this->person_repository->find_by_id($contract_end_by_id);
    if ($contract_end_by === null) {
      return ["errors" => ["contract_end_by_id" => "Administrator not found."]];
    }

    $is_admin = false;
    $active_contracts = $this->repository->find_active_by_person($contract_end_by_id);
    foreach ($active_contracts as $active_contract) {
      $role_id = $active_contract->get_role_id();
      $role = $this->role_repository->find_by_id($role_id);
      if ($role && $role->get_name() === 'admin') {
        if (!$role->is_active()) {
          return ["errors" => ["contract_end_by_id" => "Administrator role is inactivated."]];
        }
        $is_admin = true;
        break;
      }
    }

    if ($is_admin === false) {
      return ["errors" => ["contract_end_by_id" => "Has not permission."]];
    }

    $contract->set_status("dismissed");
    $contract->set_contract_end_by_id($contract_end_by_id);
    $contract->set_dismissal_justification($justificative);
    $contract->set_contract_end_at(date('Y-m-d H:i:s'));

    $result = $this->repository->update_contract(
      $pk,
      [
        'status' => $contract->get_status(),
        'contract_end_by_id' => $contract->get_contract_end_by_id (),
        'dismissal_justification' => $contract->get_dismissal_justification(),
        'contract_end_at' => $contract->get_contract_end_at(),
      ]
    );

    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to terminate contract."]];
    }

    return $result;
  }

  public function find_by_id(string $id): ?ContractModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_person(string $person_id): array {
    return $this->repository->find_by_person($person_id);
  }

  public function find_by_role(int $role_id): array {
    return $this->repository->find_by_role($role_id);
  }

  public function find_pending(): array {
    return $this->repository->find_pending();
  }

  public function find_active(): array {
    return $this->repository->find_active();
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }
}