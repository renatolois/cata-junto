<?php
declare(strict_types=1);

namespace Core\Base;

abstract class BaseAdapter {
  protected array $config = [];

  abstract public function connect(array $config): void;
  abstract public function disconnect(): void;
  
  abstract public function insert(string $table, array $data): array;
  abstract public function select(string $table, array $where = []): array;
  abstract public function select_by_id(string $table, int $id): ?array;
  abstract public function update(string $table, int $id, array $data): array;
  abstract public function delete(string $table, int $id): bool;
  abstract public function join(string $main_table, string $join_table, string $foreign_key, string $select = '*'): array;
  
  public function set_config(array $config): void {
    $this->config = $config;
  }
  
  public function get_config(): array {
    return $this->config;
  }
}