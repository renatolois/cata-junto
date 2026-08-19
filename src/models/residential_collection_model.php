<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\CollectionModel;

class ResidentialCollectionModel extends CollectionModel {
  public function __construct() {
    parent::__construct();

    $this->merge_attributes_and_types(
      attributes_and_types: [
        ['collection_location_id', 'uuid'],
        ['requested_at', 'datetime'],
        ['description', 'string'],
        ['status', 'string'],
        ['deactivation_at', 'datetime'],
        ['deactivation_justification', 'string']
      ]
    );

    $this->merge_fillables(
      fillables: [
        'collection_location_id', 'requested_at', 'description',
        'status', 'deactivation_at', 'deactivation_justification'
      ]
    );
  }

  public function get_collection_location_id(): string {
    return $this->collection_location_id;
  }

  public function get_requested_at(): string {
    return $this->requested_at;
  }

  public function get_description(): ?string {
    return $this->description ?? null;
  }

  public function get_status(): string {
    return $this->status;
  }

  public function get_deactivation_at(): ?string {
    return $this->deactivation_at ?? null;
  }

  public function get_deactivation_justification(): ?string {
    return $this->deactivation_justification ?? null;
  }

  public function set_collection_location_id(string $collection_location_id): void {
    $this->collection_location_id = $collection_location_id;
  }

  public function set_requested_at(string $requested_at): void {
    $this->requested_at = $requested_at;
  }

  public function set_description(?string $description): void {
    $this->description = $description;
  }

  public function set_status(string $status): void {
    $this->status = $status;
  }

  public function set_deactivation_at(?string $deactivation_at): void {
    $this->deactivation_at = $deactivation_at;
  }

  public function set_deactivation_justification(?string $justification): void {
    $this->deactivation_justification = $justification;
  }

  // Métodos de ação
  public function complete(string $contract_id, float $quantity): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending collections can be completed.");
    }

    $this->set_collected_by($contract_id);
    $this->set_quantity($quantity);
    $this->set_collected_at(date('Y-m-d H:i:s'));
    $this->status = 'completed';
  }

  public function cancel(): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Cannot cancel a collection that is not pending.");
    }

    $this->status = 'cancelled';
  }

  public function deactivate(string $justification): void {
    if (!$this->active) {
      throw new \Exception("This collection is already deactivated.");
    }

    $this->deactivation_at = date('Y-m-d');
    $this->deactivation_justification = $justification;
    $this->active = false;
  }
}
