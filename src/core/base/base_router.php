<?php
declare(strict_types=1);

namespace Core\Base;

use ReflectionMethod;
use Throwable;
use Core\Utils\AppConstants;

abstract class BaseRouter {
  protected array $routes = [];
  protected array $controllers = [];

  private const MAX_PARAM_LENGTH = 255;

  public function __construct(array $controllers) {
    $this->controllers = $controllers;
  }

  public function set_route(string $method, string $route, string $action, array $middlewares = [], bool $inject_auth_id = false): void {
    $method = strtoupper($method);
    $route = $this->normalize_route($route);

    if (!preg_match('/^[A-Z]+$/', $method)) {
      throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
    }

    if (!str_contains($action, '@')) {
      throw new \InvalidArgumentException("Invalid action format: {$action}");
    }

    [$controller_name, $controller_method] = explode('@', $action, 2);

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $controller_name)) {
      throw new \InvalidArgumentException("Invalid controller name: {$controller_name}");
    }

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $controller_method)) {
      throw new \InvalidArgumentException("Invalid action method: {$controller_method}");
    }

    foreach ($middlewares as $middleware) {
      if (!is_object($middleware) || !method_exists($middleware, 'handle')) {
        throw new \InvalidArgumentException("Middleware must have a handle() method.");
      }
    }

    $pattern = $this->route_to_regex($route);

    $this->routes[$method][] = [
      'route'          => $route,
      'action'         => $action,
      'regex'          => $pattern,
      'middlewares'    => $middlewares,
      'inject_auth_id' => $inject_auth_id,
    ];
  }

  protected function call(string $action, array $params = [], array $middlewares = [], bool $inject_auth_id = false): void {
    if (AppConstants::RUN_MODE === 'debug') {
      $middleware_classes = array_map(fn($m) => get_class($m), $middlewares);
      error_log("ROUTER: call action={$action} middlewares=[" . implode(',', $middleware_classes) . "] inject_auth_id=" . ($inject_auth_id ? 'true' : 'false'));
    }

    $context = ['params' => $params, 'auth' => null];

    foreach ($middlewares as $middleware) {
      if (AppConstants::RUN_MODE === 'debug') {
        error_log("ROUTER: running middleware " . get_class($middleware));
      }

      try {
        $result = $middleware->handle($context);
      } catch (Throwable $e) {
        if (AppConstants::RUN_MODE === 'debug') {
          error_log("ROUTER: middleware " . get_class($middleware) . " threw: " . $e->getMessage());
        }
        $this->json_error('Internal server error', 500);
        return;
      }

      if (!is_array($result) || !isset($result['ok'])) {
        if (AppConstants::RUN_MODE === 'debug') {
          error_log("ROUTER: middleware " . get_class($middleware) . " returned invalid result: " . json_encode($result));
        }
        $this->json_error('Middleware returned invalid result', 500);
        return;
      }

      if (!$result['ok']) {
        if (AppConstants::RUN_MODE === 'debug') {
          error_log("ROUTER: middleware " . get_class($middleware) . " rejected with status=" . ($result['status'] ?? 403) . " error=" . ($result['error'] ?? 'Forbidden'));
        }
        $this->json_error($result['error'] ?? 'Forbidden', $result['status'] ?? 403);
        return;
      }

      if (AppConstants::RUN_MODE === 'debug') {
        error_log("ROUTER: middleware " . get_class($middleware) . " passed");
      }

      $context = $result['context'] ?? $context;
    }

    if ($inject_auth_id && isset($context['auth']) && is_array($context['auth'])) {
      if (!isset($params['id'])) {
        if (isset($context['auth']['person_id'])) {
          $params['id'] = $context['auth']['person_id'];
        } elseif (isset($context['auth']['collection_location_id'])) {
          $params['id'] = $context['auth']['collection_location_id'];
        }
      }

      if (isset($context['auth']['person_id'])) {
        $params['auth_person_id'] = $context['auth']['person_id'];
      } elseif (isset($context['auth']['collection_location_id'])) {
        $params['auth_location_id'] = $context['auth']['collection_location_id'];
      }

      if (isset($context['auth']['contract_id'])) {
        $params['contract_id'] = $context['auth']['contract_id'];
      }

      if (isset($context['auth']['role'])) {
        $params['auth_role'] = $context['auth']['role'];
      }
    }

    if (AppConstants::RUN_MODE === 'debug') {
      error_log("ROUTER: final params=" . json_encode($params));
    }

    [$controller_name, $method] = explode('@', $action, 2);

    $instance = $this->controllers[$controller_name] ?? null;
    if ($instance === null) {
      $this->json_error('Controller not found', 404);
      return;
    }

    if (!method_exists($instance, $method)) {
      $this->json_error('Method not found', 404);
      return;
    }

    $reflection = new ReflectionMethod($instance, $method);
    if (!$reflection->isPublic()) {
      $this->json_error('Method not found', 404);
      return;
    }

    if (method_exists($instance, 'set_route_params')) {
      $instance->set_route_params($params);
    }

    if (AppConstants::RUN_MODE === 'debug') {
      error_log("ROUTER: invoking {$controller_name}::{$method}");
    }

    try {
      $instance->$method();
    } catch (Throwable $e) {
      if (AppConstants::RUN_MODE === 'debug') {
        error_log("ROUTER: controller threw: " . $e->getMessage());
      }
      $this->json_error(
        AppConstants::RUN_MODE === 'debug' ? $e->getMessage() : 'Internal server error',
        500
      );
    }
  }

  public function dispatch(string $method, string $uri): bool {
    $method = strtoupper($method);

    if (AppConstants::RUN_MODE === 'debug') {
      error_log("ROUTER: dispatch class=" . static::class . " method={$method} uri={$uri}");
    }

    if (!preg_match('/^[A-Z]+$/', $method)) {
      $this->json_error('Invalid method', 400);
      return true;
    }

    $uri_route = parse_url($uri, PHP_URL_PATH) ?? '/';
    $uri_route = rawurldecode($uri_route);

    if (str_contains($uri_route, '..') || str_contains($uri_route, "\0")) {
      $this->json_error('Invalid URI', 400);
      return true;
    }

    $uri_route = $this->normalize_route($uri_route);

    if (!isset($this->routes[$method])) {
      return false;
    }

    foreach ($this->routes[$method] as $route) {
      if (preg_match($route['regex'], $uri_route, $matches)) {
        if (AppConstants::RUN_MODE === 'debug') {
          error_log("ROUTER: matched route={$route['route']} action={$route['action']}");
        }
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $this->call($route['action'], $params, $route['middlewares'], $route['inject_auth_id']);
        return true;
      }
    }

    return false;
  }

  public function get_routes(): array {
    return $this->routes;
  }

  private function normalize_route(string $route): string {
    $route = '/' . ltrim($route, '/');
    $route = rtrim($route, '/');
    return $route === '' ? '/' : $route;
  }

  private function route_to_regex(string $route): string {
    $placeholder_pattern = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/';

    $parts = preg_split($placeholder_pattern, $route, -1, PREG_SPLIT_DELIM_CAPTURE);

    $regex = '';
    $is_placeholder = false;

    foreach ($parts as $part) {
      if ($is_placeholder) {
        $regex .= '(?P<' . $part . '>[^/]{1,' . self::MAX_PARAM_LENGTH . '})';
        $is_placeholder = false;
      } else {
        $regex .= preg_quote($part, '#');
        $is_placeholder = true;
      }
    }

    return '#^' . $regex . '$#';
  }

  private function json_error(string $message, int $status_code): void {
    if (AppConstants::RUN_MODE === 'debug') {
      error_log("ROUTER_ERR: status={$status_code} message={$message}");
    }
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
  }
}