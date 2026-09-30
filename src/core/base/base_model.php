<?php
declare(strict_types=1);

namespace Core\Base;

use App\Core\Utils\NeutralValue;

abstract class BaseModel {
  public array $cast_conversion_types = [
    'int'      => 'int',
    'float'    => 'float',
    'string'   => 'string',
    'bool'     => 'bool',
    'array'    => 'array',
    'json'     => 'json',
    'uuid'     => 'string',
    'date'     => 'date',
    'datetime' => 'datetime',
    'phone'    => 'string',
    'email'    => 'string',
    'cpf'      => 'string',
    'cep'      => 'string'
  ];

  private array $cast_functions = [];

  protected array $attributes = [];
  protected array $fillables = [];
  protected array $hiddens = [];
  protected array $cast_types = [];

  protected function merge_attributes_and_types(array $attributes_and_types): void {
    foreach ($attributes_and_types as $attribute_and_type) {
      $this->attributes[$attribute_and_type[0]] = null;
      $this->cast_types[$attribute_and_type[0]] = $attribute_and_type[1];
    }
  }

  protected function merge_fillables(array $fillables): void {
    foreach ($fillables as $fillable) {
      $this->fillables[$fillable] = true;
    }
  }

  protected function merge_hiddens(array $hiddens): void {
    foreach ($hiddens as $hidden) {
      $this->hiddens[$hidden] = true;
    }
  }

  public function __construct(
    array $attributes_and_types = [],
    array $fillables = [],
    array $hiddens = []
  ) {
      foreach($attributes_and_types as $attribute_and_type) {
        $this->attributes[$attribute_and_type[0]] = null;
        $this->cast_types[$attribute_and_type[0]] = $attribute_and_type[1];
      }

      foreach($fillables as $fillable) {
        $this->fillables[$fillable] = true;
      }

      foreach($hiddens as $hidden) {
        $this->hiddens[$hidden] = true;
      }

      $this->register_default_casts();
  }

  public function fill(array $attributes): void {
    foreach ($attributes as $key => $value) {
      if (isset($this->fillables[$key])) {
        $this->attributes[$key] = $this->cast($key, $value);
      }
    }
  }

  protected function cast(string $key, $value) {
    if ($value instanceof NeutralValue) {
      return $value;
    }

    if (isset($this->cast_types[$key])) {
      $type = $this->cast_types[$key];

      if (isset($this->cast_functions[$type])) {
        return $this->cast_functions[$type]($value);
      }
      
    }
    
    return $value;
  }

  public function __get(string $name) {
    if (!array_key_exists($name, $this->attributes)) {
        throw new \Exception("The column '{$name}' doesn't exist in this model.");
    }

    return $this->attributes[$name];
  }

  public function __set(string $name, $value): void {
    if (!array_key_exists($name, $this->attributes)) {
        throw new \Exception("The column '{$name}' doesn't exist in this model.");
    }

    $this->attributes[$name] = $this->cast($name, $value);
  }

  public function __isset(string $name): bool {
    return isset($this->attributes[$name]);
  }

  public function to_array(): array {
    $data = [];

    foreach ($this->attributes as $key => $value) {
      if ($value instanceof NeutralValue) {
        $data[$key] = null;
      } else {
        $data[$key] = $value;
      }
    }

    foreach (array_keys($this->hiddens) as $key) {
      unset($data[$key]);
    }

    return $data;
  }

  public function to_json(): string {
    return json_encode($this->to_array());
  }

  public function get_attributes(): array {
    return $this->attributes;
  }

  public function get_cast_types(): array {
    return $this->cast_types;
  }

  public function get_fillables(): array {
    return array_keys($this->fillables);
  }

  private function register_default_casts(): void {
    $this->cast_functions = [
      'int'      => function($v) { return (int) $v; },
      'float'    => function($v) { return (float) $v; },
      'string'   => function($v) { return (string) $v; },
      'bool'     => function($v) { return (bool) $v; },
      'array'    => function($v) { return (array) $v; },
      'json'     => function($v) { return is_string($v) ? json_decode($v, true) : $v; },
      'uuid'     => function($v) { return (string) $v; },
      'date'     => function($v) { return date('Y-m-d', is_string($v) ? strtotime($v) : $v); },
      'datetime' => function($v) { return date('Y-m-d H:i:s', is_string($v) ? strtotime($v) : $v); },
      'phone'    => function($v) { return (string) $v; },
      'email'    => function($v) { return (string) $v; },
      'cpf'      => function($v) { return (string) $v; },
      'cep'      => function($v) { return (string) $v; }
    ];
  }
}