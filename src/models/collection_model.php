<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;
use App\Core\Utils\NeutralValue;

class CollectionModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'uuid'],
        ['collected_by', 'uuid'],
        ['material_type_id', 'int'],
        ['collected_at', 'datetime'],
        ['collect_type', 'string'],
        ['quantity', 'float'],
        ['observation', 'string'],
        ['active', 'bool'],
      ],
      fillables: [
        'material_type_id', 'collect_type', 'observation'
      ],
      hiddens: []
    );
  }

  public function get_id(): string {
    return (string) ($this->id ?? '');
  }

  public function get_collected_by(): string|NeutralValue {
    return $this->collected_by;
  }

  public function get_material_type_id(): int {
    return (int) ($this->material_type_id ?? 0);
  }

  public function get_collected_at(): string|NeutralValue {
    return $this->collected_at;
  }

  public function get_collect_type(): string|NeutralValue {
    return $this->collect_type;
  }

  public function get_quantity(): float|NeutralValue {
    return $this->quantity;
  }

  public function get_observation(): string|NeutralValue {
    return $this->observation;
  }

  public function is_active(): bool {
    return (bool) ($this->active ?? false);
  }

  public function set_id(string $id): void {
    $this->id = $id;
  }

  public function set_collected_by(string|NeutralValue $collected_by): void {
    $this->collected_by = $collected_by;
  }

  public function set_material_type_id(int $material_type_id): void {
    $this->material_type_id = $material_type_id;
  }

  public function set_collected_at(string|NeutralValue $collected_at): void {
    $this->collected_at = $collected_at;
  }

  public function set_collect_type(string|NeutralValue $collect_type): void {
    $this->collect_type = $collect_type;
  }

  public function set_quantity(float|NeutralValue $quantity): void {
    if ($quantity instanceof NeutralValue) {
      $this->quantity = $quantity;
      return;
    }
    $this->quantity = round($quantity, 2);
  }

  public function set_observation(string|NeutralValue $observation): void {
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