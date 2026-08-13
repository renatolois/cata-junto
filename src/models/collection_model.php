<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class CollectionModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'string'],
        ['collected_by', 'uuid'],
        ['material_type_id', 'int'],
        ['collected_at', 'datetime'],
        ['collect_type', 'string'], // weight ou unit
        ['quantity', 'float'],
        ['observation', 'string'],
        ['active', 'bool']
      ],
      fillables: [
        'collected_by', 'material_type_id', 'collected_at',
        'collect_type', 'quantity', 'observation', 'active'
      ],
      hiddens: []
    );
  }

  public function get_id(): string {
    return $this->id;
  }

  public function get_collected_by(): string {
    return $this->collected_by;
  }

  public function get_material_type_id(): int {
    return (int) $this->material_type_id;
  }

  public function get_collected_at(): string {
    return $this->collected_at;
  }

  public function get_collect_type(): string {
    return $this->collect_type;
  }

  public function get_quantity(): float {
    return (float) $this->quantity;
  }

  public function get_observation(): ?string {
    return $this->observation;
  }

  public function is_active(): bool {
    return (bool) $this->active;
  }

  public function set_collected_by(string $collected_by): void {
    $this->collected_by = $collected_by;
  }

  public function set_material_type_id(int $material_type_id): void {
    $this->material_type_id = $material_type_id;
  }

  public function set_collected_at(string $collected_at): void {
    $this->collected_at = $collected_at;
  }

  public function set_collect_type(string $collect_type): void {
    $this->collect_type = $collect_type;
  }

  public function set_quantity(float $quantity): void {
    $this->quantity = round($quantity, 2);
  }

  public function set_observation(?string $observation): void {
    $this->observation = $observation;
  }

  public function set_active(bool $active): void {
    $this->active = $active;
  }

  public function activate(): void {
    $this->active = true;
  }

  public function deactivate(): void {
    $this->active = false;
  }
}
