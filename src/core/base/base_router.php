<?php
declare(strict_types=1);

namespace Core\Base;

abstract class BaseRouter {
  protected array $routes = [];
  protected object $controller;

  public function __construct(object $controller) {
    $this->controller = $controller;
  }

  public function set_route(string $method, string $route, string $action): void {
    $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route);
    $pattern = '#^' . $pattern . '$#';
    
    $this->routes[strtoupper($method)][] = [
      'route' => $route,
      'action' => $action,
      'regex' => $pattern
    ];
  }

  protected function call(string $action, array $params = []): void {
    [$controller, $method] = explode('@', $action);
    
    // Usa o controller já instanciado
    $instance = $this->controller;
    
    if (!method_exists($instance, $method)) {
      http_response_code(404);
      echo json_encode(['error' => 'Method not found']);
      return;
    }

    // Seta os parâmetros da rota no controller
    if (method_exists($instance, 'set_route_params')) {
      $instance->set_route_params($params);
    }

    $instance->$method();
  }

  public function dispatch(string $method, string $uri): void {
    $method = strtoupper($method);
    $uri_route = parse_url($uri, PHP_URL_PATH);
    $uri_route = rtrim($uri_route, '/') ?: '/';

    if (!isset($this->routes[$method])) {
      http_response_code(405);
      echo json_encode(['error' => 'Method not allowed']);
      return;
    }

    foreach ($this->routes[$method] as $route) {
      if (preg_match($route['regex'], $uri_route, $matches)) {
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $this->call($route['action'], $params);
        return;
      }
    }

    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
  }

  public function get_routes(): array {
    return $this->routes;
  }
}