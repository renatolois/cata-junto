<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class PrizeTypeModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'int'],
        ['name', 'string'],
        ['description', 'string'],
        ['cost_points', 'int'],
        ['active', 'bool']
      ],
      fillables: [
        'name', 'description', 'cost_points', 'active'
      ],
      hiddens: []
    );

    if (empty($this->active)) {
        $this->active = true;
    }
  }

  public function get_id(): int {
    return (int) $this->id;
  }

  public function get_name(): string {
    return $this->name;
  }

  public function get_description(): string {
    return $this->description;
  }

  public function get_cost_points(): int {
    return (int) $this->cost_points;
  }

  public function is_active(): bool {
    return (bool) $this->active;
  }

  public function set_name(string $name): void {
    $this->name = $name;
  }

  public function set_description(string $description): void {
    $this->description = $description;
  }

  public function set_cost_points(int $cost_points): void {
    $this->cost_points = $cost_points;
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
