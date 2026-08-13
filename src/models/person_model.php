<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class PersonModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'uuid'],
        ['email', 'email'],
        ['verified_email', 'bool'],
        ['password_hash', 'string'],
        ['phone_number', 'phone'],
        ['cpf', 'cpf'],
        ['name', 'string'],
        ['birth_date', 'date'],
        ['current_points', 'int'],
        ['active', 'bool']
      ],
  
      fillables: [
        'email', 'phone_number', 'cpf', 'name', 'birth_date', 'current_points', 'active'
      ],
      hiddens: [
        'password_hash'
      ]
    );
  }
  
  public function verify_password(string $password): bool {
    return password_verify($password, $this->attributes['password_hash'] ?? '');
  }
  
  public function set_password(string $password): void {
    $this->password_hash = password_hash($password, PASSWORD_BCRYPT);
  }

  public function get_id(): string {
    return $this->id;
  }

  public function get_email(): string {
    return $this->email;
  }

  public function get_verified_email(): bool {
    return (bool) ($this->verified_email ?? false);
  }

  public function get_phone_number(): string {
    return $this->phone_number;
  }

  public function get_cpf(): string {
    return $this->cpf;
  }

  public function get_name(): string {
    return $this->name;
  }

  public function get_birth_date(): string {
    return $this->birth_date;
  }

  public function get_current_points(): int {
    return (int) ($this->current_points ?? 0);
  }

  public function is_active(): bool {
    return (bool) ($this->active ?? false);
  }

  public function set_cpf(string $cpf): void {
    $this->cpf = $cpf;
  }

  public function set_email(string $email): void {
    $this->email = $email;
  }

  public function set_name(string $name): void {
    $this->name = $name;
  }

  public function set_birth_date(string $birth_date): void {
    $this->birth_date = $birth_date;
  }

  public function set_phone_number(string $phone_number): void {
    $this->phone_number = $phone_number;
  }

  public function set_verified_email(bool $verified_email): void {
    $this->verified_email = $verified_email;
  }

  public function set_current_points(int $points): void {
    $this->current_points = $points;
  }

  public function activate(): void {
    $this->active = true;
  }
  
  public function deactivate(): void {
    $this->active = false;
  }
}
