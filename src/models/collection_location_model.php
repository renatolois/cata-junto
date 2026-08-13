<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class CollectionLocationModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'uuid'],
        ['responsable_email', 'email'],
        ['verified_email', 'bool'],
        ['password_hash', 'string'],
        ['responsable_phone_number', 'phone'],
        
        ['street', 'string'],
        ['number', 'string'],
        ['neighborhood', 'string'],
        ['complement', 'string'],
        ['city', 'string'],
        ['state', 'string'],
        ['cep', 'cep'],

        ['current_points', 'int'],
        ['active', 'bool']
      ],
      fillables: [
        'responsable_email', 'verified_email', 'responsable_phone_number', 
        'street', 'number', 'neighborhood', 'complement',
        'city', 'state', 'cep', 'current_points', 'active'
      ],
      hiddens: [
        'password_hash'
      ]
    );

    if (empty($this->current_points)) {
      $this->current_points = 0;
    }
    
    if (empty($this->active)) {
      $this->active = true;
    }

    if (!isset($this->verified_email)) {
      $this->verified_email = false;
    }
  }

  // Getters
  public function get_id(): string {
    return $this->id;
  }

  public function get_responsable_email(): string {
    return $this->responsable_email;
  }

  public function get_verified_email(): bool {
    return (bool) ($this->verified_email ?? false);
  }

  public function get_responsable_phone_number(): string {
    return $this->responsable_phone_number;
  }

  public function get_street(): string {
    return $this->street;
  }

  public function get_number(): string {
    return $this->number;
  }

  public function get_neighborhood(): string {
    return $this->neighborhood;
  }

  public function get_complement(): string {
    return $this->complement ?? '';
  }

  public function get_city(): string {
    return $this->city;
  }

  public function get_state(): string {
    return $this->state;
  }

  public function get_cep(): string {
    return $this->cep;
  }

  public function get_current_points(): int {
    return (int) ($this->current_points ?? 0);
  }

  public function is_active(): bool {
    return (bool) ($this->active ?? false);
  }

  // Setters
  public function set_responsable_email(string $responsable_email): void {
    $this->responsable_email = $responsable_email;
  }

  public function set_verified_email(bool $verified_email): void {
    $this->verified_email = $verified_email;
  }

  public function set_responsable_phone_number(string $responsable_phone_number): void {
    $this->responsable_phone_number = $responsable_phone_number;
  }

  public function set_street(string $street): void {
    $this->street = $street;
  }

  public function set_number(string $number): void {
    $this->number = $number;
  }

  public function set_neighborhood(string $neighborhood): void {
    $this->neighborhood = $neighborhood;
  }

  public function set_complement(string $complement): void {
    $this->complement = $complement;
  }

  public function set_city(string $city): void {
    $this->city = $city;
  }

  public function set_state(string $state): void {
    $this->state = $state;
  }

  public function set_cep(string $cep): void {
    $this->cep = $cep;
  }

  public function set_current_points(int $points): void {
    $this->current_points = $points;
  }

  public function set_active(bool $active): void {
    $this->active = $active;
  }

  public function set_password(string $password): void {
    $this->password_hash = password_hash($password, PASSWORD_BCRYPT);
  }

  public function set_address(
    string $street,
    string $number,
    string $neighborhood,
    string $city,
    string $state,
    string $cep,
    string $complement = ''
  ): void {
    $this->street = $street;
    $this->number = $number;
    $this->neighborhood = $neighborhood;
    $this->complement = $complement;
    $this->city = $city;
    $this->state = $state;
    $this->cep = $cep;
  }

  // Métodos de ação
  public function activate(): void {
    $this->active = true;
  }
  
  public function deactivate(): void {
    $this->active = false;
  }

  public function verify_password(string $password): bool {
    return password_verify($password, $this->password_hash ?? '');
  }
}
