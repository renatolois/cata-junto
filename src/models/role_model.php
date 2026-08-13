<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class RoleModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'int'],
        ['name', 'string'],
        ['active', 'bool']
      ],
      fillables: [
        'name', 'active'
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

  public function is_active(): bool {
    return (bool) $this->active;
  }

  public function set_name(string $name): void {
    $this->name = $name;
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
