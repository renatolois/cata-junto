<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;
use App\Core\Utils\NeutralValue;

class ContractModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'uuid'],
        ['person_id', 'uuid'],
        ['role_id', 'int'],
        ['responded_by_id', 'uuid'],
        ['contract_end_by', 'uuid'],

        ['requested_at', 'datetime'],
        ['responded_at', 'datetime'],

        ['status', 'string'],

        ['response_justification', 'string'],
        ['dismissal_justification', 'string'],
        ['contract_end_at', 'datetime']
      ],
      fillables: [
        'person_id', 'role_id', 'responded_by_id', 'contract_end_by',
        'requested_at', 'responded_at', 'status',
        'response_justification', 'dismissal_justification',
        'contract_end_at'
      ],
      hiddens: []
    );

    if (!isset($this->status)) {
      $this->status = 'pending';
    }
  }

  public function get_id(): string {
    return (string) ($this->id ?? '');
  }

  public function get_person_id(): string {
    return (string) ($this->person_id ?? '');
  }

  public function get_role_id(): int {
    return (int) ($this->role_id ?? 0);
  }

  public function get_responded_by_id(): string|NeutralValue {
    return $this->responded_by_id;
  }

  public function get_contract_end_by(): string|NeutralValue {
    return $this->contract_end_by;
  }

  public function get_requested_at(): string {
    return (string) ($this->requested_at ?? '');
  }

  public function get_responded_at(): string|NeutralValue {
    return $this->responded_at;
  }

  public function get_status(): string {
    return (string) ($this->status ?? 'pending');
  }

  public function get_response_justification(): string|NeutralValue {
    return $this->response_justification;
  }

  public function get_dismissal_justification(): string|NeutralValue {
    return $this->dismissal_justification;
  }

  public function get_contract_end_at(): string|NeutralValue {
    return $this->contract_end_at;
  }

  public function set_person_id(string $person_id): void {
    $this->person_id = $person_id;
  }

  public function set_role_id(int $role_id): void {
    $this->role_id = $role_id;
  }

  public function set_responded_by_id(string|NeutralValue $responded_by_id): void {
    $this->responded_by_id = $responded_by_id;
  }

  public function set_contract_end_by(string|NeutralValue $contract_end_by): void {
    $this->contract_end_by = $contract_end_by;
  }

  public function set_requested_at(string $requested_at): void {
    $this->requested_at = $requested_at;
  }

  public function set_responded_at(string|NeutralValue $responded_at): void {
    $this->responded_at = $responded_at;
  }

  public function set_status(string $status): void {
    $this->status = $status;
  }

  public function set_response_justification(string|NeutralValue $justification): void {
    $this->response_justification = $justification;
  }

  public function set_dismissal_justification(string|NeutralValue $justification): void {
    $this->dismissal_justification = $justification;
  }

  public function set_contract_end_at(string|NeutralValue $contract_end_at): void {
    $this->contract_end_at = $contract_end_at;
  }

  public function approve(string $manager_contract_id): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending contracts can be approved.");
    }

    $this->responded_by_id = $manager_contract_id;
    $this->responded_at = date('Y-m-d H:i:s');
    $this->status = 'approved';
  }

  public function reject(string $manager_contract_id, string $justification): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending contracts can be rejected.");
    }

    $this->responded_by_id = $manager_contract_id;
    $this->responded_at = date('Y-m-d H:i:s');
    $this->response_justification = $justification;
    $this->status = 'rejected';
  }

  public function dismiss(string $manager_contract_id, string $justification): void {
    if ($this->status !== 'approved') {
      throw new \Exception("Cannot dismiss a contract that is not active.");
    }

    $this->contract_end_by = $manager_contract_id;
    $this->contract_end_at = date('Y-m-d H:i:s');
    $this->dismissal_justification = $justification;
    $this->status = 'dismissed';
  }

  public function cancel(): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Cannot cancel a contract that is not pending.");
    }

    $this->responded_at = date('Y-m-d H:i:s');
    $this->status = 'cancelled';
  }
}