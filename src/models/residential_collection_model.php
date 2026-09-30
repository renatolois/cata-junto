<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\CollectionModel;
use App\Core\Utils\NeutralValue;

class ResidentialCollectionModel extends CollectionModel {
  public function __construct() {
    parent::__construct();

    $this->merge_attributes_and_types(
      attributes_and_types: [
        ['collection_location_id', 'uuid'],
        ['requested_at', 'datetime'],
        ['rejected_by', 'uuid'],
        ['deactivation_at', 'datetime'],
        ['deactivation_justification', 'string'],
        ['description', 'string'],
        ['status', 'string'],
        ['conceded_points', 'int']
      ]
    );

    $this->merge_fillables(
      fillables: [
        'collection_location_id', 'description'
      ]
    );

    if (!isset($this->status)) {
      $this->status = 'pending';
    }
  }

  public function get_collection_location_id(): string {
    return (string) ($this->collection_location_id ?? '');
  }

  public function get_requested_at(): string|NeutralValue {
    return $this->requested_at;
  }

  public function get_rejected_by(): string|NeutralValue {
    return $this->rejected_by;
  }

  public function get_deactivation_at(): string|NeutralValue {
    return $this->deactivation_at;
  }

  public function get_deactivation_justification(): string|NeutralValue {
    return $this->deactivation_justification;
  }

  public function get_description(): string|NeutralValue {
    return $this->description;
  }

  public function get_status(): string {
    return (string) ($this->status ?? 'pending');
  }

  public function get_conceded_points(): int {
    return (int) ($this->conceded_points ?? 0);
  }

  public function set_collection_location_id(string $collection_location_id): void {
    $this->collection_location_id = $collection_location_id;
  }

  public function set_requested_at(string|NeutralValue $requested_at): void {
    $this->requested_at = $requested_at;
  }

  public function set_rejected_by(string|NeutralValue $rejected_by): void {
    $this->rejected_by = $rejected_by;
  }

  public function set_deactivation_at(string|NeutralValue $deactivation_at): void {
    $this->deactivation_at = $deactivation_at;
  }

  public function set_deactivation_justification(string|NeutralValue $justification): void {
    $this->deactivation_justification = $justification;
  }

  public function set_description(string|NeutralValue $description): void {
    $this->description = $description;
  }

  public function set_status(string $status): void {
    $this->status = $status;
  }

  public function set_conceded_points(int $conceded_points): void {
    $this->conceded_points = $conceded_points;
  }

  public function complete(string $contract_id, float $quantity, int $conceded_points): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending collections can be completed.");
    }

    $this->set_collected_by($contract_id);
    $this->set_quantity($quantity);
    $this->set_collected_at(date('Y-m-d H:i:s'));
    $this->set_conceded_points($conceded_points);
    $this->status = 'completed';
  }

  public function cancel(string|NeutralValue $justification): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Cannot cancel a collection that is not pending.");
    }

    $this->status = 'cancelled';
    $this->deactivation_at = date('Y-m-d H:i:s');
    $this->deactivation_justification = $justification;
  }

  public function reject(string $moderator_contract_id, string $justification): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending collections can be rejected.");
    }

    $this->status = 'rejected';
    $this->rejected_by = $moderator_contract_id;
    $this->deactivation_at = date('Y-m-d H:i:s');
    $this->deactivation_justification = $justification;
  }

  public function deactivate (string $justification = ''): void {
    if (!$this->is_active()) {
      throw new \Exception("This collection is already deactivated.");
    }

    $this->deactivation_at = date('Y-m-d H:i:s');
    $this->deactivation_justification = $justification;
    $this->active = false;
  }
}