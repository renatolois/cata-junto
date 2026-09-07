<?php
declare(strict_types=1);

namespace Core\Base;

abstract class BaseController {
  protected BaseService $service;
  protected array $body = [];
  protected array $query = [];
  protected array $route_params = [];

  public function __construct(BaseService $service) {
    $this->service = $service;
    $this->query = $_GET;
    $input = file_get_contents('php://input');
    
    if ($input) {
      $decoded = json_decode($input, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $this->body = $decoded;
      } else {
        parse_str($input, $this->body);
      }
    }
  }

  public function set_route_params(array $params): void {
    $this->route_params = $params;
  }

  protected function get_body(?string $key = null, $default = null) {
    if ($key === null) {
      return $this->body;
    }
    return $this->body[$key] ?? $default;
  }

  protected function get_query(?string $key = null, $default = null) {
    if ($key === null) {
      return $this->query;
    }
    return $this->query[$key] ?? $default;
  }

  protected function get_route(?string $key = null, $default = null) {
    if ($key === null) {
      return $this->route_params;
    }
    return $this->route_params[$key] ?? $default;
  }

  protected function json_response(array $data, int $status_code = 200): void {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
  }

  protected function error_response(string $message, int $status_code = 400): void {
    $this->json_response(['error' => $message], $status_code);
  }
}