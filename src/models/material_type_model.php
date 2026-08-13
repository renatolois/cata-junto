<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class MaterialTypeModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'int'],
        ['name', 'string'],
        
        ['price_per_weight', 'float'],
        ['points_per_weight', 'int'],
        
        ['price_per_unit', 'float'],
        ['points_per_unit', 'int'],
        
        ['weight_active', 'bool'],
        ['unit_active', 'bool'],
      ],
      fillables: [
        'name', 
        'price_per_weight', 'points_per_weight', 
        'price_per_unit', 'points_per_unit', 
        'weight_active', 'unit_active'
      ],
      hiddens: []
    );
  }

  public function get_id(): int {
    return (int) $this->id;
  }

  public function get_name(): string {
    return $this->name;
  }

  public function get_price_per_weight(): float {
    return (float) ($this->price_per_weight ?? 0);
  }

  public function get_points_per_weight(): int {
    return (int) ($this->points_per_weight ?? 0);
  }

  public function get_price_per_unit(): float {
    return (float) ($this->price_per_unit ?? 0);
  }

  public function get_points_per_unit(): int {
    return (int) ($this->points_per_unit ?? 0);
  }

  public function is_weight_active(): bool {
    return (bool) ($this->weight_active ?? false);
  }

  public function is_unit_active(): bool {
    return (bool) ($this->unit_active ?? false);
  }

  public function set_name(string $name): void {
    $this->name = $name;
  }

  public function set_price_per_weight(float $price): void {
    $this->price_per_weight = $price;
  }

  public function set_points_per_weight(int $points): void {
    $this->points_per_weight = $points;
  }

  public function set_price_per_unit(float $price): void {
    $this->price_per_unit = $price;
  }

  public function set_points_per_unit(int $points): void {
    $this->points_per_unit = $points;
  }

  public function set_weight_values(float $price, int $points): void {
    $this->price_per_weight = $price;
    $this->points_per_weight = $points;
  }

  public function set_unit_values(float $price, int $points): void {
    $this->price_per_unit = $price;
    $this->points_per_unit = $points;
  }

  public function set_weight_active(bool $active): void {
    $this->weight_active = $active;
  }

  public function set_unit_active(bool $active): void {
    $this->unit_active = $active;
  }

  public function activate_weight(): void {
    $this->weight_active = true;
  }

  public function deactivate_weight(): void {
    $this->weight_active = false;
  }

  public function activate_unit(): void {
    $this->unit_active = true;
  }

  public function deactivate_unit(): void {
    $this->unit_active = false;
  }

  public function calculate_price_by_weight(float $weight): float {
    if (!$this->is_weight_active()) {
      throw new \Exception("Weight calculation is not active for this material type.");
    }
    return $weight * ($this->price_per_weight ?? 0);
  }

  public function calculate_points_by_weight(float $weight): int {
    if (!$this->is_weight_active()) {
      throw new \Exception("Weight calculation is not active for this material type.");
    }
    return (int) ceil($weight * ($this->points_per_weight ?? 0));
  }

  public function calculate_price_by_units(int $units): float {
    if (!$this->is_unit_active()) {
      throw new \Exception("Unit calculation is not active for this material type.");
    }
    return $units * ($this->price_per_unit ?? 0);
  }

  public function calculate_points_by_units(int $units): int {
    if (!$this->is_unit_active()) {
      throw new \Exception("Unit calculation is not active for this material type.");
    }
    return $units * ($this->points_per_unit ?? 0);
  }

  public function calculate_by_weight(float $weight): array {
    if (!$this->is_weight_active()) {
      throw new \Exception("Weight calculation is not active for this material type.");
    }

    return [
      'price'  => $weight * ($this->price_per_weight ?? 0),
      'points' => (int) ceil($weight * ($this->points_per_weight ?? 0))
    ];
  }

  public function calculate_by_units(int $units): array {
    if (!$this->is_unit_active()) {
      throw new \Exception("Unit calculation is not active for this material type.");
    }

    return [
      'price'  => $units * ($this->price_per_unit ?? 0),
      'points' => $units * ($this->points_per_unit ?? 0)
    ];
  }
}
