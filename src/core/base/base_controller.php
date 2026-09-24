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

  protected function is_error_result($result): bool {
    return is_array($result) && isset($result['errors']);
  }

  protected function is_null_result($result): bool {
    return $result === null;
  }

  protected function is_primitive_type_result($result): bool {
    return is_scalar($result);
  }

  protected function is_model_result($result): bool {
    return $result instanceof BaseModel;
  }

  protected function is_array_result($result): bool {
    return is_array($result) && !isset($result['errors']);
  }

  protected function handle_error(array $errors): void {
    if (isset($errors['not_found_error'])) {
      $this->json_response(['errors' => $errors], 404);
      return;
    }

    if (isset($errors['service_error'])) {
      $this->json_response(['errors' => $errors], 500);
      return;
    }

    if (isset($errors['server_error'])) {
      $this->json_response(['errors' => $errors], 500);
      return;
    }

    $this->json_response(['errors' => $errors], 422);
  }

  protected function handle_result($result, int $success_code = 200): void {
    if ($this->is_error_result($result)) {
      $this->handle_error($result['errors']);
      return;
    }

    if ($this->is_null_result($result)) {
      $this->json_response([
        'errors' => ['not_found_error' => 'Resource not found']
      ], 404);
      return;
    }
    
    if ($this->is_model_result($result)) {
      $this->json_response($result->to_array(), $success_code);
      return;
    }

    if ($this->is_primitive_type_result($result)) {
      $this->json_response(['result' => $result]);
      return;
    }

    if ($this->is_array_result($result)) {
      $items = [];
      foreach ($result as $item) {
        if ($item instanceof BaseModel) {
          $items[] = $item->to_array();
        } else {
          $this->json_response([
            'errors' => ['server_error' => 'Unexpected type in array']
          ], 500);
          return;
        }
      }
      $this->json_response($items, $success_code);
      return;
    }
    
    $this->json_response(['errors' => ['server_error' => 'Unexpected type']], 500);
  }
}